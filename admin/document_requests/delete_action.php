<?php
// admin/document_requests/delete_action.php
require_once('../../config.php');

// 응답 헤더 설정
header('Content-Type: application/json');

// 로그인 확인
if(!isset($_SESSION['userdata']['id'])){
    echo json_encode(['status' => 'failed', 'msg' => '로그인이 필요합니다.']);
    exit;
}

// POST 데이터 확인
if(!isset($_POST['ids']) || empty($_POST['ids'])){
    echo json_encode(['status' => 'failed', 'msg' => '삭제할 항목을 선택해주세요.']);
    exit;
}

// 권한 확인 (관리자만 삭제 가능)
$user_type = $_SESSION['userdata']['type'] ?? 0;
if($user_type != 1){
    echo json_encode(['status' => 'failed', 'msg' => '삭제 권한이 없습니다.']);
    exit;
}

$ids = $_POST['ids'];
$deleted_count = 0;
$failed_count = 0;
$errors = [];

// 트랜잭션 시작
$conn->begin_transaction();

try {
    foreach($ids as $id){
        $id = $conn->real_escape_string($id);

        // 1. 업로드된 파일들 먼저 삭제
        $file_qry = $conn->query("
            SELECT uf.* 
            FROM uploaded_files uf 
            WHERE uf.request_id = '{$id}'
        ");

        while($file = $file_qry->fetch_assoc()){
            // Wasabi에서 파일 삭제
            if(!empty($file['wasabi_key']) && !empty($file['wasabi_bucket'])){
                // UploadHandler 클래스 사용
                require_once(base_app . 'classes/UploadHandler.php');
                $uploadHandler = new UploadHandler();

                // Wasabi 파일 삭제
                $delete_result = $uploadHandler->deleteFromWasabi($file['wasabi_key'], $file['wasabi_bucket']);
                if(!$delete_result){
                    error_log("Wasabi 파일 삭제 실패: " . $file['wasabi_key']);
                }
            }

            // 로컬 파일 삭제
            if(!empty($file['file_path'])){
                $local_path = base_app . 'uploads/' . $file['file_path'];
                if(file_exists($local_path)){
                    unlink($local_path);
                }
            }
        }

        // 2. document_uploads 테이블에서 파일 경로 조회 및 삭제
        $upload_qry = $conn->query("
            SELECT du.file_path 
            FROM document_uploads du 
            INNER JOIN request_documents rd ON du.document_id = rd.id 
            WHERE rd.request_id = '{$id}'
        ");

        while($upload = $upload_qry->fetch_assoc()){
            $file_path = base_app . 'uploads/' . $upload['file_path'];
            if(file_exists($file_path)){
                unlink($file_path);
            }
        }

        // 3. 관련 테이블 데이터 삭제 (외래키 CASCADE로 자동 삭제되지만 명시적으로 처리)
        $tables_to_clean = [
            'access_tokens',
            'document_cost_details',
            'document_request_details',
            'document_targets',
            'document_writers',
            'review_credentials',
            'workflow_status',
            'upload_notifications',
            'uploaded_files',
            'request_documents'
        ];

        foreach($tables_to_clean as $table){
            $conn->query("DELETE FROM {$table} WHERE request_id = '{$id}'");
        }

        // 4. 메인 테이블 삭제
        $delete_main = $conn->query("DELETE FROM document_requests WHERE id = '{$id}'");

        if($delete_main){
            $deleted_count++;

            // 삭제 로그 기록
            $user_id = $_SESSION['userdata']['id'];
            $user_name = $_SESSION['userdata']['firstname'] . ' ' . $_SESSION['userdata']['lastname'];
            error_log("Document request deleted - ID: {$id}, User: {$user_name} (ID: {$user_id})");
        } else {
            $failed_count++;
            $errors[] = "ID {$id}: " . $conn->error;
        }
    }

    // 트랜잭션 커밋
    $conn->commit();

    // 결과 반환
    if($deleted_count > 0 && $failed_count == 0){
        $_SESSION['success'] = "{$deleted_count}개의 서류 요청이 삭제되었습니다.";
        echo json_encode([
            'status' => 'success',
            'msg' => "{$deleted_count}개의 서류 요청이 삭제되었습니다."
        ]);
    } else if($deleted_count > 0 && $failed_count > 0){
        $_SESSION['warning'] = "{$deleted_count}개 삭제 성공, {$failed_count}개 삭제 실패";
        echo json_encode([
            'status' => 'partial',
            'msg' => "{$deleted_count}개 삭제 성공, {$failed_count}개 삭제 실패",
            'errors' => $errors
        ]);
    } else {
        throw new Exception("삭제 실패: " . implode(", ", $errors));
    }

} catch (Exception $e) {
    // 트랜잭션 롤백
    $conn->rollback();

    $_SESSION['error'] = "삭제 중 오류가 발생했습니다: " . $e->getMessage();
    echo json_encode([
        'status' => 'failed',
        'msg' => "삭제 중 오류가 발생했습니다.",
        'error' => $e->getMessage(),
        'errors' => $errors
    ]);
}

$conn->close();
?>
<?php
// admin/upload_portal/upload_handler.php
require_once('upload_init.php');  // 전용 초기화 파일 사용
require_once(base_app . 'classes/UploadHandler.php');

// 에러 리포팅 설정
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 응답 헤더 설정
header('Content-Type: application/json; charset=utf-8');

// 초기 응답 배열
$response = ['status' => 'error', 'msg' => '알 수 없는 오류가 발생했습니다.'];

try {
    // POST 데이터 확인
    if(!isset($_POST['request_id']) || !isset($_POST['document_id'])) {
        $response = ['status' => 'error', 'msg' => '필수 정보가 누락되었습니다. (요청 ID 또는 문서 ID)'];
        echo json_encode($response);
        exit;
    }

    // 파일 확인
    if(!isset($_FILES['file'])) {
        $response = ['status' => 'error', 'msg' => '업로드할 파일이 선택되지 않았습니다.'];
        echo json_encode($response);
        exit;
    }

    // 파일 업로드 에러 체크
    if($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => '파일 크기가 서버 설정 한계를 초과했습니다.',
            UPLOAD_ERR_FORM_SIZE => '파일 크기가 폼 설정 한계를 초과했습니다.',
            UPLOAD_ERR_PARTIAL => '파일이 부분적으로만 업로드되었습니다.',
            UPLOAD_ERR_NO_FILE => '파일이 업로드되지 않았습니다.',
            UPLOAD_ERR_NO_TMP_DIR => '임시 폴더가 없습니다.',
            UPLOAD_ERR_CANT_WRITE => '파일을 디스크에 쓸 수 없습니다.',
            UPLOAD_ERR_EXTENSION => 'PHP 확장에 의해 파일 업로드가 중지되었습니다.'
        ];

        $error_msg = isset($upload_errors[$_FILES['file']['error']])
            ? $upload_errors[$_FILES['file']['error']]
            : '파일 업로드 중 오류가 발생했습니다. (코드: ' . $_FILES['file']['error'] . ')';

        $response = ['status' => 'error', 'msg' => $error_msg];
        echo json_encode($response);
        exit;
    }

    // 입력값 검증 및 이스케이프
    $request_id = filter_var($_POST['request_id'], FILTER_VALIDATE_INT);
    $document_id = filter_var($_POST['document_id'], FILTER_VALIDATE_INT);

    if($request_id === false || $document_id === false) {
        $response = ['status' => 'error', 'msg' => '잘못된 요청 정보입니다.'];
        echo json_encode($response);
        exit;
    }

    // 데이터베이스 연결 확인
    if(!$conn || $conn->connect_error) {
        $response = ['status' => 'error', 'msg' => '데이터베이스 연결 오류가 발생했습니다.'];
        echo json_encode($response);
        exit;
    }

    // 문서 정보 확인
    $stmt = $conn->prepare("
        SELECT rd.*, dr.upload_token, dr.status as request_status 
        FROM `request_documents` rd 
        LEFT JOIN `document_requests` dr ON rd.request_id = dr.id 
        WHERE rd.id = ? AND rd.request_id = ?
    ");

    if(!$stmt) {
        $response = ['status' => 'error', 'msg' => '쿼리 준비 중 오류가 발생했습니다.'];
        echo json_encode($response);
        exit;
    }

    $stmt->bind_param("ii", $document_id, $request_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows <= 0) {
        $response = ['status' => 'error', 'msg' => '유효하지 않은 문서 요청입니다.'];
        echo json_encode($response);
        exit;
    }

    $doc_info = $result->fetch_assoc();
    $stmt->close();

    // 이미 업로드된 파일인지 확인
    if($doc_info['status'] == 1) {
        $response = ['status' => 'error', 'msg' => '이미 파일이 업로드된 문서입니다. 기존 파일을 삭제 후 다시 업로드해주세요.'];
        echo json_encode($response);
        exit;
    }

    // 서류명 가져오기
    $document_name = $doc_info['document_name'];

    // 현재 요청 상태 저장
    $current_request_status = $doc_info['request_status'];

    // 업로드 처리
    $uploadHandler = new UploadHandler();
    // 서류명을 추가 파라미터로 전달
    $result = $uploadHandler->uploadDocument($_FILES['file'], $request_id, $document_id, $document_name);

    // 업로드 성공 시 추가 작업
    if($result['status'] === 'success') {
        // 트랜잭션 시작
        $conn->begin_transaction();

        try {
            // 1. 첫 업로드인 경우에만 상태를 '진행중'으로 변경 (완료 상태로는 변경하지 않음)
            if($current_request_status == 0) {
                $update_status = $conn->prepare("UPDATE document_requests SET status = 1 WHERE id = ?");
                $update_status->bind_param("i", $request_id);
                $update_status->execute();
                $update_status->close();

                // 워크플로우 기록
                $workflow_stmt = $conn->prepare("
                    INSERT INTO workflow_status 
                    (request_id, current_step, step_name, step_description, started_at, is_current) 
                    VALUES (?, 'processing', '진행중', '첫 번째 서류가 업로드되어 진행중 상태로 변경되었습니다.', NOW(), 1)
                ");
                $workflow_stmt->bind_param("i", $request_id);
                $workflow_stmt->execute();
                $workflow_stmt->close();

                // 기존 current 상태 해제
                $conn->query("UPDATE workflow_status SET is_current = 0 WHERE request_id = {$request_id} AND id != LAST_INSERT_ID()");
            }

            // 2. 진행률 계산 (정보 제공용으로만 사용, 자동 완료 처리 제거)
            $progress_stmt = $conn->prepare("
                SELECT 
                    COUNT(*) as total_docs,
                    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as completed_docs,
                    SUM(CASE WHEN is_required = 1 THEN 1 ELSE 0 END) as required_docs,
                    SUM(CASE WHEN is_required = 1 AND status = 1 THEN 1 ELSE 0 END) as completed_required_docs
                FROM request_documents 
                WHERE request_id = ?
            ");
            $progress_stmt->bind_param("i", $request_id);
            $progress_stmt->execute();
            $progress_result = $progress_stmt->get_result();
            $progress_data = $progress_result->fetch_assoc();
            $progress_stmt->close();

            // 진행률 계산
            $total_progress = $progress_data['total_docs'] > 0
                ? round(($progress_data['completed_docs'] / $progress_data['total_docs']) * 100)
                : 0;

            // 필수 서류 완료율
            $required_progress = $progress_data['required_docs'] > 0
                ? round(($progress_data['completed_required_docs'] / $progress_data['required_docs']) * 100)
                : 100;

            // 자동 완료 처리 제거 - 사용자가 명시적으로 제출 완료 버튼을 눌러야만 완료됨
            // 모든 필수 서류가 완료되어도 상태를 '진행중(1)'으로 유지

            // 응답에 진행률 정보만 추가
            $result['progress'] = [
                'total' => $total_progress,
                'required' => $required_progress,
                'completed_docs' => $progress_data['completed_docs'],
                'total_docs' => $progress_data['total_docs'],
                'all_required_completed' => ($required_progress == 100), // 모든 필수 서류 완료 여부
                'status_updated' => ($current_request_status == 0)
            ];

            // 모든 필수 서류가 완료된 경우 안내 메시지 추가
            if($required_progress == 100) {
                $result['message'] = '모든 필수 서류가 업로드되었습니다. 제출 완료 버튼을 눌러 최종 제출해주세요.';
            }

            $conn->commit();

        } catch(Exception $e) {
            $conn->rollback();
            // 상태 업데이트 실패는 로그에만 기록하고 업로드는 성공으로 처리
            error_log('Status update failed: ' . $e->getMessage());
        }
    }

    // 결과 반환
    echo json_encode($result);

} catch(Exception $e) {
    // 예외 처리
    error_log('Upload Error: ' . $e->getMessage());
    $response = [
        'status' => 'error',
        'msg' => '파일 업로드 처리 중 오류가 발생했습니다.'
    ];

    // 개발 환경에서는 상세 오류 표시
    if(defined('DEV_MODE') && DEV_MODE === true) {
        $response['debug'] = $e->getMessage();
    }

    echo json_encode($response);
}
?>
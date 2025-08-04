<?php
// admin/upload_portal/download.php
require_once('upload_init.php');
require_once(base_app . 'classes/UploadHandler.php');

// 다운로드 ID 확인
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('잘못된 요청입니다.');
}

$document_id = intval($_GET['id']);

// 토큰 확인 (선택사항)
$token = $_GET['token'] ?? '';

try {
    // 문서 정보 조회 - uploaded_files 테이블과 document_id로 조인
    $stmt = $conn->prepare("
        SELECT rd.*, uf.*, dr.upload_token 
        FROM `request_documents` rd
        LEFT JOIN `uploaded_files` uf ON uf.document_id = rd.id
        LEFT JOIN `document_requests` dr ON rd.request_id = dr.id
        WHERE rd.id = ? AND rd.status = 1
    ");

    if(!$stmt) {
        die('데이터베이스 오류');
    }

    $stmt->bind_param("i", $document_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows <= 0) {
        die('파일을 찾을 수 없습니다.');
    }

    $file_info = $result->fetch_assoc();
    $stmt->close();

    // 권한 확인 (관리자가 아닌 경우 토큰 확인)
    if(!isset($_SESSION['userdata']) || $_SESSION['userdata']['type'] != 1) {
        if(empty($token) || $token !== $file_info['upload_token']) {
            die('권한이 없습니다.');
        }
    }

    // Wasabi 파일인 경우
    if($file_info['wasabi_key']) {
        // UploadHandler 인스턴스 생성
        $uploadHandler = new UploadHandler();

        // 임시 서명된 URL 생성 (1시간 유효)
        $download_url = $uploadHandler->getWasabiFileUrl($file_info['wasabi_key'], 3600);

        if($download_url) {
            // Wasabi URL로 리다이렉트
            header('Location: ' . $download_url);
            exit;
        } else {
            die('다운로드 URL 생성 실패');
        }
    } else {
        // 로컬 파일 다운로드
        $file_path = base_app . $file_info['file_path'];

        if(!file_exists($file_path)) {
            die('파일이 존재하지 않습니다.');
        }

        // 파일 정보
        $file_size = filesize($file_path);
        $file_name = $file_info['file_name'];
        $mime_type = mime_content_type($file_path);

        // 다운로드 헤더 설정
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . $file_name . '"');
        header('Content-Length: ' . $file_size);
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // 파일 출력
        readfile($file_path);

        // 다운로드 로그 (선택사항)
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        $log_stmt = $conn->prepare("
            INSERT INTO `upload_logs` 
            (request_id, document_id, action, file_name, ip_address, user_agent) 
            VALUES (?, ?, 'download', ?, ?, ?)
        ");

        if($log_stmt) {
            $log_stmt->bind_param("iisss",
                $file_info['request_id'],
                $document_id,
                $file_name,
                $ip,
                $user_agent
            );
            $log_stmt->execute();
            $log_stmt->close();
        }
    }

} catch(Exception $e) {
    error_log('Download error: ' . $e->getMessage());
    die('다운로드 중 오류가 발생했습니다.');
}
?>
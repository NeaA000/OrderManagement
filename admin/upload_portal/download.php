<?php
// admin/upload_portal/download.php
require_once('upload_init.php');
require_once(base_app . 'classes/UploadHandler.php');
require_once('auth_check.php');   // 보안 인증 모듈 추가

// 다운로드 ID 확인
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('잘못된 요청입니다.');
}

$document_id = intval($_GET['id']);

// 토큰 확인
$token = $_GET['token'] ?? '';

// ★ 내부 사용자(로그인) 다운로드 파라미터 확인
$internal_download = isset($_GET['internal_download']) && $_GET['internal_download'] == '1';

// IP 기반 의심스러운 활동 체크
$client_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
if(!checkSuspiciousActivity($conn, $client_ip)) {
    die('너무 많은 시도가 감지되었습니다. 잠시 후 다시 시도해주세요.');
}

try {
    // 문서 정보 조회 - 두 가지 방식 모두 지원 (uploaded_files 우선 조회)
    $stmt = $conn->prepare("
        SELECT 
            rd.id as document_id,
            rd.document_name,
            rd.request_id,
            rd.status,
            rd.file_name as rd_file_name,
            rd.file_path as rd_file_path,
            dr.upload_token,
            uf.id as uploaded_file_id,
            uf.original_name,
            uf.stored_name,
            uf.wasabi_key,
            uf.wasabi_bucket,
            uf.wasabi_region,
            uf.file_size
        FROM `request_documents` rd
        LEFT JOIN `document_requests` dr ON rd.request_id = dr.id
        LEFT JOIN `uploaded_files` uf ON uf.document_id = rd.id AND uf.is_deleted = 0
        WHERE rd.id = ? AND rd.status = 1
        ORDER BY uf.id DESC
        LIMIT 1
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

    // ★ 권한 확인 - 로그인한 모든 사용자 허용
    $has_access = false;

    // 내부 사용자(로그인) 확인
    if($internal_download && isLoggedIn()) {
        $has_access = true;
    }
    // 토큰이 있는 경우 검증
    else if(!empty($token)) {
        $validation = validateUploadToken($conn, $token, $file_info['request_id']);
        if($validation['valid'] || (isset($validation['allow_view']) && $validation['allow_view'])) {
            $has_access = true;
        } else {
            logFailedAttempt($conn, 'invalid_token_download', $token, $client_ip);
        }
    }

    if(!$has_access) {
        die('권한이 없습니다.');
    }

    // ★ 파일 다운로드 처리
    // 1. uploaded_files 테이블에 Wasabi 정보가 있는 경우
    if(!empty($file_info['wasabi_key']) && !empty($file_info['wasabi_bucket'])) {
        // Wasabi 사용 설정 확인
        if($_settings->info('use_wasabi') !== 'true') {
            error_log("Wasabi is not enabled but file has wasabi_key");
            die('파일을 찾을 수 없습니다.');
        }

        // UploadHandler 인스턴스 생성
        $uploadHandler = new UploadHandler();

        // 임시 서명된 URL 생성 (1시간 유효)
        $download_url = $uploadHandler->getWasabiFileUrl($file_info['wasabi_key'], 3600);

        if($download_url) {
            // Wasabi URL로 리다이렉트
            header('Location: ' . $download_url);
            exit;
        } else {
            error_log("Failed to generate Wasabi URL for key: " . $file_info['wasabi_key']);
            die('다운로드 URL 생성 실패');
        }
    }
    // 2. request_documents 테이블의 로컬 파일 경로 사용
    else if(!empty($file_info['rd_file_path'])) {
        $file_path = base_app . $file_info['rd_file_path'];

        // ★ 경로 보안 검증 (디렉토리 traversal 방지)
        if(!isSecurePath($file_path, base_app . 'uploads/')) {
            die('잘못된 파일 경로입니다.');
        }

        if(!file_exists($file_path)) {
            error_log("File not found: " . $file_path);
            die('파일이 존재하지 않습니다.');
        }

        // 파일 정보
        $file_size = filesize($file_path);
        $file_name = $file_info['original_name'] ?: $file_info['rd_file_name'] ?: basename($file_path);
        $mime_type = mime_content_type($file_path);

        // ★ Content-Type 보안 설정
        $safe_mime_types = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/jpeg',
            'image/png',
            'application/zip',
            'application/x-hwp',
            'application/vnd.hancom.hwp'
        ];

        if(!in_array($mime_type, $safe_mime_types)) {
            $mime_type = 'application/octet-stream';
        }

        // 다운로드 헤더 설정
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . addslashes($file_name) . '"');
        header('Content-Length: ' . $file_size);
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // ★ 보안 헤더 추가
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');

        // 파일 출력
        readfile($file_path);

        // 다운로드 로그
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $user_id = $_SESSION['userdata']['id'] ?? 0;
        $action_type = $internal_download ? 'internal_download' : 'external_download';

        $log_stmt = $conn->prepare("
            INSERT INTO `upload_logs` 
            (request_id, document_id, action, file_name, ip_address, user_agent, user_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        if($log_stmt) {
            $log_stmt->bind_param("iissssi",
                $file_info['request_id'],
                $document_id,
                $action_type,
                $file_name,
                $ip,
                $user_agent,
                $user_id
            );
            $log_stmt->execute();
            $log_stmt->close();
        }
        exit;
    } else {
        error_log("No file path found for document_id: " . $document_id);
        die('파일 정보를 찾을 수 없습니다.');
    }

} catch(Exception $e) {
    error_log("Download error: " . $e->getMessage());
    die('다운로드 중 오류가 발생했습니다.');
}
?>
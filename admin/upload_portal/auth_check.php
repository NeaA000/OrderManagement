<?php
// admin/upload_portal/auth_check.php
// 모든 업로드 관련 파일에서 include하여 사용하는 공통 인증 모듈

/**
 * 업로드 토큰 검증 함수
 * @param mysqli $conn 데이터베이스 연결
 * @param string $token 검증할 토큰
 * @param int|null $request_id 요청 ID (선택)
 * @return array 검증 결과
 */
function validateUploadToken($conn, $token, $request_id = null) {
    if(empty($token)) {
        return ['valid' => false, 'msg' => '인증 토큰이 없습니다.'];
    }

    // 토큰 검증 쿼리
    $query = "SELECT dr.*, 
              at.status as token_status,
              at.completed_at,
              at.use_count,
              at.last_used_at
              FROM document_requests dr
              LEFT JOIN access_tokens at ON dr.upload_token = at.token
              WHERE dr.upload_token = ?";

    if($request_id) {
        $query .= " AND dr.id = ?";
    }

    $stmt = $conn->prepare($query);
    if(!$stmt) {
        error_log("Token validation prepare failed: " . $conn->error);
        return ['valid' => false, 'msg' => '시스템 오류가 발생했습니다.'];
    }

    if($request_id) {
        $stmt->bind_param("si", $token, $request_id);
    } else {
        $stmt->bind_param("s", $token);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows == 0) {
        logFailedAttempt($conn, 'invalid_token', $token, $_SERVER['REMOTE_ADDR']);
        return ['valid' => false, 'msg' => '유효하지 않은 토큰입니다.'];
    }

    $data = $result->fetch_assoc();
    $stmt->close();

    // request_id가 제공된 경우 추가 검증
    if($request_id !== null && $data['id'] != $request_id) {
        logFailedAttempt($conn, 'request_id_mismatch', $token, $_SERVER['REMOTE_ADDR']);
        return ['valid' => false, 'msg' => '토큰과 요청 ID가 일치하지 않습니다.'];
    }

    // 완료된 요청인 경우
    if($data['status'] == 2 || $data['token_status'] == 'completed') {
        return [
            'valid' => false,
            'msg' => '이미 완료된 요청입니다. 추가 수정이 불가능합니다.',
            'allow_view' => true,  // 조회는 허용
            'data' => $data
        ];
    }

    // 토큰 사용 횟수 업데이트 (access_tokens 테이블이 있는 경우)
    if($data['token_status'] !== null) {
        $update_stmt = $conn->prepare("
            UPDATE access_tokens 
            SET use_count = use_count + 1,
                last_used_at = NOW()
            WHERE token = ?
        ");
        if($update_stmt) {
            $update_stmt->bind_param("s", $token);
            $update_stmt->execute();
            $update_stmt->close();
        }
    }

    return ['valid' => true, 'data' => $data];
}

/**
 * 실패한 접근 시도 로깅
 * @param mysqli $conn 데이터베이스 연결
 * @param string $action 액션 타입
 * @param string $token 시도한 토큰
 * @param string $ip IP 주소
 */
function logFailedAttempt($conn, $action, $token, $ip) {
    try {
        // security_logs 테이블이 있는지 확인
        $table_check = $conn->query("SHOW TABLES LIKE 'security_logs'");
        if($table_check->num_rows == 0) {
            // 테이블이 없으면 생성
            $conn->query("
                CREATE TABLE IF NOT EXISTS `security_logs` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `action` VARCHAR(50),
                    `token` VARCHAR(100),
                    `ip_address` VARCHAR(45),
                    `user_agent` TEXT,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
        }

        $stmt = $conn->prepare("
            INSERT INTO security_logs (action, token, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $stmt->bind_param("ssss", $action, $token, $ip, $user_agent);
        $stmt->execute();
        $stmt->close();
    } catch(Exception $e) {
        error_log("Security log error: " . $e->getMessage());
    }
}

/**
 * 의심스러운 활동 체크
 * @param mysqli $conn 데이터베이스 연결
 * @param string $ip IP 주소
 * @return bool 접근 허용 여부
 */
function checkSuspiciousActivity($conn, $ip) {
    try {
        // security_logs 테이블 존재 확인
        $table_check = $conn->query("SHOW TABLES LIKE 'security_logs'");
        if($table_check->num_rows == 0) {
            return true; // 테이블이 없으면 일단 허용
        }

        // 최근 5분간 실패 시도 횟수 확인
        $stmt = $conn->prepare("
            SELECT COUNT(*) as fail_count
            FROM security_logs
            WHERE ip_address = ?
            AND action IN ('invalid_token', 'token_validation_failed')
            AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ");
        $stmt->bind_param("s", $ip);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();

        if($data['fail_count'] > 10) {
            error_log("Suspicious activity detected from IP: " . $ip);
            return false;
        }

        return true;
    } catch(Exception $e) {
        error_log("Suspicious activity check error: " . $e->getMessage());
        return true; // 에러 시 일단 허용
    }
}

/**
 * 로그인 여부 확인
 * @return bool 로그인 여부
 */
function isLoggedIn() {
    // 세션 시작 확인
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // 세션에 userdata가 있으면 로그인 상태
    return isset($_SESSION['userdata']) && !empty($_SESSION['userdata']['id']);
}

/**
 * 관리자 권한 확인
 * @return bool 관리자 여부
 */
function isAdmin() {
    return isset($_SESSION['userdata']) && $_SESSION['userdata']['type'] == 1;
}

/**
 * 현재 사용자 정보 가져오기
 * @return array|null 사용자 정보
 */
function getCurrentUser() {
    if(isLoggedIn()) {
        return $_SESSION['userdata'];
    }
    return null;
}

/**
 * 경로 traversal 공격 방지
 * @param string $file_path 검증할 파일 경로
 * @param string $base_path 기본 경로
 * @return bool 안전한 경로인지 여부
 */
function isSecurePath($file_path, $base_path) {
    $real_file_path = realpath($file_path);
    $real_base_path = realpath($base_path);

    if($real_file_path === false || $real_base_path === false) {
        return false;
    }

    return strpos($real_file_path, $real_base_path) === 0;
}

/**
 * CSRF 토큰 생성
 * @return string CSRF 토큰
 */
function generateCSRFToken() {
    if(!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF 토큰 검증
 * @param string $token 검증할 토큰
 * @return bool 유효한 토큰인지 여부
 */
function validateCSRFToken($token) {
    if(!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
?>
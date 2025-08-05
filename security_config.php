<?php
// security_config.php
// 보안 관련 설정 상수 정의

// 개발/운영 모드
define('DEV_MODE', false);

// 파일 업로드 설정
define('MAX_FILE_SIZE', 10485760); // 10MB
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'zip', 'hwp', 'hwpx']);

// 보안 설정
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 300); // 5분
define('TOKEN_EXPIRY_DAYS', 7);
define('SESSION_TIMEOUT', 3600); // 1시간

// 로깅 설정
define('ENABLE_SECURITY_LOGGING', true);
define('LOG_FAILED_ATTEMPTS', true);
define('LOG_FILE_OPERATIONS', true);

// IP 제한 설정
define('ENABLE_IP_WHITELIST', false);
define('IP_WHITELIST', [
    // '192.168.1.0/24',
    // '10.0.0.0/8'
]);

// 파일 보안 설정
define('ENABLE_VIRUS_SCAN', false); // ClamAV 등 설치 시 true
define('QUARANTINE_PATH', base_app . 'uploads/quarantine/');

// CSRF 설정
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_LENGTH', 32);

// 암호화 설정
define('ENCRYPTION_KEY', 'your-secret-encryption-key-here'); // 변경 필요

// 업로드 경로 설정
define('UPLOAD_BASE_PATH', base_app . 'uploads/documents/');
define('TEMP_UPLOAD_PATH', base_app . 'uploads/temp/');

// Wasabi 설정 (환경변수로 관리 권장)
define('WASABI_USE_ENV', true); // true인 경우 환경변수 사용
if(WASABI_USE_ENV) {
    define('WASABI_ACCESS_KEY', getenv('WASABI_ACCESS_KEY'));
    define('WASABI_SECRET_KEY', getenv('WASABI_SECRET_KEY'));
    define('WASABI_BUCKET', getenv('WASABI_BUCKET'));
    define('WASABI_REGION', getenv('WASABI_REGION') ?: 'ap-northeast-1');
    define('WASABI_ENDPOINT', getenv('WASABI_ENDPOINT') ?: 'https://s3.ap-northeast-1.wasabisys.com');
}

// 보안 헤더 설정
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    if(!DEV_MODE) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// 입력 검증 헬퍼 함수
function sanitizeInput($input, $type = 'string') {
    switch($type) {
        case 'int':
            return filter_var($input, FILTER_VALIDATE_INT);
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL);
        case 'url':
            return filter_var($input, FILTER_VALIDATE_URL);
        case 'string':
        default:
            return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
}

// 파일명 검증 함수
function isValidFileName($filename) {
    // 위험한 문자 패턴
    $dangerous_chars = ['..', '/', '\\', '<', '>', ':', '"', '|', '?', '*', "\0"];

    foreach($dangerous_chars as $char) {
        if(strpos($filename, $char) !== false) {
            return false;
        }
    }

    // 파일명 길이 제한
    if(strlen($filename) > 255) {
        return false;
    }

    return true;
}

// 보안 로그 기록 함수
function securityLog($action, $details = [], $level = 'info') {
    if(!ENABLE_SECURITY_LOGGING) {
        return;
    }

    $log_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'level' => $level,
        'action' => $action,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'user_id' => $_SESSION['userdata']['id'] ?? null,
        'details' => $details
    ];

    $log_file = base_app . 'logs/security_' . date('Y-m-d') . '.log';
    $log_dir = dirname($log_file);

    if(!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    error_log(json_encode($log_data) . PHP_EOL, 3, $log_file);
}
?>
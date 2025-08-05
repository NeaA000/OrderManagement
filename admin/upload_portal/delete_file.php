<?php
// admin/upload_portal/delete_file.php
require_once('upload_init.php');  // 전용 초기화 파일 사용
require_once(base_app . 'classes/UploadHandler.php');

// auth_check.php가 있는지 확인하고 없으면 간단한 대체 함수 정의
$auth_check_file = __DIR__ . '/auth_check.php';
if(file_exists($auth_check_file)) {
    require_once($auth_check_file);
} else {
    // auth_check.php가 없을 경우 간단한 대체 함수
    function checkSuspiciousActivity($conn, $ip) { return true; }
    function validateUploadToken($conn, $token, $request_id) {
        $stmt = $conn->prepare("SELECT * FROM document_requests WHERE upload_token = ? AND id = ?");
        $stmt->bind_param("si", $token, $request_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return ['valid' => $result->num_rows > 0, 'msg' => '유효하지 않은 토큰입니다.'];
    }
    function logFailedAttempt($conn, $action, $token, $ip) {}
}

header('Content-Type: application/json');

// 클라이언트 IP 정의
$client_ip = $_SERVER['REMOTE_ADDR'] ?? '';

// POST 메소드 확인
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'msg' => '잘못된 요청 방식입니다.']);
    exit;
}

// 의심 활동 체크
if(!checkSuspiciousActivity($conn, $client_ip)) {
    echo json_encode(['status' => 'error', 'msg' => '비정상적인 활동이 감지되었습니다.']);
    exit;
}

// POST 데이터 확인
if(!isset($_POST['document_id'])) {
    echo json_encode(['status' => 'error', 'msg' => '문서 ID가 없습니다.']);
    exit;
}

// 토큰 검증 추가 (보안 강화)
if(!isset($_POST['token']) || empty($_POST['token'])) {
    logFailedAttempt($conn, 'missing_token_delete', '', $client_ip);
    echo json_encode(['status' => 'error', 'msg' => '인증 토큰이 없습니다.']);
    exit;
}

// 입력값 검증
$document_id = filter_var($_POST['document_id'], FILTER_VALIDATE_INT);
$token = filter_var($_POST['token'], FILTER_SANITIZE_STRING);

if($document_id === false) {
    logFailedAttempt($conn, 'invalid_document_id', $token, $client_ip);
    echo json_encode(['status' => 'error', 'msg' => '잘못된 문서 ID입니다.']);
    exit;
}

// 문서 정보 및 Wasabi 정보 확인 (prepared statement 사용)
$stmt = $conn->prepare("
   SELECT rd.*, dr.upload_token, dr.status as request_status,
          uf.wasabi_key, uf.wasabi_bucket
   FROM `request_documents` rd 
   LEFT JOIN `document_requests` dr ON rd.request_id = dr.id 
   LEFT JOIN `uploaded_files` uf ON uf.document_id = rd.id
   WHERE rd.id = ?
");

if(!$stmt) {
    error_log("Database prepare error: " . $conn->error);
    echo json_encode(['status' => 'error', 'msg' => '데이터베이스 오류가 발생했습니다.']);
    exit;
}

$stmt->bind_param("i", $document_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows <= 0) {
    $stmt->close();
    logFailedAttempt($conn, 'invalid_document_delete', $token, $client_ip);
    echo json_encode(['status' => 'error', 'msg' => '유효하지 않은 요청입니다.']);
    exit;
}

$doc_info = $result->fetch_assoc();
$stmt->close();

// 토큰 검증 (보안 강화)
$validation = validateUploadToken($conn, $token, $doc_info['request_id']);
if(!$validation['valid']) {
    logFailedAttempt($conn, 'invalid_token_delete', $token, $client_ip);
    echo json_encode(['status' => 'error', 'msg' => $validation['msg']]);
    exit;
}

// 완료된 요청은 삭제 불가 (보안 강화)
if($doc_info['request_status'] == 2) {
    echo json_encode(['status' => 'error', 'msg' => '완료된 요청의 파일은 삭제할 수 없습니다.']);
    exit;
}

// 파일이 업로드되지 않은 경우
if($doc_info['status'] != 1) {
    echo json_encode(['status' => 'error', 'msg' => '삭제할 파일이 없습니다.']);
    exit;
}

// 삭제 처리 - UploadHandler 클래스가 Wasabi/로컬 모두 처리
try {
    $uploadHandler = new UploadHandler();
    $result = $uploadHandler->deleteDocument($document_id);
} catch (Exception $e) {
    error_log("Delete error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'msg' => '파일 삭제 중 오류가 발생했습니다.']);
    exit;
}

// 삭제 성공 시 추가 작업
if($result['status'] == 'success') {
    // 삭제 로그를 남김
    $log_stmt = $conn->prepare("
       INSERT INTO `upload_logs` 
       (request_id, document_id, action, file_name, ip_address, user_agent) 
       VALUES (?, ?, 'delete', ?, ?, ?)
   ");

    if($log_stmt) {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $log_stmt->bind_param("iisss",
            $doc_info['request_id'],
            $document_id,
            $doc_info['file_name'],
            $client_ip,
            $user_agent
        );
        $log_stmt->execute();
        $log_stmt->close();
    }

    error_log("File deleted - Document ID: {$document_id}, File: {$doc_info['file_name']}, IP: {$client_ip}");
}

echo json_encode($result);
?>
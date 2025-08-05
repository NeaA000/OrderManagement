<?php
// admin/upload_portal/complete_submission.php
require_once('upload_init.php');  // 전용 초기화 파일 사용
require_once('auth_check.php');   // 보안 인증 모듈 추가

header('Content-Type: application/json');

// IP 기반 의심스러운 활동 체크
$client_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
if(!checkSuspiciousActivity($conn, $client_ip)) {
    echo json_encode(['status' => 'error', 'msg' => '너무 많은 시도가 감지되었습니다.']);
    exit;
}

// POST 데이터 확인
if(!isset($_POST['request_id']) || !isset($_POST['token'])) {
    echo json_encode(['status' => 'error', 'msg' => '필수 정보가 누락되었습니다.']);
    exit;
}

$request_id = filter_var($_POST['request_id'], FILTER_VALIDATE_INT);
$token = filter_var($_POST['token'], FILTER_SANITIZE_STRING);

if($request_id === false) {
    echo json_encode(['status' => 'error', 'msg' => '잘못된 요청 ID입니다.']);
    exit;
}

// 토큰 검증 (validateUploadToken 함수 사용)
$validation = validateUploadToken($conn, $token, $request_id);
if(!$validation['valid']) {
    logFailedAttempt($conn, 'invalid_token_complete', $token, $client_ip);
    echo json_encode(['status' => 'error', 'msg' => $validation['msg']]);
    exit;
}

$request = $validation['data'];

// 업체명 추가 조회 (알림용)
$supplier_query = $conn->prepare("
    SELECT s.name as supplier_name 
    FROM `document_requests` dr 
    LEFT JOIN `supplier_list` s ON dr.supplier_id = s.id 
    WHERE dr.id = ?
");
$supplier_query->bind_param("i", $request_id);
$supplier_query->execute();
$supplier_result = $supplier_query->get_result();
$supplier_data = $supplier_result->fetch_assoc();
$request['supplier_name'] = $supplier_data['supplier_name'] ?? 'Unknown';
$supplier_query->close();

// 이미 완료된 요청인지 확인
if($request['status'] == 2) {
    echo json_encode(['status' => 'error', 'msg' => '이미 제출이 완료된 요청입니다.']);
    exit;
}

// 모든 필수 서류가 제출되었는지 확인
$check_stmt = $conn->prepare("
    SELECT COUNT(*) as total,
           SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as submitted
    FROM `request_documents` 
    WHERE request_id = ? AND is_required = 1
");
$check_stmt->bind_param("i", $request_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$doc_status = $check_result->fetch_assoc();
$check_stmt->close();

if($doc_status['total'] != $doc_status['submitted']) {
    echo json_encode(['status' => 'error', 'msg' => '아직 제출되지 않은 필수 서류가 있습니다.']);
    exit;
}

// 트랜잭션 시작
$conn->begin_transaction();

try {
    // 요청 상태를 완료(2)로 업데이트
    $update_stmt = $conn->prepare("
        UPDATE `document_requests` 
        SET status = 2, 
            completed_at = NOW() 
        WHERE id = ?
    ");
    $update_stmt->bind_param("i", $request_id);
    $update_request = $update_stmt->execute();
    $update_stmt->close();

    if(!$update_request) {
        throw new Exception("요청 상태 업데이트 실패");
    }

    // 워크플로우 상태 업데이트
    $workflow_stmt = $conn->prepare("
        INSERT INTO `workflow_status` 
        (request_id, current_step, step_name, step_description, started_at, is_current) 
        VALUES (?, 'completed', '제출완료', '모든 필수 서류가 제출되어 완료 처리되었습니다.', NOW(), 1)
    ");
    $workflow_stmt->bind_param("i", $request_id);

    if($workflow_stmt->execute()) {
        $last_id = $conn->insert_id;
        // 기존 current 상태 해제
        $update_current = $conn->prepare("UPDATE workflow_status SET is_current = 0 WHERE request_id = ? AND id != ?");
        $update_current->bind_param("ii", $request_id, $last_id);
        $update_current->execute();
        $update_current->close();
    }
    $workflow_stmt->close();

    // 알림 생성 - notifications 테이블 사용
    $notification_message = "'{$request['project_name']}' 프로젝트의 모든 서류가 제출되었습니다. (업체: {$request['supplier_name']})";

    $notification_stmt = $conn->prepare("
        INSERT INTO `notifications` 
        (request_id, type, title, message, is_read, created_at) 
        VALUES (?, 'completion', '서류 제출 완료', ?, 0, NOW())
    ");
    $notification_stmt->bind_param("is", $request_id, $notification_message);

    if(!$notification_stmt->execute()) {
        // 알림 생성 실패는 로그에만 기록하고 진행
        error_log("알림 생성 실패: " . $conn->error);
    }
    $notification_stmt->close();

    // 업로드 로그 기록
    $log_stmt = $conn->prepare("
        INSERT INTO `upload_logs` 
        (request_id, document_id, action, ip_address, user_agent, created_at) 
        VALUES (?, 0, 'complete_submission', ?, ?, NOW())
    ");
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $log_stmt->bind_param("iss", $request_id, $client_ip, $user_agent);
    $log_stmt->execute();
    $log_stmt->close();

    // 트랜잭션 커밋
    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'msg' => '서류 제출이 완료되었습니다.'
    ]);

} catch(Exception $e) {
    // 트랜잭션 롤백
    $conn->rollback();

    echo json_encode([
        'status' => 'error',
        'msg' => '제출 처리 중 오류가 발생했습니다: ' . $e->getMessage()
    ]);
}
?>
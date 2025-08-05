<?php
// admin/upload_portal/complete_submission.php
require_once('upload_init.php');  // 전용 초기화 파일 사용

header('Content-Type: application/json');

// POST 데이터 확인
if(!isset($_POST['request_id']) || !isset($_POST['token'])) {
    echo json_encode(['status' => 'error', 'msg' => '필수 정보가 누락되었습니다.']);
    exit;
}

$request_id = $conn->real_escape_string($_POST['request_id']);
$token = $conn->real_escape_string($_POST['token']);

// 요청 정보 확인
$qry = $conn->query("
    SELECT dr.*, s.name as supplier_name 
    FROM `document_requests` dr 
    LEFT JOIN `supplier_list` s ON dr.supplier_id = s.id 
    WHERE dr.id = '{$request_id}' AND dr.upload_token = '{$token}'
");

if($qry->num_rows <= 0) {
    echo json_encode(['status' => 'error', 'msg' => '유효하지 않은 요청입니다.']);
    exit;
}

$request = $qry->fetch_assoc();

// 이미 완료된 요청인지 확인
if($request['status'] == 2) {
    echo json_encode(['status' => 'error', 'msg' => '이미 제출이 완료된 요청입니다.']);
    exit;
}

// 모든 필수 서류가 제출되었는지 확인
$check_docs = $conn->query("
    SELECT COUNT(*) as total,
           SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as submitted
    FROM `request_documents` 
    WHERE request_id = '{$request_id}' AND is_required = 1
");

$doc_status = $check_docs->fetch_assoc();

if($doc_status['total'] != $doc_status['submitted']) {
    echo json_encode(['status' => 'error', 'msg' => '아직 제출되지 않은 필수 서류가 있습니다.']);
    exit;
}

// 트랜잭션 시작
$conn->begin_transaction();

try {
    // 요청 상태를 완료(2)로 업데이트
    $update_request = $conn->query("
        UPDATE `document_requests` 
        SET status = 2, 
            completed_at = NOW() 
        WHERE id = '{$request_id}'
    ");

    if(!$update_request) {
        throw new Exception("요청 상태 업데이트 실패");
    }

    // 워크플로우 상태 업데이트
    $workflow_query = "
        INSERT INTO `workflow_status` 
        (request_id, current_step, step_name, step_description, started_at, is_current) 
        VALUES 
        ('{$request_id}', 'completed', '제출완료', 
         '모든 필수 서류가 제출되어 완료 처리되었습니다.', NOW(), 1)
    ";

    if($conn->query($workflow_query)) {
        // 기존 current 상태 해제
        $conn->query("UPDATE workflow_status SET is_current = 0 WHERE request_id = '{$request_id}' AND id != LAST_INSERT_ID()");
    }

    // 알림 생성 - notifications 테이블 사용
    $notification_message = "'{$request['project_name']}' 프로젝트의 모든 서류가 제출되었습니다. (업체: {$request['supplier_name']})";

    $notification_query = "
        INSERT INTO `notifications` 
        (request_id, type, title, message, is_read, created_at) 
        VALUES 
        ('{$request_id}', 'completion', 
         '서류 제출 완료', '{$notification_message}', 0, NOW())
    ";

    if(!$conn->query($notification_query)) {
        // 알림 생성 실패는 로그에만 기록하고 진행
        error_log("알림 생성 실패: " . $conn->error);
    }

    // 업로드 로그 기록
    $log_query = "
        INSERT INTO `upload_logs` 
        (request_id, document_id, action, ip_address, user_agent, created_at) 
        VALUES 
        ('{$request_id}', 0, 'complete_submission', 
         '{$_SERVER['REMOTE_ADDR']}', '{$_SERVER['HTTP_USER_AGENT']}', NOW())
    ";

    $conn->query($log_query);

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
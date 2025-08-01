<?php
// admin/document_requests/send_email.php
require_once('../../initialize.php');
require_once('../../classes/EmailSender.php');

// AJAX 요청 확인
if(!isset($_POST['request_id'])) {
    echo json_encode(['status' => 'error', 'msg' => '요청 ID가 없습니다.']);
    exit;
}

$request_id = $conn->real_escape_string($_POST['request_id']);

// 이메일 전송
$emailSender = new EmailSender();
$result = $emailSender->sendDocumentRequest($request_id);

// 결과 반환
echo json_encode($result);
?>
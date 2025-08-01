<?php
// upload_portal/upload_handler.php
require_once('../initialize.php');
require_once('../classes/UploadHandler.php');

header('Content-Type: application/json');

// POST 데이터 확인
if(!isset($_POST['request_id']) || !isset($_POST['document_id'])) {
    echo json_encode(['status' => 'error', 'msg' => '필수 정보가 누락되었습니다.']);
    exit;
}

// 파일 확인
if(!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'msg' => '파일이 전송되지 않았습니다.']);
    exit;
}

$request_id = $conn->real_escape_string($_POST['request_id']);
$document_id = $conn->real_escape_string($_POST['document_id']);

// 문서 정보 확인
$doc_check = $conn->query("
    SELECT rd.*, dr.upload_token 
    FROM `request_documents` rd 
    LEFT JOIN `document_requests` dr ON rd.request_id = dr.id 
    WHERE rd.id = '{$document_id}' AND rd.request_id = '{$request_id}'
");

if($doc_check->num_rows <= 0) {
    echo json_encode(['status' => 'error', 'msg' => '유효하지 않은 요청입니다.']);
    exit;
}

// 업로드 처리
$uploadHandler = new UploadHandler();
$result = $uploadHandler->uploadDocument($_FILES['file'], $request_id, $document_id);

echo json_encode($result);
?>
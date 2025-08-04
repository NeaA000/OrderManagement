<?php
// admin/upload_portal/delete_file.php
require_once('upload_init.php');  // 전용 초기화 파일 사용
require_once(base_app . 'classes/UploadHandler.php');

header('Content-Type: application/json');

if(!isset($_POST['document_id'])) {
    echo json_encode(['status' => 'error', 'msg' => '문서 ID가 없습니다.']);
    exit;
}

$document_id = $conn->real_escape_string($_POST['document_id']);

// 문서 정보 확인
$doc_check = $conn->query("
    SELECT rd.*, dr.upload_token 
    FROM `request_documents` rd 
    LEFT JOIN `document_requests` dr ON rd.request_id = dr.id 
    WHERE rd.id = '{$document_id}'
");

if($doc_check->num_rows <= 0) {
    echo json_encode(['status' => 'error', 'msg' => '유효하지 않은 요청입니다.']);
    exit;
}

// 삭제 처리
$uploadHandler = new UploadHandler();
$result = $uploadHandler->deleteDocument($document_id);

echo json_encode($result);
?>
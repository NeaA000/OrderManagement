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

// 문서 정보 및 Wasabi 정보 확인
$doc_check = $conn->query("
    SELECT rd.*, dr.upload_token, 
           uf.wasabi_key, uf.wasabi_bucket
    FROM `request_documents` rd 
    LEFT JOIN `document_requests` dr ON rd.request_id = dr.id 
    LEFT JOIN `uploaded_files` uf ON uf.document_id = rd.id
    WHERE rd.id = '{$document_id}'
");

if($doc_check->num_rows <= 0) {
    echo json_encode(['status' => 'error', 'msg' => '유효하지 않은 요청입니다.']);
    exit;
}

$doc_info = $doc_check->fetch_assoc();

// 삭제 권한 확인 (필요한 경우)
// 여기에 권한 확인 로직 추가 가능

// 삭제 처리 - UploadHandler 클래스가 Wasabi/로컬 모두 처리
$uploadHandler = new UploadHandler();
$result = $uploadHandler->deleteDocument($document_id);

// 삭제 성공 시 추가 작업
if($result['status'] == 'success') {
    // 삭제 로그를 남기거나 추가 작업 수행 가능
    error_log("File deleted - Document ID: {$document_id}, File: {$doc_info['file_name']}");
}

echo json_encode($result);
?>
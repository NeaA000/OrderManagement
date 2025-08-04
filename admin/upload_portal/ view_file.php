<?php
// admin/upload_portal/view_file.php
require_once('upload_init.php');  // 전용 초기화 파일 사용

if(!isset($_GET['id'])) {
    die('파일을 찾을 수 없습니다.');
}

$doc_id = $conn->real_escape_string($_GET['id']);

// 문서 정보 조회
$doc = $conn->query("
    SELECT rd.*, dr.upload_token 
    FROM `request_documents` rd 
    LEFT JOIN `document_requests` dr ON rd.request_id = dr.id 
    WHERE rd.id = '{$doc_id}'
")->fetch_assoc();

if(!$doc || empty($doc['file_path'])) {
    die('파일을 찾을 수 없습니다.');
}

$file_path = base_app . $doc['file_path'];

if(!file_exists($file_path)) {
    die('파일이 존재하지 않습니다.');
}

$file_ext = strtolower(pathinfo($doc['file_name'], PATHINFO_EXTENSION));
$file_size = filesize($file_path);

// MIME 타입 설정
$mime_types = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'zip' => 'application/zip',
    'hwp' => 'application/x-hwp',
    'hwpx' => 'application/vnd.hancom.hwpx'
];

$mime_type = $mime_types[$file_ext] ?? 'application/octet-stream';

// 미리보기 가능한 파일 타입
$preview_types = ['pdf', 'jpg', 'jpeg', 'png'];

if(isset($_GET['download']) || !in_array($file_ext, $preview_types)) {
    // 다운로드
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . $doc['file_name'] . '"');
    header('Content-Length: ' . $file_size);
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    readfile($file_path);
    exit;
} else {
    // 미리보기
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: inline; filename="' . $doc['file_name'] . '"');
    header('Content-Length: ' . $file_size);
    readfile($file_path);
    exit;
}
?>
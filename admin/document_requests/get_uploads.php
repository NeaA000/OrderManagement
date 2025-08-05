<?php
// admin/document_requests/get_uploads.php
require_once('../../config.php');

// 관리자 권한 확인
if(!isset($_SESSION['userdata']) || $_SESSION['userdata']['type'] != 1) {
    echo json_encode(['status' => 'error', 'msg' => '권한이 없습니다.']);
    exit;
}

if(!isset($_GET['document_id'])) {
    echo json_encode(['status' => 'error', 'msg' => '문서 ID가 없습니다.']);
    exit;
}

$document_id = intval($_GET['document_id']);

// 업로드된 파일 정보 조회 (uploaded_files 테이블 사용)
$stmt = $conn->prepare("
    SELECT 
        uf.*,
        rd.document_name,
        rd.request_id,
        dr.upload_token
    FROM uploaded_files uf
    LEFT JOIN request_documents rd ON uf.document_id = rd.id
    LEFT JOIN document_requests dr ON rd.request_id = dr.id
    WHERE uf.document_id = ?
    ORDER BY uf.uploaded_at DESC
");

if(!$stmt) {
    echo json_encode(['status' => 'error', 'msg' => '데이터베이스 오류']);
    exit;
}

$stmt->bind_param("i", $document_id);
$stmt->execute();
$result = $stmt->get_result();

$uploads = [];
while($row = $result->fetch_assoc()) {
    // ★ 직접 URL 노출 방지 - 다운로드 스크립트 경유
    $download_url = base_url . 'admin/upload_portal/download.php?id=' . $document_id;

    // 관리자는 토큰 없이도 접근 가능
    if(!isset($_SESSION['userdata']) || $_SESSION['userdata']['type'] != 1) {
        $download_url .= '&token=' . $row['upload_token'];
    }

    // 미리보기 URL (이미지인 경우)
    $preview_url = null;
    if(in_array(strtolower(pathinfo($row['original_name'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'])) {
        $preview_url = base_url . 'admin/upload_portal/view_file.php?id=' . $document_id;
    }

    $uploads[] = [
        'id' => $row['id'],
        'document_id' => $row['document_id'],
        'file_name' => $row['original_name'] ?: $row['stored_name'],
        'file_size' => formatFileSize($row['file_size']),
        'uploaded_at' => date('Y-m-d H:i:s', strtotime($row['uploaded_at'])),
        'uploaded_by' => $row['uploaded_by'],
        'download_url' => $download_url,
        'preview_url' => $preview_url,
        'storage' => $row['wasabi_key'] ? 'wasabi' : 'local'
    ];
}

$stmt->close();

// 파일 크기 포맷팅 함수
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// 결과 반환
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'data' => $uploads
]);
?>
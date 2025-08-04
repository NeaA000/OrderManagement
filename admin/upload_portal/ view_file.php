<?php
// admin/upload_portal/view_file.php
require_once('upload_init.php');  // 전용 초기화 파일 사용
require_once(base_app . 'classes/UploadHandler.php');

if(!isset($_GET['id'])) {
    die('파일을 찾을 수 없습니다.');
}

$doc_id = $conn->real_escape_string($_GET['id']);

// 문서 정보 및 Wasabi 정보 조회
$doc = $conn->query("
    SELECT rd.*, dr.upload_token, 
           uf.wasabi_key, uf.wasabi_bucket, uf.wasabi_url,
           uf.original_name, uf.mime_type
    FROM `request_documents` rd 
    LEFT JOIN `document_requests` dr ON rd.request_id = dr.id 
    LEFT JOIN `uploaded_files` uf ON uf.document_id = rd.id
    WHERE rd.id = '{$doc_id}'
")->fetch_assoc();

if(!$doc || (empty($doc['file_path']) && empty($doc['wasabi_key']))) {
    die('파일을 찾을 수 없습니다.');
}

// Wasabi 사용 여부 확인
$use_wasabi = $_settings->info('use_wasabi') === 'true';

// 파일이 Wasabi에 있는 경우
if($use_wasabi && !empty($doc['wasabi_key'])) {
    try {
        // UploadHandler 인스턴스 생성
        $uploadHandler = new UploadHandler();

        // 임시 서명된 URL 생성 (1시간 유효)
        $signed_url = $uploadHandler->getWasabiFileUrl($doc['wasabi_key'], 3600);

        if($signed_url) {
            // 다운로드 강제 여부 확인
            if(isset($_GET['download'])) {
                // 다운로드를 위한 헤더 설정
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . ($doc['original_name'] ?: $doc['file_name']) . '"');
                header('Cache-Control: no-cache, must-revalidate');
                header('Pragma: no-cache');

                // Wasabi에서 파일 내용을 가져와서 출력
                $context = stream_context_create([
                    "http" => [
                        "header" => "User-Agent: CDMS/1.0\r\n"
                    ]
                ]);

                $file_content = file_get_contents($signed_url, false, $context);
                if($file_content !== false) {
                    echo $file_content;
                } else {
                    die('파일을 다운로드할 수 없습니다.');
                }
            } else {
                // 미리보기를 위해 서명된 URL로 리다이렉트
                header('Location: ' . $signed_url);
            }
            exit;
        } else {
            die('파일 URL을 생성할 수 없습니다.');
        }

    } catch(Exception $e) {
        error_log("Wasabi file view error: " . $e->getMessage());
        die('파일을 불러올 수 없습니다.');
    }
}
// 로컬 파일인 경우 (기존 코드)
else {
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
}
?>
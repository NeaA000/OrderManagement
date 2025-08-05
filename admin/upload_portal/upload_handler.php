<?php
// admin/upload_portal/upload_handler.php

// 에러 표시 설정 (디버깅용)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 모든 출력 버퍼 지우기
ob_clean();

// JSON 헤더는 나중에 설정
try {
    require_once('upload_init.php');
    require_once(base_app . 'classes/UploadHandler.php');
    require_once('auth_check.php');

    // 여기서부터 JSON 헤더 설정
    header('Content-Type: application/json');

    // 토큰 검증
    if(!isset($_POST['token']) || empty($_POST['token'])) {
        throw new Exception('인증 토큰이 없습니다.');
    }

    $token = $_POST['token'];
    $request_id = intval($_POST['request_id']);
    $validation = validateUploadToken($conn, $token, $request_id);

    if(!$validation['valid']) {
        throw new Exception($validation['msg']);
    }

    // 완료된 요청은 업로드 불가
    if($validation['data']['status'] == 2) {
        throw new Exception('이미 완료된 요청입니다.');
    }

    // 파일 업로드 처리
    if(!isset($_FILES['file'])) {
        throw new Exception('업로드할 파일이 없습니다.');
    }

    $document_id = intval($_POST['document_id']);

    // 문서명 가져오기
    $doc_stmt = $conn->prepare("SELECT document_name FROM request_documents WHERE id = ?");
    if(!$doc_stmt) {
        throw new Exception('DB 준비 실패: ' . $conn->error);
    }

    $doc_stmt->bind_param("i", $document_id);
    $doc_stmt->execute();
    $doc_result = $doc_stmt->get_result();
    $doc_data = $doc_result->fetch_assoc();
    $document_name = $doc_data['document_name'] ?? null;
    $doc_stmt->close();

    // UploadHandler 클래스 사용
    $uploadHandler = new UploadHandler();
    $result = $uploadHandler->uploadDocument($_FILES['file'], $request_id, $document_id, $document_name);

    echo json_encode($result);

} catch(Exception $e) {
    // 에러 발생 시 JSON 형식으로 반환
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'msg' => $e->getMessage(),
        'trace' => $e->getTraceAsString() // 디버깅용
    ]);
}
?>
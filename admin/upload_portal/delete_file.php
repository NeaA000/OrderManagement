<?php
// admin/upload_portal/delete_file.php

// 모든 에러를 Exception으로 변환
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// 치명적 에러 핸들러
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE)) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'msg' => 'Fatal error: ' . $error['message']
        ]);
    }
});

// 출력 버퍼링 시작
ob_start();

try {
    // 에러 설정
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);

    // 필수 파일 포함
    if (!file_exists('upload_init.php')) {
        throw new Exception('upload_init.php 파일을 찾을 수 없습니다.');
    }
    require_once('upload_init.php');

    if (!defined('base_app')) {
        throw new Exception('base_app이 정의되지 않았습니다.');
    }

    $uploadHandlerPath = base_app . 'classes/UploadHandler.php';
    if (!file_exists($uploadHandlerPath)) {
        throw new Exception('UploadHandler.php 파일을 찾을 수 없습니다: ' . $uploadHandlerPath);
    }
    require_once($uploadHandlerPath);

    // 데이터베이스 연결 확인
    if (!isset($conn) || !$conn) {
        throw new Exception('데이터베이스 연결이 없습니다.');
    }

    // auth_check.php 처리
    $auth_check_file = __DIR__ . '/auth_check.php';
    if(file_exists($auth_check_file)) {
        require_once($auth_check_file);
    } else {
        function checkSuspiciousActivity($conn, $ip) { return true; }
        function validateUploadToken($conn, $token, $request_id) {
            $stmt = $conn->prepare("SELECT * FROM document_requests WHERE upload_token = ? AND id = ?");
            if (!$stmt) {
                throw new Exception('토큰 검증 쿼리 준비 실패');
            }
            $stmt->bind_param("si", $token, $request_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $valid = $result->num_rows > 0;
            $stmt->close();
            return ['valid' => $valid, 'msg' => $valid ? '' : '유효하지 않은 토큰입니다.'];
        }
        function logFailedAttempt($conn, $action, $token, $ip) {}
    }

    // 출력 버퍼 정리
    while (ob_get_level()) {
        ob_end_clean();
    }

    // JSON 헤더
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache');

    // POST 확인
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('POST 요청이 아닙니다.');
    }

    // 필수 파라미터 확인
    if (!isset($_POST['document_id']) || !isset($_POST['token'])) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }

    // 파라미터 검증
    $document_id = filter_var($_POST['document_id'], FILTER_VALIDATE_INT);
    $token = trim($_POST['token']);

    if ($document_id === false) {
        throw new Exception('잘못된 문서 ID입니다.');
    }

    // 문서 정보 조회
    $sql = "SELECT rd.*, dr.upload_token, dr.status as request_status 
            FROM request_documents rd 
            LEFT JOIN document_requests dr ON rd.request_id = dr.id 
            WHERE rd.id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('쿼리 준비 실패: ' . $conn->error);
    }

    $stmt->bind_param("i", $document_id);
    if (!$stmt->execute()) {
        throw new Exception('쿼리 실행 실패: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($result->num_rows <= 0) {
        $stmt->close();
        throw new Exception('문서를 찾을 수 없습니다.');
    }

    $doc_info = $result->fetch_assoc();
    $stmt->close();

    // 토큰 검증
    if ($doc_info['upload_token'] !== $token) {
        throw new Exception('인증 토큰이 일치하지 않습니다.');
    }

    // 상태 확인
    if ($doc_info['request_status'] == 2) {
        throw new Exception('완료된 요청의 파일은 삭제할 수 없습니다.');
    }

    if ($doc_info['status'] != 1) {
        throw new Exception('삭제할 파일이 없습니다.');
    }

    // UploadHandler 클래스 확인
    if (!class_exists('UploadHandler')) {
        throw new Exception('UploadHandler 클래스를 찾을 수 없습니다.');
    }

    // 파일 삭제 처리
    $uploadHandler = new UploadHandler($conn);
    $result = $uploadHandler->deleteDocument($document_id);

    if ($result['status'] !== 'success') {
        throw new Exception($result['msg'] ?? '파일 삭제 실패');
    }

    // 로그 기록
    error_log("File deleted - Document ID: {$document_id}, File: {$doc_info['file_name']}");

    // 성공 응답
    echo json_encode($result);

} catch (Exception $e) {
    // 모든 출력 버퍼 정리
    while (ob_get_level()) {
        ob_end_clean();
    }

    // 에러 로깅
    error_log("Delete file error: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());

    // JSON 헤더 재설정
    header('Content-Type: application/json; charset=utf-8');

    // 에러 응답
    echo json_encode([
        'status' => 'error',
        'msg' => $e->getMessage()
    ]);
}

// 에러 핸들러 복원
restore_error_handler();
?>
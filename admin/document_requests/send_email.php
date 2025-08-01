<?php
// admin/document_requests/send_email.php

// 이 파일은 AJAX 백엔드이므로 HTML/JavaScript를 출력하지 않아야 함
// 에러 리포팅 비활성화 (운영 환경에서는 꼭 필요)
error_reporting(0);
ini_set('display_errors', 0);

// 버퍼 시작 (예상치 못한 출력 방지)
ob_start();

// 경로 설정
$base_path = dirname(dirname(dirname(__FILE__)));

// 필요한 파일 포함
require_once($base_path . '/config.php');

// 클래스 파일들 포함
if(!class_exists('DBConnection')) {
    require_once($base_path . '/classes/DBConnection.php');
}
if(!class_exists('SystemSettings')) {
    require_once($base_path . '/classes/SystemSettings.php');
}
if(!class_exists('EmailSender')) {
    require_once($base_path . '/classes/EmailSender.php');
}

// 전역 변수 설정
if(!isset($conn)) {
    $db = new DBConnection();
    $conn = $db->conn;
}
if(!isset($_settings)) {
    $_settings = new SystemSettings();
}

// 버퍼 내용 제거 (HTML 출력 방지)
ob_clean();

// JSON 헤더 설정
header('Content-Type: application/json');

// POST 데이터 확인
if(!isset($_POST['request_id']) || empty($_POST['request_id'])) {
    echo json_encode(['status' => 'error', 'msg' => '요청 ID가 없습니다.']);
    exit;
}

$request_id = intval($_POST['request_id']);

try {
    // 요청 ID 유효성 확인
    $check = $conn->query("SELECT id FROM document_requests WHERE id = '{$request_id}' LIMIT 1");
    if($check->num_rows == 0) {
        echo json_encode(['status' => 'error', 'msg' => '유효하지 않은 요청 ID입니다.']);
        exit;
    }

    // EmailSender 클래스 인스턴스 생성
    $emailSender = new EmailSender();

    // 이메일 발송
    $result = $emailSender->sendDocumentRequest($request_id);

    // 결과 반환
    echo json_encode($result);

} catch(Exception $e) {
    // 에러 발생 시
    echo json_encode([
        'status' => 'error',
        'msg' => '이메일 전송 중 오류가 발생했습니다: ' . $e->getMessage()
    ]);
}

// 스크립트 종료
exit;
?>
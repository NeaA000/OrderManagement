<?php
// admin/test_email.php
require_once('../config.php');
require_once('../classes/DBConnection.php');
require_once('../classes/SystemSettings.php');
require_once('../classes/EmailSender.php');

header('Content-Type: application/json');

if(!isset($_POST['email'])) {
    echo json_encode(['status' => 'error', 'msg' => '이메일 주소가 없습니다.']);
    exit;
}

$test_email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);

if(!$test_email) {
    echo json_encode(['status' => 'error', 'msg' => '유효하지 않은 이메일 주소입니다.']);
    exit;
}

// 테스트 이메일 내용
$subject = "[테스트] SMTP 설정 확인";
$body = "
<div style='font-family: \"Noto Sans KR\", sans-serif; max-width: 600px; margin: 0 auto;'>
    <div style='background-color: #f8f9fa; padding: 20px; border-radius: 10px;'>
        <h2 style='color: #333;'>SMTP 설정 테스트</h2>
        <p>이 메일을 받으셨다면 SMTP 설정이 올바르게 구성되었습니다.</p>
        
        <div style='background-color: #fff; padding: 20px; margin: 20px 0; border-radius: 5px;'>
            <h3>현재 설정 정보:</h3>
            <ul>
                <li>SMTP 호스트: " . $_settings->info('smtp_host') . "</li>
                <li>SMTP 포트: " . $_settings->info('smtp_port') . "</li>
                <li>보안 방식: " . $_settings->info('smtp_secure') . "</li>
                <li>발신자명: " . $_settings->info('smtp_from_name') . "</li>
                <li>발신 이메일: " . $_settings->info('smtp_from_email') . "</li>
            </ul>
        </div>
        
        <p style='color: #666; font-size: 14px;'>
            이 이메일은 " . $_settings->info('name') . " 시스템에서 발송되었습니다.
        </p>
    </div>
</div>
";

// 이메일 발송
$emailSender = new EmailSender();
$result = $emailSender->sendEmail($test_email, '관리자', $subject, $body);

echo json_encode($result);
?>
<?php
// classes/EmailSender.php

// 절대 경로로 설정
$current_dir = dirname(__FILE__);
$root_path = dirname($current_dir) . '/';

// 필요한 파일들 포함
if(file_exists($root_path . 'initialize.php')) {
    require_once($root_path . 'initialize.php');
} else {
    // initialize.php가 없으면 개별 파일들 포함
    if(!class_exists('DBConnection')) {
        require_once($root_path . 'classes/DBConnection.php');
    }
    if(!class_exists('SystemSettings')) {
        require_once($root_path . 'classes/SystemSettings.php');
    }
}

// PHPMailer 라이브러리
require_once(dirname(__FILE__) . '/PHPMailer/PHPMailer.php');
require_once(dirname(__FILE__) . '/PHPMailer/SMTP.php');
require_once(dirname(__FILE__) . '/PHPMailer/Exception.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailSender extends DBConnection {
    private $settings;

    function __construct() {
        global $_settings;
        if(!isset($_settings)) {
            $_settings = new SystemSettings();
        }
        $this->settings = $_settings;
        parent::__construct();
    }

    // SMTP 설정 가져오기
    private function getSMTPConfig() {
        $config = array();
        $config['host'] = $this->settings->info('smtp_host') ?? 'smtp.gmail.com';
        $config['username'] = $this->settings->info('smtp_username') ?? '';
        $config['password'] = $this->settings->info('smtp_password') ?? '';
        $config['port'] = $this->settings->info('smtp_port') ?? 587;
        $config['secure'] = $this->settings->info('smtp_secure') ?? 'tls';
        $config['from_email'] = $this->settings->info('smtp_from_email') ?? $config['username'];
        $config['from_name'] = $this->settings->info('smtp_from_name') ?? $this->settings->info('name');

        return $config;
    }

    // 서류 요청 이메일 전송
    public function sendDocumentRequest($request_id) {
        // 요청 정보 가져오기
        $request_qry = $this->conn->query("
            SELECT dr.*, sl.name as supplier_name, sl.contact_person, sl.email 
            FROM `document_requests` dr 
            LEFT JOIN `supplier_list` sl ON dr.supplier_id = sl.id 
            WHERE dr.id = '{$request_id}'
        ");

        if($request_qry->num_rows <= 0) {
            return ['status' => 'error', 'msg' => '요청 정보를 찾을 수 없습니다.'];
        }

        $request = $request_qry->fetch_assoc();

        // 요청된 서류 목록 가져오기
        $docs_qry = $this->conn->query("
            SELECT rd.*, dc.name as category_name 
            FROM `request_documents` rd 
            LEFT JOIN `document_categories` dc ON rd.category_id = dc.id 
            WHERE rd.request_id = '{$request_id}' 
            ORDER BY rd.is_required DESC, dc.name ASC
        ");

        $required_docs = [];
        $optional_docs = [];

        while($doc = $docs_qry->fetch_assoc()) {
            if($doc['is_required'] == 1) {
                $required_docs[] = $doc['document_name'];
            } else {
                $optional_docs[] = $doc['document_name'];
            }
        }

        // 업로드 링크 생성
        if(defined('base_url')) {
            // 정확한 경로로 수정
            $upload_link = base_url."admin/upload_portal/?token=".$request['upload_token'];
        } else {
            // base_url이 정의되지 않은 경우 동적으로 생성
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $path = dirname(dirname($_SERVER['SCRIPT_NAME']));
            $upload_link = $protocol . "://" . $host . $path . "/admin/upload_portal/?token=" . $request['upload_token'];
        }

        // DB에서 이메일 템플릿 가져오기
        $template_qry = $this->conn->query("
            SELECT * FROM email_templates 
            WHERE template_type = 'request_notification' 
            AND is_default = 1 
            LIMIT 1
        ");

        if($template_qry && $template_qry->num_rows > 0) {
            $template = $template_qry->fetch_assoc();
            $subject = $template['subject'];
            $email_body = $template['body_html'] ?? $template['content']; // body_html 또는 content 컬럼 사용

            // 템플릿이 비어있는지 확인
            if(empty($email_body)) {
                $subject = "[{company_name}] 서류 제출 요청 - {project_name}";
                $email_body = $this->getDefaultEmailTemplate();
            }
        } else {
            // 템플릿이 없으면 기본 템플릿 사용
            $subject = "[{company_name}] 서류 제출 요청 - {project_name}";
            $email_body = $this->getDefaultEmailTemplate();
        }

        // 변수 치환
        $variables = [
            '{supplier_name}' => $request['supplier_name'],
            '{contact_person}' => $request['contact_person'],
            '{company_name}' => $this->settings->info('name'),
            '{project_name}' => $request['project_name'],
            '{due_date}' => date('Y년 m월 d일', strtotime($request['due_date'])),
            '{upload_link}' => $upload_link,
            '{required_documents}' => $this->formatDocumentList($required_docs),
            '{optional_documents}' => empty($optional_docs) ? '<span style="color: #6c757d;">없음</span>' : $this->formatDocumentList($optional_docs),
            '{additional_notes}' => !empty($request['additional_notes']) ? nl2br(htmlspecialchars($request['additional_notes'])) : '<span style="color: #6c757d;">없음</span>',
            // 이메일 설정 페이지에서 사용하는 변수명도 지원
            '{{supplier_name}}' => $request['supplier_name'],
            '{{contact_person}}' => $request['contact_person'],
            '{{company_name}}' => $this->settings->info('name'),
            '{{project_name}}' => $request['project_name'],
            '{{due_date}}' => date('Y년 m월 d일', strtotime($request['due_date'])),
            '{{upload_link}}' => '<a href="'.$upload_link.'" style="color: #007bff;">'.$upload_link.'</a>',
            '{{document_list}}' => $this->formatDocumentList(array_merge($required_docs, $optional_docs))
        ];

        // 템플릿의 변수를 실제 값으로 치환
        foreach($variables as $key => $value) {
            $subject = str_replace($key, $value, $subject);
            $email_body = str_replace($key, $value, $email_body);
        }

        // 최종 이메일 본문이 비어있는지 확인
        if(empty(trim($email_body))) {
            return ['status' => 'error', 'msg' => '이메일 본문이 비어있습니다. 템플릿을 확인해주세요.'];
        }

        // 이메일 발송
        $result = $this->sendEmail(
            $request['email'],
            $request['contact_person'],
            $subject,
            $email_body
        );

        if($result['status'] == 'success') {
            // 이메일 전송 시간 업데이트
            $this->conn->query("UPDATE `document_requests` SET email_sent_at = NOW(), status = 1 WHERE id = '{$request_id}'");
        }

        return $result;
    }

    // 서류 목록 포맷팅
    private function formatDocumentList($docs) {
        if(empty($docs)) {
            return '<span style="color: #6c757d;">없음</span>';
        }

        $html = '<ul style="margin: 10px 0; padding-left: 20px;">';
        foreach($docs as $doc) {
            $html .= '<li style="margin: 5px 0;">' . htmlspecialchars($doc) . '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    // 기본 이메일 템플릿
    private function getDefaultEmailTemplate() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: "Noto Sans KR", sans-serif; }
        .container { max-width: 600px; margin: 0 auto; }
        .header { background-color: #f8f9fa; padding: 20px; border-radius: 10px; }
        .content { background-color: #fff; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .button { background-color: #007bff; color: white; padding: 10px 30px; text-decoration: none; border-radius: 5px; display: inline-block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>서류 제출 요청</h2>
            <p>안녕하세요, {contact_person}님</p>
            <p>{company_name}에서 서류 제출을 요청드립니다.</p>
            
            <div class="content">
                <h3>프로젝트 정보</h3>
                <p><strong>프로젝트명:</strong> {project_name}</p>
                <p><strong>제출 기한:</strong> <span style="color: #dc3545; font-weight: bold;">{due_date}</span></p>
                
                <h3>필수 제출 서류</h3>
                {required_documents}
                
                <h3>선택 제출 서류</h3>
                {optional_documents}
                
                <h3>추가 요청사항</h3>
                <p>{additional_notes}</p>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{upload_link}" class="button">서류 업로드하기</a>
            </div>
            
            <p style="color: #666; font-size: 14px;">
                이 링크는 보안을 위해 제출 기한까지만 유효합니다.<br>
                문의사항이 있으시면 회신 부탁드립니다.
            </p>
        </div>
    </div>
</body>
</html>';
    }

    // 기본 이메일 전송 함수
    public function sendEmail($to_email, $to_name, $subject, $body, $attachments = []) {
        $mail = new PHPMailer(true);
        $config = $this->getSMTPConfig();

        try {
            // 디버그 모드 (개발 환경에서만)
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER;

            // 서버 설정
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            $mail->SMTPSecure = $config['secure'];
            $mail->Port = $config['port'];
            $mail->CharSet = 'UTF-8';

            // 발신자/수신자 설정
            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($to_email, $to_name);

            // 내용 설정
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);

            // 첨부파일 추가
            if(!empty($attachments)) {
                foreach($attachments as $attachment) {
                    if(is_array($attachment) && isset($attachment['path']) && file_exists($attachment['path'])) {
                        $name = isset($attachment['name']) ? $attachment['name'] : basename($attachment['path']);
                        $mail->addAttachment($attachment['path'], $name);
                    } elseif(is_string($attachment) && file_exists($attachment)) {
                        $mail->addAttachment($attachment);
                    }
                }
            }

            $mail->send();
            return ['status' => 'success', 'msg' => '이메일이 성공적으로 전송되었습니다.'];

        } catch (Exception $e) {
            // 상세한 에러 메시지 (개발 환경에서만 사용)
            $error_msg = "이메일 전송 실패: {$mail->ErrorInfo}";

            // 운영 환경에서는 간단한 메시지로
            // $error_msg = "이메일 전송에 실패했습니다. 잠시 후 다시 시도해주세요.";

            return ['status' => 'error', 'msg' => $error_msg];
        }
    }

    // 리마인더 이메일 전송
    public function sendReminder($request_id) {
        // 미제출 서류가 있는 요청만 리마인더 전송
        $check = $this->conn->query("
            SELECT COUNT(*) as pending 
            FROM `request_documents` 
            WHERE request_id = '{$request_id}' AND status = 0
        ")->fetch_assoc();

        if($check['pending'] == 0) {
            return ['status' => 'info', 'msg' => '모든 서류가 제출되었습니다.'];
        }

        // 요청 정보와 함께 리마인더 전송
        return $this->sendDocumentRequest($request_id);
    }

    // 일괄 리마인더 전송 (크론잡용)
    public function sendBulkReminders() {
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        // 내일이 마감인 미완료 요청 찾기
        $requests = $this->conn->query("
            SELECT dr.id 
            FROM `document_requests` dr 
            WHERE dr.due_date = '{$tomorrow}' 
            AND dr.status != 2 
            AND EXISTS (
                SELECT 1 FROM `request_documents` rd 
                WHERE rd.request_id = dr.id AND rd.status = 0
            )
        ");

        $sent = 0;
        $failed = 0;

        while($row = $requests->fetch_assoc()) {
            $result = $this->sendReminder($row['id']);
            if($result['status'] == 'success') {
                $sent++;
            } else {
                $failed++;
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    // 테스트 이메일 전송 (시스템 설정 확인용)
    public function sendTestEmail($to_email) {
        $subject = "[테스트] SMTP 설정 확인";
        $body = "
        <div style='font-family: \"Noto Sans KR\", sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background-color: #f8f9fa; padding: 20px; border-radius: 10px;'>
                <h2 style='color: #333;'>SMTP 설정 테스트</h2>
                <p>이 메일을 받으셨다면 SMTP 설정이 올바르게 구성되었습니다.</p>
                
                <div style='background-color: #fff; padding: 20px; margin: 20px 0; border-radius: 5px;'>
                    <h3>현재 설정 정보:</h3>
                    <ul>
                        <li>SMTP 호스트: " . $this->settings->info('smtp_host') . "</li>
                        <li>SMTP 포트: " . $this->settings->info('smtp_port') . "</li>
                        <li>보안 방식: " . $this->settings->info('smtp_secure') . "</li>
                        <li>발신자명: " . $this->settings->info('smtp_from_name') . "</li>
                        <li>발신 이메일: " . $this->settings->info('smtp_from_email') . "</li>
                    </ul>
                </div>
                
                <p style='color: #666; font-size: 14px;'>
                    이 이메일은 " . $this->settings->info('name') . " 시스템에서 발송되었습니다.
                </p>
            </div>
        </div>
        ";

        return $this->sendEmail($to_email, '관리자', $subject, $body);
    }
}
?>
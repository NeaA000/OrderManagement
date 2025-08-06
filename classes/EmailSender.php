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

        // 이메일 호환 업로드 버튼 HTML (테이블 기반)
        $upload_button = '<table cellpadding="0" cellspacing="0" border="0" align="center">
            <tr>
                <td align="center" bgcolor="#007bff" style="border-radius: 5px;">
                    <a href="'.$upload_link.'" target="_blank" style="display: inline-block; padding: 12px 30px; font-family: \'Noto Sans KR\', sans-serif; font-size: 16px; color: #ffffff; text-decoration: none; border-radius: 5px;">서류 업로드하기</a>
                </td>
            </tr>
        </table>';

        // 전체 서류 목록 생성 (document_list용)
        $all_docs = array();
        foreach($required_docs as $doc) {
            $all_docs[] = $doc . ' (필수)';
        }
        foreach($optional_docs as $doc) {
            $all_docs[] = $doc . ' (선택)';
        }

        // 변수 치환
        $variables = [
            // 중괄호 두 개 형태 (우선 처리)
            '{{supplier_name}}' => $request['supplier_name'],
            '{{contact_person}}' => $request['contact_person'],
            '{{company_name}}' => $this->settings->info('name'),
            '{{project_name}}' => $request['project_name'],
            '{{due_date}}' => date('Y년 m월 d일', strtotime($request['due_date'])),
            '{{upload_link}}' => $upload_button,
            '{{document_list}}' => $this->formatDocumentListForEmail($all_docs),
            '{{required_documents}}' => $this->formatDocumentListForEmail($required_docs),
            '{{optional_documents}}' => empty($optional_docs) ? '<span style="color: #6c757d;">없음</span>' : $this->formatDocumentListForEmail($optional_docs),
            '{{additional_notes}}' => !empty($request['additional_notes']) ? nl2br(htmlspecialchars($request['additional_notes'])) : '<span style="color: #6c757d;">없음</span>'
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

    // 이메일 호환 서류 목록 포맷팅 (테이블 기반)
    private function formatDocumentListForEmail($docs) {
        if(empty($docs)) {
            return '<span style="color: #6c757d;">없음</span>';
        }

        $html = '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
        foreach($docs as $doc) {
            $html .= '<tr><td style="padding: 5px 0; color: #333; font-size: 14px;">• ' . htmlspecialchars($doc) . '</td></tr>';
        }
        $html .= '</table>';

        return $html;
    }

    // 서류 목록 포맷팅 (일반 용도)
    private function formatDocumentList($docs) {
        if(empty($docs)) {
            return '<span style="color: #6c757d;">없음</span>';
        }

        $html = '<ul style="margin: 10px 0; padding-left: 20px; list-style-type: disc;">';
        foreach($docs as $doc) {
            $html .= '<li style="margin: 5px 0; line-height: 1.6;">' . htmlspecialchars($doc) . '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    // 기본 이메일 템플릿 (테이블 기반 레이아웃)
    private function getDefaultEmailTemplate() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>서류 제출 요청</title>
</head>
<body style="margin: 0; padding: 0; font-family: \'Noto Sans KR\', \'Malgun Gothic\', \'맑은 고딕\', sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f4f4f4">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <table width="600" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="border: 1px solid #e0e0e0;">
                    <!-- 헤더 -->
                    <tr>
                        <td bgcolor="#f8f9fa" style="padding: 30px 40px; border-bottom: 1px solid #e0e0e0;">
                            <h2 style="margin: 0; color: #333; font-size: 24px;">서류 제출 요청</h2>
                        </td>
                    </tr>
                    
                    <!-- 인사말 -->
                    <tr>
                        <td style="padding: 30px 40px;">
                            <p style="margin: 0 0 15px 0; color: #333; font-size: 16px; line-height: 1.6;">
                                안녕하세요, {{contact_person}}님
                            </p>
                            <p style="margin: 0 0 25px 0; color: #333; font-size: 16px; line-height: 1.6;">
                                {{company_name}}에서 {{project_name}} 프로젝트와 관련하여 서류 제출을 요청드립니다.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- 프로젝트 정보 -->
                    <tr>
                        <td style="padding: 0 40px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f8f9fa" style="border: 1px solid #e0e0e0;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <h3 style="margin: 0 0 15px 0; color: #333; font-size: 18px;">프로젝트 정보</h3>
                                        <table width="100%" cellpadding="5" cellspacing="0" border="0">
                                            <tr>
                                                <td width="30%" style="color: #666; font-size: 14px;"><strong>프로젝트명:</strong></td>
                                                <td style="color: #333; font-size: 14px;">{{project_name}}</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666; font-size: 14px;"><strong>제출 기한:</strong></td>
                                                <td style="color: #dc3545; font-size: 14px; font-weight: bold;">{{due_date}}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- 서류 목록 -->
                    <tr>
                        <td style="padding: 30px 40px;">
                            <h3 style="margin: 0 0 15px 0; color: #333; font-size: 18px;">필수 제출 서류</h3>
                            {{required_documents}}
                            
                            <h3 style="margin: 25px 0 15px 0; color: #333; font-size: 18px;">선택 제출 서류</h3>
                            {{optional_documents}}
                            
                            <h3 style="margin: 25px 0 15px 0; color: #333; font-size: 18px;">추가 요청사항</h3>
                            <p style="margin: 0; color: #333; font-size: 14px; line-height: 1.6;">{{additional_notes}}</p>
                        </td>
                    </tr>
                    
                    <!-- 업로드 버튼 -->
                    <tr>
                        <td align="center" style="padding: 30px 40px;">
                            {{upload_link}}
                        </td>
                    </tr>
                    
                    <!-- 푸터 -->
                    <tr>
                        <td bgcolor="#f8f9fa" style="padding: 30px 40px; border-top: 1px solid #e0e0e0;">
                            <p style="margin: 0 0 10px 0; color: #666; font-size: 14px; line-height: 1.6;">
                                이 링크는 보안을 위해 제출 기한까지만 유효합니다.
                            </p>
                            <p style="margin: 0; color: #666; font-size: 14px; line-height: 1.6;">
                                문의사항이 있으시면 회신 부탁드립니다.
                            </p>
                            <p style="margin: 15px 0 0 0; color: #333; font-size: 14px;">
                                감사합니다.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
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
        $body = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMTP 테스트</title>
</head>
<body style="margin: 0; padding: 0; font-family: \'Noto Sans KR\', \'Malgun Gothic\', \'맑은 고딕\', sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f4f4f4">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <table width="600" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="border: 1px solid #e0e0e0;">
                    <tr>
                        <td bgcolor="#f8f9fa" style="padding: 30px 40px; border-bottom: 1px solid #e0e0e0;">
                            <h2 style="margin: 0; color: #333; font-size: 24px;">SMTP 설정 테스트</h2>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 30px 40px;">
                            <p style="margin: 0 0 20px 0; color: #333; font-size: 16px;">
                                이 메일을 받으셨다면 SMTP 설정이 올바르게 구성되었습니다.
                            </p>
                            
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f8f9fa" style="border: 1px solid #e0e0e0;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <h3 style="margin: 0 0 15px 0; color: #333; font-size: 18px;">현재 설정 정보</h3>
                                        <table width="100%" cellpadding="5" cellspacing="0" border="0">
                                            <tr>
                                                <td width="40%" style="color: #666; font-size: 14px;"><strong>SMTP 호스트:</strong></td>
                                                <td style="color: #333; font-size: 14px;">' . $this->settings->info('smtp_host') . '</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666; font-size: 14px;"><strong>SMTP 포트:</strong></td>
                                                <td style="color: #333; font-size: 14px;">' . $this->settings->info('smtp_port') . '</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666; font-size: 14px;"><strong>보안 방식:</strong></td>
                                                <td style="color: #333; font-size: 14px;">' . $this->settings->info('smtp_secure') . '</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666; font-size: 14px;"><strong>발신자명:</strong></td>
                                                <td style="color: #333; font-size: 14px;">' . $this->settings->info('smtp_from_name') . '</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666; font-size: 14px;"><strong>발신 이메일:</strong></td>
                                                <td style="color: #333; font-size: 14px;">' . $this->settings->info('smtp_from_email') . '</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td bgcolor="#f8f9fa" style="padding: 20px 40px; border-top: 1px solid #e0e0e0;">
                            <p style="margin: 0; color: #666; font-size: 14px; text-align: center;">
                                이 이메일은 ' . $this->settings->info('name') . ' 시스템에서 발송되었습니다.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

        return $this->sendEmail($to_email, '관리자', $subject, $body);
    }
}
?>
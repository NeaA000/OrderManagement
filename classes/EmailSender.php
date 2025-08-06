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

    // 서류 요청 이메일 전송 - 통합 버전
    public function sendDocumentRequest($request_id) {
        // 요청 정보 가져오기
        $request_qry = $this->conn->query("
            SELECT dr.*, sl.name as supplier_name, sl.contact_person, sl.email,
                   drd.manager_email, drd.manager_name
            FROM `document_requests` dr 
            LEFT JOIN `supplier_list` sl ON dr.supplier_id = sl.id 
            LEFT JOIN `document_request_details` drd ON drd.request_id = dr.id
            WHERE dr.id = '{$request_id}'
        ");

        if($request_qry->num_rows <= 0) {
            return ['status' => 'error', 'msg' => '요청 정보를 찾을 수 없습니다.'];
        }

        $request = $request_qry->fetch_assoc();

        // 수신자 정보 결정
        $to_email = !empty($request['manager_email']) ? $request['manager_email'] : $request['email'];
        $to_name = !empty($request['manager_name']) ? $request['manager_name'] : $request['contact_person'];

        if(empty($to_email)) {
            return ['status' => 'error', 'msg' => '수신자 이메일이 없습니다.'];
        }

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

        // 템플릿 가져오기 (우선순위: DB → 기본 템플릿)
        $template = $this->getEmailTemplate('request_notification');

        // 변수 준비
        $variables = $this->prepareTemplateVariables($request, $required_docs, $optional_docs);

        // 템플릿 변수 치환
        $subject = $this->replaceTemplateVariables($template['subject'], $variables);
        $body = $this->replaceTemplateVariables($template['content'], $variables);
        
        // MSO 조건부 주석 복원 (Outlook 호환성)
        $body = $this->restoreMsoComments($body);

        // HTML 구조 보장
        $body = $this->ensureHTMLStructure($body);

        // 이메일 발송
        $result = $this->sendEmail($to_email, $to_name, $subject, $body);

        if($result['status'] == 'success') {
            // 이메일 전송 시간 업데이트
            $this->conn->query("UPDATE `document_requests` SET email_sent_at = NOW(), status = 1 WHERE id = '{$request_id}'");
        }

        return $result;
    }

    // 템플릿 가져오기 (DB 우선, 없으면 기본값)
    private function getEmailTemplate($template_type) {
        // DB에서 템플릿 조회
        $template_qry = $this->conn->query("
            SELECT * FROM email_templates 
            WHERE template_type = '{$template_type}' 
            AND is_default = 1 
            AND status = 1
            LIMIT 1
        ");

        if($template_qry && $template_qry->num_rows > 0) {
            $template = $template_qry->fetch_assoc();

            // content가 비어있으면 기본 템플릿 사용
            if(empty(trim($template['content']))) {
                return [
                    'subject' => $template['subject'] ?? $this->getDefaultSubject($template_type),
                    'content' => $this->getDefaultEmailTemplate($template_type)
                ];
            }

            return [
                'subject' => $template['subject'],
                'content' => $template['content']
            ];
        }

        // DB에 템플릿이 없으면 기본값 반환
        return [
            'subject' => $this->getDefaultSubject($template_type),
            'content' => $this->getDefaultEmailTemplate($template_type)
        ];
    }

    // 기본 제목
    private function getDefaultSubject($template_type) {
        switch($template_type) {
            case 'request_notification':
                return '[{{company_name}}] 서류 제출 요청 - {{project_name}}';
            case 'reminder':
                return '[리마인더] {{project_name}} 서류 제출 기한 임박';
            case 'completion':
                return '[{{company_name}}] {{project_name}} 서류 제출 완료';
            default:
                return '[{{company_name}}] 알림';
        }
    }

    // 템플릿 변수 준비
    private function prepareTemplateVariables($request, $required_docs = [], $optional_docs = []) {
        // 업로드 링크 생성
        $upload_link = $this->generateUploadLink($request['upload_token']);

        // 업로드 버튼 HTML 생성
        $upload_button_html = $this->generateUploadButton($upload_link);

        // 변수 배열
        $variables = [
            '{{contact_person}}' => $request['contact_person'] ?? '',
            '{{company_name}}' => $this->settings->info('name'),
            '{{supplier_name}}' => $request['supplier_name'] ?? '',
            '{{project_name}}' => $request['project_name'] ?? '',
            '{{due_date}}' => !empty($request['due_date']) ? date('Y년 m월 d일', strtotime($request['due_date'])) : '',
            '{{upload_link}}' => $upload_button_html,
            '{{upload_button}}' => $upload_button_html, // 별칭
            '{{required_documents}}' => $this->formatDocumentListForEmail($required_docs),
            '{{optional_documents}}' => $this->formatDocumentListForEmail($optional_docs),
            '{{document_list}}' => $this->formatAllDocumentsList($required_docs, $optional_docs),
            '{{additional_notes}}' => $this->formatAdditionalNotes($request['additional_notes'] ?? '')
        ];

        return $variables;
    }

    // 업로드 링크 생성
    private function generateUploadLink($token) {
        if(defined('base_url')) {
            return base_url . "admin/upload_portal/?token=" . $token;
        } else {
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $path = dirname(dirname($_SERVER['SCRIPT_NAME']));
            return $protocol . "://" . $host . $path . "/admin/upload_portal/?token=" . $token;
        }
    }

    // 업로드 버튼 HTML 생성 (이메일 호환)
    private function generateUploadButton($link) {
        return '<table cellpadding="0" cellspacing="0" border="0" width="100%">
            <tr>
                <td align="center" style="padding: 30px 0;">
                    <!--[if mso]>
                    <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" 
                        href="'.$link.'" 
                        style="height:40px;v-text-anchor:middle;width:200px;" 
                        arcsize="10%" stroke="f" fillcolor="#007bff">
                        <w:anchorlock/>
                        <center>
                    <![endif]-->
                    <a href="'.$link.'" 
                       style="background-color:#007bff;border-radius:4px;color:#ffffff;display:inline-block;font-family:Arial,sans-serif;font-size:16px;font-weight:bold;line-height:40px;text-align:center;text-decoration:none;width:200px;-webkit-text-size-adjust:none;">
                        서류 업로드하기
                    </a>
                    <!--[if mso]>
                        </center>
                    </v:roundrect>
                    <![endif]-->
                </td>
            </tr>
        </table>';
    }

    // 서류 목록 포맷팅 (이메일 호환)
    private function formatDocumentListForEmail($docs) {
        if(empty($docs)) {
            return '<span style="color: #666666;">없음</span>';
        }

        $html = '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
        foreach($docs as $doc) {
            $html .= '<tr><td style="padding: 5px 0; color: #333333; font-size: 14px;">• ' . htmlspecialchars($doc) . '</td></tr>';
        }
        $html .= '</table>';

        return $html;
    }

    // 전체 서류 목록 (필수/선택 표시)
    private function formatAllDocumentsList($required_docs, $optional_docs) {
        $html = '<table cellpadding="0" cellspacing="0" border="0" width="100%">';

        foreach($required_docs as $doc) {
            $html .= '<tr><td style="padding: 5px 0; color: #333333; font-size: 14px;">• ' . htmlspecialchars($doc) . ' <span style="color: #dc3545;">(필수)</span></td></tr>';
        }

        foreach($optional_docs as $doc) {
            $html .= '<tr><td style="padding: 5px 0; color: #333333; font-size: 14px;">• ' . htmlspecialchars($doc) . ' <span style="color: #6c757d;">(선택)</span></td></tr>';
        }

        if(empty($required_docs) && empty($optional_docs)) {
            $html .= '<tr><td style="padding: 5px 0; color: #666666; font-size: 14px;">서류 목록이 없습니다.</td></tr>';
        }

        $html .= '</table>';
        return $html;
    }

    // 추가 요청사항 포맷팅
    private function formatAdditionalNotes($notes) {
        if(empty($notes)) {
            return '<span style="color: #666666;">없음</span>';
        }
        return nl2br(htmlspecialchars($notes));
    }

    /**
     * MSO 조건부 주석 복원
     * HTML 엔티티로 변환된 MSO 주석을 원래대로 복원
     */
    private function restoreMsoComments($content) {
        // HTML 엔티티 디코딩
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // MSO 조건부 주석 패턴 복원
        $patterns = [
            // 인코딩된 MSO 시작 태그
            '/&lt;!--\[if mso\]&gt;/' => '<!--[if mso]>',
            '/&lt;!\[if mso\]&gt;/' => '<!--[if mso]>',
            
            // 인코딩된 MSO 종료 태그
            '/&lt;!\[endif\]--&gt;/' => '<![endif]-->',
            '/&lt;!--\[endif\]--&gt;/' => '<!--[endif]-->',
            
            // VML 관련 태그
            '/&lt;v:roundrect/' => '<v:roundrect',
            '/&lt;\/v:roundrect&gt;/' => '</v:roundrect>',
            '/&lt;w:anchorlock/' => '<w:anchorlock',
            '/&lt;\/center&gt;/' => '</center>',
            
            // XML 네임스페이스
            '/xmlns:v=&quot;/' => 'xmlns:v="',
            '/xmlns:w=&quot;/' => 'xmlns:w="',
            '/&quot;/' => '"'
        ];
        
        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }
        
        return $content;
    }

    // 템플릿 변수 치환
    private function replaceTemplateVariables($template, $variables) {
        // 디버깅을 위한 로그
        error_log("Template before replacement: " . substr($template, 0, 200));
        error_log("Variables: " . print_r(array_keys($variables), true));

        // 변수를 길이 순으로 정렬 (긴 것부터 치환)
        $sortedVariables = $variables;
        uksort($sortedVariables, function($a, $b) {
            return strlen($b) - strlen($a);
        });
        
        // 각 변수에 대해 치환 수행
        foreach($sortedVariables as $key => $value) {
            // 정규식을 사용하여 정확한 매칭
            $pattern = '/' . preg_quote($key, '/') . '/';
            $count = 0;
            $template = preg_replace($pattern, $value, $template, -1, $count);
            
            if($count > 0) {
                error_log("Replaced {$key} -> {$count} times");
            }
        }
        
        // 치환되지 않은 변수가 있는지 확인
        if(preg_match_all('/\{\{[^}]+\}\}/', $template, $matches)) {
            error_log("Unreplaced variables: " . print_r($matches[0], true));
        }

        error_log("Template after replacement: " . substr($template, 0, 200));

        return $template;
    }

    // HTML 구조 보장
    private function ensureHTMLStructure($content) {
        // DOCTYPE이 있는지 확인
        if(strpos($content, '<!DOCTYPE') !== false) {
            return $content;
        }

        // body 태그가 있는지 확인
        if(strpos($content, '<body') !== false) {
            return $content;
        }

        // 기본 HTML 구조로 감싸기 (이메일 호환성 향상)
        return '<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin: 0; padding: 0; font-family: Arial, \'Malgun Gothic\', \'맑은 고딕\', sans-serif; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center">
    ' . $content . '
            </td>
        </tr>
    </table>
</body>
</html>';
    }

    // 기본 이메일 템플릿 (완전 개선된 버전)
    private function getDefaultEmailTemplate($template_type = 'request_notification') {
        switch($template_type) {
            case 'request_notification':
                return $this->getRequestNotificationTemplate();
            case 'reminder':
                return $this->getReminderTemplate();
            case 'completion':
                return $this->getCompletionTemplate();
            default:
                return $this->getRequestNotificationTemplate();
        }
    }

    // 서류 요청 알림 템플릿
    private function getRequestNotificationTemplate() {
        return '<table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f4f4f4">
    <tr>
        <td align="center" style="padding: 20px 0;">
            <!--[if mso]><table width="600" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
            <table cellpadding="0" cellspacing="0" border="0" style="width: 100%; max-width: 600px;" bgcolor="#ffffff">
                <!-- 헤더 -->
                <tr>
                    <td bgcolor="#f8f9fa">
                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="padding: 30px 40px; border-bottom: 1px solid #e0e0e0;">
                                    <h2 style="margin: 0; color: #333333; font-size: 24px; font-weight: bold; font-family: Arial, \'Malgun Gothic\', sans-serif;">서류 제출 요청</h2>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                
                <!-- 인사말 -->
                <tr>
                    <td>
                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="padding: 30px 40px;">
                                    <p style="margin: 0 0 15px 0; color: #333333; font-size: 16px; line-height: 24px; font-family: Arial, \'Malgun Gothic\', sans-serif;">
                                        안녕하세요, {{contact_person}}님
                                    </p>
                                    <p style="margin: 0 0 25px 0; color: #333333; font-size: 16px; line-height: 24px; font-family: Arial, \'Malgun Gothic\', sans-serif;">
                                        {{company_name}}에서 {{project_name}} 프로젝트와 관련하여 서류 제출을 요청드립니다.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                
                <!-- 프로젝트 정보 -->
                <tr>
                    <td>
                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="padding: 0 40px;">
                                    <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f8f9fa" style="border: 1px solid #e0e0e0;">
                                        <tr>
                                            <td style="padding: 20px;">
                                                <h3 style="margin: 0 0 15px 0; color: #333333; font-size: 18px; font-weight: bold; font-family: Arial, \'Malgun Gothic\', sans-serif;">프로젝트 정보</h3>
                                                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td width="30%" style="padding: 5px 0; color: #666666; font-size: 14px; font-weight: bold; font-family: Arial, \'Malgun Gothic\', sans-serif;">프로젝트명:</td>
                                                        <td style="padding: 5px 0; color: #333333; font-size: 14px; font-family: Arial, \'Malgun Gothic\', sans-serif;">{{project_name}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding: 5px 0; color: #666666; font-size: 14px; font-weight: bold; font-family: Arial, \'Malgun Gothic\', sans-serif;">제출 기한:</td>
                                                        <td style="padding: 5px 0; color: #dc3545; font-size: 14px; font-weight: bold; font-family: Arial, \'Malgun Gothic\', sans-serif;">{{due_date}}</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                
                <!-- 서류 목록 -->
                <tr>
                    <td>
                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="padding: 30px 40px;">
                                    <h3 style="margin: 0 0 15px 0; color: #333333; font-size: 18px; font-weight: bold; font-family: Arial, \'Malgun Gothic\', sans-serif;">필수 제출 서류</h3>
                                    {{required_documents}}
                                    
                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                        <tr><td style="padding: 25px 0 0 0;"></td></tr>
                                    </table>
                                    
                                    <h3 style="margin: 0 0 15px 0; color: #333333; font-size: 18px; font-weight: bold; font-family: Arial, \'Malgun Gothic\', sans-serif;">선택 제출 서류</h3>
                                    {{optional_documents}}
                                    
                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                        <tr><td style="padding: 25px 0 0 0;"></td></tr>
                                    </table>
                                    
                                    <h3 style="margin: 0 0 15px 0; color: #333333; font-size: 18px; font-weight: bold; font-family: Arial, \'Malgun Gothic\', sans-serif;">추가 요청사항</h3>
                                    <p style="margin: 0; color: #333333; font-size: 14px; line-height: 22px; font-family: Arial, \'Malgun Gothic\', sans-serif;">{{additional_notes}}</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                
                <!-- 업로드 버튼 -->
                <tr>
                    <td>
                        {{upload_link}}
                    </td>
                </tr>
                
                <!-- 푸터 -->
                <tr>
                    <td bgcolor="#f8f9fa">
                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td style="padding: 30px 40px; border-top: 1px solid #e0e0e0;">
                                    <p style="margin: 0 0 10px 0; color: #666666; font-size: 14px; line-height: 20px; font-family: Arial, \'Malgun Gothic\', sans-serif;">
                                        이 링크는 보안을 위해 제출 기한까지만 유효합니다.
                                    </p>
                                    <p style="margin: 0; color: #666666; font-size: 14px; line-height: 20px; font-family: Arial, \'Malgun Gothic\', sans-serif;">
                                        문의사항이 있으시면 회신 부탁드립니다.
                                    </p>
                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                        <tr><td style="padding: 15px 0 0 0;"></td></tr>
                                    </table>
                                    <p style="margin: 0; color: #333333; font-size: 14px; font-family: Arial, \'Malgun Gothic\', sans-serif;">
                                        감사합니다.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <!--[if mso]></td></tr></table><![endif]-->
        </td>
    </tr>
</table>';
    }

    // 리마인더 템플릿
    private function getReminderTemplate() {
        // 리마인더용 템플릿 (추후 구현)
        return $this->getRequestNotificationTemplate();
    }

    // 완료 알림 템플릿
    private function getCompletionTemplate() {
        // 완료 알림용 템플릿 (추후 구현)
        return $this->getRequestNotificationTemplate();
    }

    // 기본 이메일 전송 함수
    public function sendEmail($to_email, $to_name, $subject, $body, $attachments = []) {
        $mail = new PHPMailer(true);
        $config = $this->getSMTPConfig();

        try {
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
            $error_msg = "이메일 전송 실패: {$mail->ErrorInfo}";
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

    // 테스트 이메일 전송
    public function sendTestEmail($to_email, $subject = null, $content = null) {
        // 테스트용 샘플 데이터
        $sample_request = [
            'contact_person' => '홍길동',
            'supplier_name' => '테스트 의뢰처',
            'project_name' => '테스트 프로젝트',
            'due_date' => date('Y-m-d', strtotime('+7 days')),
            'upload_token' => 'test-token-' . time(),
            'additional_notes' => '서류는 PDF 형식으로 제출해주시기 바랍니다.'
        ];

        $sample_required = ['안전관리계획서', '유해위험방지계획서', '사업자등록증'];
        $sample_optional = ['건설업면허증', '기타 관련 서류'];

        // 템플릿 처리
        if(!empty($subject) && !empty($content)) {
            // 전달받은 템플릿 사용
            $template = [
                'subject' => $subject,
                'content' => $content
            ];
        } else {
            // 기본 템플릿 사용
            $template = $this->getEmailTemplate('request_notification');
        }

        // 변수 준비
        $variables = $this->prepareTemplateVariables($sample_request, $sample_required, $sample_optional);

        // 디버깅용 로그
        error_log("Test email - Subject template: " . $template['subject']);
        error_log("Test email - Variables: " . print_r(array_keys($variables), true));

        // 변수 치환
        $test_subject = '[테스트] ' . $this->replaceTemplateVariables($template['subject'], $variables);
        $test_body = $this->replaceTemplateVariables($template['content'], $variables);
        
        // MSO 조건부 주석 복원
        $test_body = $this->restoreMsoComments($test_body);

        error_log("Test email - Final subject: " . $test_subject);

        // HTML 구조 보장
        $test_body = $this->ensureHTMLStructure($test_body);

        // 이메일 발송
        return $this->sendEmail($to_email, '테스트 수신자', $test_subject, $test_body);
    }
}
?>
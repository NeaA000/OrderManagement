<?php
// classes/EmailSender.php

require_once('../initialize.php');
require_once('PHPMailer/PHPMailer.php');
require_once('PHPMailer/SMTP.php');
require_once('PHPMailer/Exception.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailSender extends DBConnection {
    private $settings;

    function __construct() {
        global $_settings;
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
        $upload_link = base_url."upload_portal/?token=".$request['upload_token'];

        // 이메일 본문 생성
        $email_body = $this->generateEmailBody($request, $required_docs, $optional_docs, $upload_link);

        // 이메일 전송
        $result = $this->sendEmail(
            $request['email'],
            $request['contact_person'],
            "[{$this->settings->info('name')}] 서류 제출 요청 - {$request['project_name']}",
            $email_body
        );

        if($result['status'] == 'success') {
            // 이메일 전송 시간 업데이트
            $this->conn->query("UPDATE `document_requests` SET email_sent_at = NOW(), status = 1 WHERE id = '{$request_id}'");
        }

        return $result;
    }

    // 이메일 본문 생성
    private function generateEmailBody($request, $required_docs, $optional_docs, $upload_link) {
        $due_date = date('Y년 m월 d일', strtotime($request['due_date']));
        $company_name = $this->settings->info('name');

        $html = "
        <div style='font-family: \"Noto Sans KR\", sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background-color: #f8f9fa; padding: 20px; border-radius: 10px;'>
                <h2 style='color: #333; margin-bottom: 20px;'>서류 제출 요청</h2>
                
                <p>안녕하세요, <strong>{$request['contact_person']}</strong>님</p>
                
                <p><strong>{$request['project_name']}</strong> 프로젝트와 관련하여 아래 서류 제출을 요청드립니다.</p>
                
                <div style='background-color: #fff; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #007bff;'>
                    <h3 style='color: #007bff; margin-top: 0;'>프로젝트 정보</h3>
                    <p><strong>프로젝트명:</strong> {$request['project_name']}</p>
                    <p><strong>제출 기한:</strong> <span style='color: #dc3545; font-weight: bold;'>{$due_date}</span></p>
                </div>
        ";

        if(count($required_docs) > 0) {
            $html .= "
                <div style='background-color: #fff; padding: 20px; margin: 20px 0; border-radius: 5px;'>
                    <h3 style='color: #dc3545; margin-top: 0;'>필수 제출 서류</h3>
                    <ul style='color: #666;'>";
            foreach($required_docs as $doc) {
                $html .= "<li>{$doc}</li>";
            }
            $html .= "</ul></div>";
        }

        if(count($optional_docs) > 0) {
            $html .= "
                <div style='background-color: #fff; padding: 20px; margin: 20px 0; border-radius: 5px;'>
                    <h3 style='color: #28a745; margin-top: 0;'>선택 제출 서류</h3>
                    <ul style='color: #666;'>";
            foreach($optional_docs as $doc) {
                $html .= "<li>{$doc}</li>";
            }
            $html .= "</ul></div>";
        }

        if(!empty($request['additional_notes'])) {
            $html .= "
                <div style='background-color: #fff3cd; padding: 20px; margin: 20px 0; border-radius: 5px;'>
                    <h3 style='color: #856404; margin-top: 0;'>추가 요청사항</h3>
                    <p style='color: #856404;'>{$request['additional_notes']}</p>
                </div>";
        }

        $html .= "
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$upload_link}' style='display: inline-block; padding: 15px 40px; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 5px; font-size: 16px; font-weight: bold;'>서류 업로드하기</a>
                </div>
                
                <div style='background-color: #e9ecef; padding: 15px; border-radius: 5px; margin-top: 20px;'>
                    <p style='color: #666; margin: 0; font-size: 14px;'>
                        ※ 위 버튼을 클릭하시면 서류 업로드 페이지로 이동합니다.<br>
                        ※ 업로드 링크: <a href='{$upload_link}' style='color: #007bff;'>{$upload_link}</a><br>
                        ※ 문의사항이 있으시면 회신 부탁드립니다.
                    </p>
                </div>
                
                <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6;'>
                    <p style='color: #666; font-size: 14px;'>{$company_name}</p>
                </div>
            </div>
        </div>
        ";

        return $html;
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
            foreach($attachments as $attachment) {
                if(file_exists($attachment['path'])) {
                    $mail->addAttachment($attachment['path'], $attachment['name']);
                }
            }

            $mail->send();
            return ['status' => 'success', 'msg' => '이메일이 성공적으로 전송되었습니다.'];

        } catch (Exception $e) {
            return ['status' => 'error', 'msg' => "이메일 전송 실패: {$mail->ErrorInfo}"];
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
}
?>
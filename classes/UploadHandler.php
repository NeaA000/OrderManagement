<?php
// classes/UploadHandler.php

require_once('../initialize.php');

class UploadHandler extends DBConnection {
    private $settings;
    private $upload_dir;
    private $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'zip'];
    private $max_size = 10485760; // 10MB

    function __construct() {
        global $_settings;
        $this->settings = $_settings;
        parent::__construct();

        // 업로드 디렉토리 설정
        $this->upload_dir = base_app.'uploads/documents/';
        $this->createUploadDirectories();
    }

    // 업로드 디렉토리 생성
    private function createUploadDirectories() {
        if(!is_dir($this->upload_dir)) {
            mkdir($this->upload_dir, 0777, true);
        }

        // 연도/월 폴더 생성
        $year_dir = $this->upload_dir . date('Y') . '/';
        $month_dir = $year_dir . date('m') . '/';

        if(!is_dir($year_dir)) {
            mkdir($year_dir, 0777, true);
        }
        if(!is_dir($month_dir)) {
            mkdir($month_dir, 0777, true);
        }
    }

    // 파일 업로드 처리
    public function uploadDocument($file, $request_id, $document_id) {
        // 파일 유효성 검사
        $validation = $this->validateFile($file);
        if($validation['status'] == 'error') {
            return $validation;
        }

        // 파일 정보 추출
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $file_name = $this->generateFileName($file['name'], $request_id, $document_id);
        $upload_path = $this->getUploadPath() . $file_name;

        // 파일 이동
        if(move_uploaded_file($file['tmp_name'], $upload_path)) {
            // DB 업데이트
            $this->updateDocumentStatus($document_id, $file_name, $upload_path, $file['size']);

            // 업로드 로그 생성
            $this->createUploadLog($request_id, $document_id, 'upload', $file_name);

            // 전체 상태 확인 및 업데이트
            $this->checkRequestCompletion($request_id);

            return [
                'status' => 'success',
                'msg' => '파일이 성공적으로 업로드되었습니다.',
                'file_name' => $file_name,
                'file_path' => $upload_path
            ];
        } else {
            return ['status' => 'error', 'msg' => '파일 업로드 중 오류가 발생했습니다.'];
        }
    }

    // 파일 유효성 검사
    private function validateFile($file) {
        // 파일 존재 확인
        if(!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['status' => 'error', 'msg' => '파일 업로드 오류가 발생했습니다.'];
        }

        // 파일 크기 확인
        if($file['size'] > $this->max_size) {
            return ['status' => 'error', 'msg' => '파일 크기는 10MB를 초과할 수 없습니다.'];
        }

        // 파일 형식 확인
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if(!in_array($file_ext, $this->allowed_types)) {
            return ['status' => 'error', 'msg' => '허용되지 않은 파일 형식입니다. (허용: ' . implode(', ', $this->allowed_types) . ')'];
        }

        // MIME 타입 확인
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed_mimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/jpeg',
            'image/png',
            'application/zip'
        ];

        if(!in_array($mime_type, $allowed_mimes)) {
            return ['status' => 'error', 'msg' => '파일 내용이 올바르지 않습니다.'];
        }

        return ['status' => 'success'];
    }

    // 파일명 생성
    private function generateFileName($original_name, $request_id, $document_id) {
        $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $base_name = pathinfo($original_name, PATHINFO_FILENAME);
        $base_name = preg_replace('/[^a-zA-Z0-9가-힣_-]/', '_', $base_name);

        // 파일명 형식: REQ{요청ID}_DOC{문서ID}_{원본파일명}_{타임스탬프}.{확장자}
        $file_name = "REQ{$request_id}_DOC{$document_id}_{$base_name}_" . time() . ".{$file_ext}";

        return $file_name;
    }

    // 업로드 경로 가져오기
    private function getUploadPath() {
        return $this->upload_dir . date('Y') . '/' . date('m') . '/';
    }

    // 문서 상태 업데이트
    private function updateDocumentStatus($document_id, $file_name, $file_path, $file_size) {
        $sql = "UPDATE `request_documents` SET 
                status = 1,
                file_name = '{$file_name}',
                file_path = '{$file_path}',
                file_size = '{$file_size}',
                uploaded_at = NOW()
                WHERE id = '{$document_id}'";

        $this->conn->query($sql);
    }

    // 업로드 로그 생성
    private function createUploadLog($request_id, $document_id, $action, $file_name) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $sql = "INSERT INTO `upload_logs` SET
                request_id = '{$request_id}',
                document_id = '{$document_id}',
                action = '{$action}',
                file_name = '{$file_name}',
                ip_address = '{$ip}',
                user_agent = '{$user_agent}'";

        $this->conn->query($sql);
    }

    // 요청 완료 상태 확인
    private function checkRequestCompletion($request_id) {
        // 필수 서류 중 미제출 건수 확인
        $check = $this->conn->query("
            SELECT COUNT(*) as pending 
            FROM `request_documents` 
            WHERE request_id = '{$request_id}' 
            AND is_required = 1 
            AND status = 0
        ")->fetch_assoc();

        if($check['pending'] == 0) {
            // 모든 필수 서류가 제출되면 요청 상태를 완료로 변경
            $this->conn->query("UPDATE `document_requests` SET status = 2 WHERE id = '{$request_id}'");

            // 완료 알림 생성
            $this->createNotification($request_id, 'request_completed', '서류 제출 완료', '모든 필수 서류가 제출되었습니다.');
        }
    }

    // 알림 생성
    private function createNotification($request_id, $type, $title, $message) {
        $sql = "INSERT INTO `notifications` SET
                request_id = '{$request_id}',
                type = '{$type}',
                title = '{$title}',
                message = '{$message}'";

        $this->conn->query($sql);
    }

    // 파일 삭제
    public function deleteDocument($document_id) {
        // 문서 정보 가져오기
        $doc = $this->conn->query("SELECT * FROM `request_documents` WHERE id = '{$document_id}'")->fetch_assoc();

        if(!$doc) {
            return ['status' => 'error', 'msg' => '문서를 찾을 수 없습니다.'];
        }

        // 파일 삭제
        if(!empty($doc['file_path']) && file_exists($doc['file_path'])) {
            unlink($doc['file_path']);
        }

        // DB 업데이트
        $sql = "UPDATE `request_documents` SET 
                status = 0,
                file_name = NULL,
                file_path = NULL,
                file_size = NULL,
                uploaded_at = NULL
                WHERE id = '{$document_id}'";

        $this->conn->query($sql);

        // 삭제 로그 생성
        $this->createUploadLog($doc['request_id'], $document_id, 'delete', $doc['file_name']);

        // 요청 상태 재확인
        $this->conn->query("UPDATE `document_requests` SET status = 1 WHERE id = '{$doc['request_id']}'");

        return ['status' => 'success', 'msg' => '파일이 삭제되었습니다.'];
    }

    // 파일 다운로드 URL 생성
    public function getDownloadUrl($document_id) {
        $doc = $this->conn->query("SELECT * FROM `request_documents` WHERE id = '{$document_id}'")->fetch_assoc();

        if($doc && !empty($doc['file_path'])) {
            return base_url . 'download.php?id=' . $document_id;
        }

        return null;
    }

    // 파일 미리보기 가능 여부 확인
    public function canPreview($file_name) {
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $preview_types = ['pdf', 'jpg', 'jpeg', 'png'];

        return in_array($ext, $preview_types);
    }
}
?>
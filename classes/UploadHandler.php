<?php
// classes/UploadHandler.php

require_once(__DIR__ . '/../initialize.php');

class UploadHandler extends DBConnection {
    private $settings;
    private $upload_dir;
    private $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'zip', 'hwp', 'hwpx'];
    private $max_size = 10485760; // 10MB

    function __construct() {
        global $_settings;
        $this->settings = $_settings;
        parent::__construct();

        // 업로드 디렉토리 설정 - 절대 경로 사용
        $this->upload_dir = __DIR__ . '/../uploads/documents/';

        // 디렉토리 생성
        if(!$this->createUploadDirectories()) {
            throw new Exception('업로드 디렉토리를 생성할 수 없습니다.');
        }
    }

    // 업로드 디렉토리 생성
    private function createUploadDirectories() {
        try {
            // 기본 업로드 디렉토리
            if(!is_dir($this->upload_dir)) {
                if(!mkdir($this->upload_dir, 0755, true)) {
                    error_log("Failed to create directory: " . $this->upload_dir);
                    return false;
                }
            }

            // 연도/월 폴더 생성
            $year_dir = $this->upload_dir . date('Y') . '/';
            $month_dir = $year_dir . date('m') . '/';

            if(!is_dir($year_dir)) {
                if(!mkdir($year_dir, 0755, true)) {
                    error_log("Failed to create year directory: " . $year_dir);
                    return false;
                }
            }

            if(!is_dir($month_dir)) {
                if(!mkdir($month_dir, 0755, true)) {
                    error_log("Failed to create month directory: " . $month_dir);
                    return false;
                }
            }

            // 디렉토리 쓰기 권한 확인
            if(!is_writable($month_dir)) {
                error_log("Directory not writable: " . $month_dir);
                return false;
            }

            return true;
        } catch(Exception $e) {
            error_log("Directory creation error: " . $e->getMessage());
            return false;
        }
    }

    // 파일 업로드 처리
    public function uploadDocument($file, $request_id, $document_id) {
        try {
            // 파일 유효성 검사
            $validation = $this->validateFile($file);
            if($validation['status'] == 'error') {
                return $validation;
            }

            // 파일 정보 추출
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $file_name = $this->generateFileName($file['name'], $request_id, $document_id);
            $upload_path = $this->getUploadPath() . $file_name;

            // 디렉토리 존재 확인
            $upload_dir = dirname($upload_path);
            if(!is_dir($upload_dir)) {
                if(!mkdir($upload_dir, 0755, true)) {
                    return ['status' => 'error', 'msg' => '업로드 디렉토리를 생성할 수 없습니다.'];
                }
            }

            // 파일 이동
            if(!move_uploaded_file($file['tmp_name'], $upload_path)) {
                $error = error_get_last();
                error_log("File upload failed: " . print_r($error, true));
                return ['status' => 'error', 'msg' => '파일 저장 중 오류가 발생했습니다.'];
            }

            // 파일 권한 설정
            chmod($upload_path, 0644);

            // 상대 경로로 변환 (DB 저장용)
            $relative_path = str_replace(__DIR__ . '/../', '', $upload_path);

            // DB 업데이트
            if(!$this->updateDocumentStatus($document_id, $file_name, $relative_path, $file['size'])) {
                // DB 업데이트 실패 시 업로드된 파일 삭제
                unlink($upload_path);
                return ['status' => 'error', 'msg' => '문서 정보 업데이트 중 오류가 발생했습니다.'];
            }

            // 업로드 로그 생성
            $this->createUploadLog($request_id, $document_id, 'upload', $file_name);

            // 🔔 실시간 알림 생성 (새로 추가)
            $this->createUploadNotification($request_id, $document_id, $file_name);

            // 전체 상태 확인 및 업데이트
            $this->checkRequestCompletion($request_id);

            return [
                'status' => 'success',
                'msg' => '파일이 성공적으로 업로드되었습니다.',
                'file_name' => $file_name,
                'file_path' => $relative_path
            ];

        } catch(Exception $e) {
            error_log("Upload exception: " . $e->getMessage());
            return ['status' => 'error', 'msg' => '파일 업로드 중 오류가 발생했습니다.'];
        }
    }

    // 파일 유효성 검사
    private function validateFile($file) {
        // 파일 존재 확인
        if(!isset($file) || !is_array($file)) {
            return ['status' => 'error', 'msg' => '유효하지 않은 파일입니다.'];
        }

        // 파일 업로드 에러 확인
        if($file['error'] !== UPLOAD_ERR_OK) {
            return ['status' => 'error', 'msg' => '파일 업로드 오류가 발생했습니다. (코드: ' . $file['error'] . ')'];
        }

        // 파일 크기 확인
        if($file['size'] <= 0) {
            return ['status' => 'error', 'msg' => '파일 크기가 0입니다.'];
        }

        if($file['size'] > $this->max_size) {
            return ['status' => 'error', 'msg' => '파일 크기는 10MB를 초과할 수 없습니다. (현재: ' . round($file['size'] / 1048576, 2) . 'MB)'];
        }

        // 파일 형식 확인
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if(!in_array($file_ext, $this->allowed_types)) {
            return ['status' => 'error', 'msg' => '허용되지 않은 파일 형식입니다. (허용: ' . implode(', ', $this->allowed_types) . ')'];
        }

        // MIME 타입 확인
        if(function_exists('finfo_open')) {
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
                'application/zip',
                'application/x-zip-compressed',
                'application/x-hwp',
                'application/haansofthwp',
                'application/vnd.hancom.hwp',
                'application/vnd.hancom.hwpx',
                'application/octet-stream' // 일부 시스템에서 HWP 파일이 이렇게 인식될 수 있음
            ];

            // HWP 파일의 경우 MIME 타입이 다양할 수 있으므로 확장자로도 체크
            if(in_array($file_ext, ['hwp', 'hwpx']) && $mime_type == 'application/octet-stream') {
                return ['status' => 'success'];
            }

            if(!in_array($mime_type, $allowed_mimes)) {
                error_log("Invalid MIME type: " . $mime_type . " for file: " . $file['name']);
                // MIME 타입 검증 실패시 경고만 로그에 기록하고 계속 진행
                // 일부 서버에서 MIME 타입 검증이 제대로 작동하지 않을 수 있음
            }
        }

        return ['status' => 'success'];
    }

    // 파일명 생성
    private function generateFileName($original_name, $request_id, $document_id) {
        $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $base_name = pathinfo($original_name, PATHINFO_FILENAME);

        // 한글 파일명 처리
        $base_name = preg_replace('/[^\p{L}\p{N}_-]/u', '_', $base_name);

        // 파일명 길이 제한
        if(strlen($base_name) > 50) {
            $base_name = substr($base_name, 0, 50);
        }

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
        try {
            $stmt = $this->conn->prepare("
                UPDATE `request_documents` SET 
                status = 1,
                file_name = ?,
                file_path = ?,
                file_size = ?,
                uploaded_at = NOW()
                WHERE id = ?
            ");

            if(!$stmt) {
                error_log("Prepare failed: " . $this->conn->error);
                return false;
            }

            $stmt->bind_param("ssii", $file_name, $file_path, $file_size, $document_id);
            $result = $stmt->execute();

            if(!$result) {
                error_log("Execute failed: " . $stmt->error);
            }

            $stmt->close();
            return $result;

        } catch(Exception $e) {
            error_log("DB Update error: " . $e->getMessage());
            return false;
        }
    }

    // 업로드 로그 생성
    private function createUploadLog($request_id, $document_id, $action, $file_name) {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

            $stmt = $this->conn->prepare("
                INSERT INTO `upload_logs` 
                (request_id, document_id, action, file_name, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            if($stmt) {
                $stmt->bind_param("iissss", $request_id, $document_id, $action, $file_name, $ip, $user_agent);
                $stmt->execute();
                $stmt->close();
            }
        } catch(Exception $e) {
            error_log("Upload log error: " . $e->getMessage());
            // 로그 실패는 업로드 실패로 처리하지 않음
        }
    }

    // 🔔 업로드 알림 생성 (새로 추가된 메서드)
    private function createUploadNotification($request_id, $document_id, $file_name) {
        try {
            // 요청 및 문서 정보 조회
            $info_query = $this->conn->prepare("
                SELECT dr.supplier_id, s.name as supplier_name, 
                       rd.document_name
                FROM document_requests dr
                LEFT JOIN supplier_list s ON dr.supplier_id = s.id
                LEFT JOIN request_documents rd ON rd.id = ?
                WHERE dr.id = ?
            ");

            if(!$info_query) {
                error_log("Prepare failed for notification info: " . $this->conn->error);
                return false;
            }

            $info_query->bind_param("ii", $document_id, $request_id);
            $info_query->execute();
            $result = $info_query->get_result();

            if($result && $result->num_rows > 0) {
                $info = $result->fetch_assoc();

                // 알림 삽입
                $notif_stmt = $this->conn->prepare("
                    INSERT INTO upload_notifications 
                    (request_id, document_id, supplier_id, supplier_name, document_name, file_name, uploaded_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");

                if(!$notif_stmt) {
                    error_log("Prepare failed for notification insert: " . $this->conn->error);
                    return false;
                }

                $notif_stmt->bind_param("iiisss",
                    $request_id,
                    $document_id,
                    $info['supplier_id'],
                    $info['supplier_name'],
                    $info['document_name'],
                    $file_name
                );

                $success = $notif_stmt->execute();

                if(!$success) {
                    error_log("Notification insert failed: " . $notif_stmt->error);
                }

                $notif_stmt->close();
            }

            $info_query->close();

        } catch(Exception $e) {
            error_log("Notification creation failed: " . $e->getMessage());
            // 알림 생성 실패는 업로드 실패로 처리하지 않음
            return false;
        }

        return true;
    }

    // 요청 완료 상태 확인
    private function checkRequestCompletion($request_id) {
        try {
            // 모든 문서가 업로드되었는지 확인
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total, 
                       SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as completed 
                FROM `request_documents` 
                WHERE request_id = ?
            ");

            if(!$stmt) return;

            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            // 모든 문서가 업로드되었으면 요청 상태 업데이트
            if($row['total'] > 0 && $row['total'] == $row['completed']) {
                $update_stmt = $this->conn->prepare("
                    UPDATE `document_requests` 
                    SET status = 2, completed_at = NOW() 
                    WHERE id = ?
                ");

                if($update_stmt) {
                    $update_stmt->bind_param("i", $request_id);
                    $update_stmt->execute();
                    $update_stmt->close();

                    // 완료 알림 이메일 발송 (선택사항)
                    $this->sendCompletionNotification($request_id);
                }
            }
        } catch(Exception $e) {
            error_log("Check completion error: " . $e->getMessage());
        }
    }

    // 완료 알림 발송
    private function sendCompletionNotification($request_id) {
        // 이메일 발송 로직 구현 (선택사항)
        // EmailHandler 클래스를 사용하여 관리자에게 알림
    }

    // 파일 삭제 처리
    public function deleteDocument($document_id) {
        try {
            // 문서 정보 조회
            $stmt = $this->conn->prepare("
                SELECT * FROM `request_documents` 
                WHERE id = ? AND status = 1
            ");

            if(!$stmt) {
                return ['status' => 'error', 'msg' => '문서 조회 중 오류가 발생했습니다.'];
            }

            $stmt->bind_param("i", $document_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if($result->num_rows <= 0) {
                return ['status' => 'error', 'msg' => '삭제할 파일이 없습니다.'];
            }

            $doc = $result->fetch_assoc();
            $stmt->close();

            // 파일 삭제
            $file_path = __DIR__ . '/../' . $doc['file_path'];
            if(file_exists($file_path)) {
                if(!unlink($file_path)) {
                    return ['status' => 'error', 'msg' => '파일 삭제 중 오류가 발생했습니다.'];
                }
            }

            // DB 업데이트
            $update_stmt = $this->conn->prepare("
                UPDATE `request_documents` 
                SET status = 0, file_name = NULL, file_path = NULL, 
                    file_size = NULL, uploaded_at = NULL 
                WHERE id = ?
            ");

            if(!$update_stmt) {
                return ['status' => 'error', 'msg' => '데이터베이스 업데이트 중 오류가 발생했습니다.'];
            }

            $update_stmt->bind_param("i", $document_id);
            $update_stmt->execute();
            $update_stmt->close();

            // 삭제 로그
            $this->createUploadLog($doc['request_id'], $document_id, 'delete', $doc['file_name']);

            // 요청 상태 재확인
            $this->updateRequestStatus($doc['request_id']);

            return ['status' => 'success', 'msg' => '파일이 삭제되었습니다.'];

        } catch(Exception $e) {
            error_log("Delete error: " . $e->getMessage());
            return ['status' => 'error', 'msg' => '파일 삭제 중 오류가 발생했습니다.'];
        }
    }

    // 요청 상태 업데이트
    private function updateRequestStatus($request_id) {
        try {
            // 요청이 완료 상태였다가 파일 삭제로 미완료가 된 경우 상태 변경
            $stmt = $this->conn->prepare("
                UPDATE `document_requests` 
                SET status = 1, completed_at = NULL 
                WHERE id = ? AND status = 2
            ");

            if($stmt) {
                $stmt->bind_param("i", $request_id);
                $stmt->execute();
                $stmt->close();
            }
        } catch(Exception $e) {
            error_log("Update request status error: " . $e->getMessage());
        }
    }
}
?>
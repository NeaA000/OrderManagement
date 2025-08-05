<?php
// classes/UploadHandler.php

class UploadHandler extends DBConnection {
    private $settings;
    private $upload_dir;
    private $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'zip', 'hwp', 'hwpx'];
    private $max_size = 10485760; // 10MB
    private $s3Client = null;
    private $wasabi_config = [];

    // ★ 매직 바이트 정의 (보안 강화)
    private $magic_bytes = [
        'pdf' => "\x25\x50\x44\x46",
        'doc' => "\xD0\xCF\x11\xE0",
        'docx' => "\x50\x4B\x03\x04",
        'xls' => "\xD0\xCF\x11\xE0",
        'xlsx' => "\x50\x4B\x03\x04",
        'jpg' => "\xFF\xD8\xFF",
        'jpeg' => "\xFF\xD8\xFF",
        'png' => "\x89\x50\x4E\x47",
        'zip' => "\x50\x4B",
        'hwp' => "\xD0\xCF\x11\xE0", // HWP v5.0
        'hwpx' => "\x50\x4B\x03\x04"  // HWPX
    ];

    function __construct() {
        global $_settings;
        $this->settings = $_settings;
        $this->upload_dir = base_app . 'uploads/documents/';
        parent::__construct();

        // Wasabi 설정 초기화
        $this->initializeWasabi();
    }

    // Wasabi S3 클라이언트 초기화
    private function initializeWasabi() {
        // Wasabi 사용 설정 확인
        if($this->settings->info('use_wasabi') !== 'true') {
            return;
        }

        // Wasabi 설정 가져오기
        $this->wasabi_config = [
            'key' => $this->settings->info('wasabi_access_key'),
            'secret' => $this->settings->info('wasabi_secret_key'),
            'region' => $this->settings->info('wasabi_region') ?? 'ap-northeast-1',
            'bucket' => $this->settings->info('wasabi_bucket'),
            'endpoint' => $this->settings->info('wasabi_endpoint') ?? 'https://s3.ap-northeast-1.wasabisys.com'
        ];

        // 필수 설정 확인
        if(empty($this->wasabi_config['key']) || empty($this->wasabi_config['secret']) || empty($this->wasabi_config['bucket'])) {
            error_log("Wasabi configuration incomplete");
            return;
        }

        try {
            // Composer autoload 확인
            $autoload_path = base_app . 'vendor/autoload.php';
            if(!file_exists($autoload_path)) {
                error_log("AWS SDK not found. Please run: composer require aws/aws-sdk-php");
                return;
            }

            require_once $autoload_path;

            // S3 클라이언트 생성
            $this->s3Client = new \Aws\S3\S3Client([
                'version' => 'latest',
                'region' => $this->wasabi_config['region'],
                'endpoint' => $this->wasabi_config['endpoint'],
                'use_path_style_endpoint' => true,
                'credentials' => [
                    'key' => $this->wasabi_config['key'],
                    'secret' => $this->wasabi_config['secret']
                ]
            ]);

        } catch(Exception $e) {
            error_log("Wasabi initialization error: " . $e->getMessage());
            $this->s3Client = null;
        }
    }

    // 파일 업로드 메인 함수
    public function uploadDocument($file, $request_id, $document_id, $document_name = null) {
        // 파일 유효성 검사 (강화된 검증)
        $validation = $this->validateFile($file);
        if($validation['status'] !== 'success') {
            return $validation;
        }

        // 파일명 생성
        $file_name = $this->generateFileName($file['name'], $request_id, $document_id);

        // Wasabi 사용 여부에 따라 분기
        if($this->s3Client !== null && $this->settings->info('use_wasabi') === 'true') {
            return $this->uploadToWasabi($file, $file_name, $request_id, $document_id, $document_name);
        } else {
            return $this->uploadToLocal($file, $file_name, $request_id, $document_id, $document_name);
        }
    }

    // ★ 강화된 파일 유효성 검사
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

        // ★ 파일명 보안 검증 (디렉토리 traversal 방지)
        $file_name = basename($file['name']);
        if($file_name !== $file['name'] || preg_match('/\.\./', $file['name'])) {
            return ['status' => 'error', 'msg' => '잘못된 파일명입니다.'];
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
                'application/octet-stream'
            ];

            // HWP 파일의 경우 MIME 타입이 다양할 수 있으므로 확장자로도 체크
            if(in_array($file_ext, ['hwp', 'hwpx']) && $mime_type == 'application/octet-stream') {
                // HWP 파일은 통과
            } else if(!in_array($mime_type, $allowed_mimes)) {
                error_log("Invalid MIME type: " . $mime_type . " for file: " . $file['name']);
                // ★ MIME 타입 검증 실패 시 차단 (보안 강화)
                return ['status' => 'error', 'msg' => '허용되지 않은 파일 형식입니다. (MIME: ' . $mime_type . ')'];
            }
        }

        // ★ 매직 바이트 검증 (보안 강화)
        if(isset($this->magic_bytes[$file_ext])) {
            $handle = fopen($file['tmp_name'], 'rb');
            if($handle) {
                $header = fread($handle, 8);
                fclose($handle);

                $magic = $this->magic_bytes[$file_ext];
                if(strpos($header, $magic) !== 0) {
                    // ZIP 기반 파일들 (docx, xlsx, hwpx)은 추가 검증
                    if(in_array($file_ext, ['docx', 'xlsx', 'hwpx']) && strpos($header, "\x50\x4B") === 0) {
                        // ZIP 파일 구조 검증 통과
                    } else {
                        return ['status' => 'error', 'msg' => '파일 내용이 확장자와 일치하지 않습니다.'];
                    }
                }
            }
        }

        // ★ 실행 가능한 콘텐츠 검사 (보안 강화)
        $content = file_get_contents($file['tmp_name'], false, null, 0, 1024);
        $dangerous_patterns = [
            '/<\?php/i',
            '/<script/i',
            '/<%[\s\S]*?%>/i',
            '/<\?[\s\S]*?\?>/i',
            '/eval\s*\(/i',
            '/exec\s*\(/i',
            '/system\s*\(/i'
        ];

        foreach($dangerous_patterns as $pattern) {
            if(preg_match($pattern, $content)) {
                return ['status' => 'error', 'msg' => '보안상 위험한 콘텐츠가 포함되어 있습니다.'];
            }
        }

        return ['status' => 'success'];
    }

    // ★ 안전한 파일명 생성 (보안 강화)
    private function generateFileName($original_name, $request_id, $document_id) {
        $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $base_name = pathinfo($original_name, PATHINFO_FILENAME);

        // 한글 파일명 처리 및 특수문자 제거
        $base_name = preg_replace('/[^\p{L}\p{N}_-]/u', '_', $base_name);

        // 이중 언더스코어 제거
        $base_name = preg_replace('/_+/', '_', $base_name);

        // 파일명 길이 제한
        if(mb_strlen($base_name) > 50) {
            $base_name = mb_substr($base_name, 0, 50);
        }

        // 파일명 형식: REQ{요청ID}_DOC{문서ID}_{원본파일명}_{타임스탬프}.{확장자}
        $file_name = sprintf("REQ%d_DOC%d_%s_%d.%s",
            $request_id,
            $document_id,
            $base_name,
            time(),
            $file_ext
        );

        return $file_name;
    }

    // 업로드 경로 가져오기
    private function getUploadPath() {
        return $this->upload_dir . date('Y') . '/' . date('m') . '/';
    }

    // 로컬로 파일 업로드
    private function uploadToLocal($file, $file_name, $request_id, $document_id, $document_name = null) {
        $upload_path = $this->getUploadPath() . $file_name;

        // 디렉토리 존재 확인
        $upload_dir = dirname($upload_path);
        if(!is_dir($upload_dir)) {
            if(!mkdir($upload_dir, 0755, true)) {
                return ['status' => 'error', 'msg' => '업로드 디렉토리를 생성할 수 없습니다.'];
            }
        }

        // ★ 경로 보안 검증 (디렉토리 traversal 방지)
        $real_upload_path = realpath($upload_dir);
        $real_base_path = realpath($this->upload_dir);
        if($real_upload_path === false || strpos($real_upload_path, $real_base_path) !== 0) {
            return ['status' => 'error', 'msg' => '잘못된 업로드 경로입니다.'];
        }

        // 파일 이동
        if(!move_uploaded_file($file['tmp_name'], $upload_path)) {
            $error = error_get_last();
            error_log("File upload failed: " . print_r($error, true));
            return ['status' => 'error', 'msg' => '파일 저장 중 오류가 발생했습니다.'];
        }

        // 파일 권한 설정 (실행 권한 제거)
        chmod($upload_path, 0644);

        // 상대 경로로 변환 (DB 저장용)
        $relative_path = str_replace(base_app, '', $upload_path);

        // DB 업데이트
        if(!$this->updateDocumentStatus($document_id, $file_name, $relative_path, $file['size'])) {
            // DB 업데이트 실패 시 업로드된 파일 삭제
            unlink($upload_path);
            return ['status' => 'error', 'msg' => '문서 정보 업데이트 중 오류가 발생했습니다.'];
        }

        // 업로드 로그 생성
        $this->createUploadLog($request_id, $document_id, 'upload', $file_name);

        // 실시간 알림 생성
        $this->createUploadNotification($request_id, $document_id, $file_name);

        return [
            'status' => 'success',
            'msg' => '파일이 성공적으로 업로드되었습니다.',
            'file_name' => $file_name,
            'file_path' => $relative_path,
            'storage' => 'local'
        ];
    }

    // Wasabi로 파일 업로드
    private function uploadToWasabi($file, $file_name, $request_id, $document_id, $document_name = null) {
        try {
            // S3 키 생성 (경로 포함)
            $key = 'documents/' . date('Y') . '/' . date('m') . '/' . $file_name;

            // 파일 MIME 타입 결정
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            // S3에 업로드
            $result = $this->s3Client->putObject([
                'Bucket' => $this->wasabi_config['bucket'],
                'Key' => $key,
                'SourceFile' => $file['tmp_name'],
                'ContentType' => $mime_type,
                'ACL' => 'private',
                'Metadata' => [
                    'request_id' => (string)$request_id,
                    'document_id' => (string)$document_id,
                    'original_name' => $file['name'],
                    'uploaded_at' => date('Y-m-d H:i:s')
                ]
            ]);

            // 업로드 성공 확인
            if($result['@metadata']['statusCode'] !== 200) {
                throw new Exception('Wasabi 업로드 실패');
            }

            // 공개 URL 생성 (필요한 경우)
            $wasabi_url = $result['ObjectURL'];

            // DB 업데이트 - uploaded_files 테이블에도 저장
            $this->conn->begin_transaction();

            try {
                // request_documents 테이블 업데이트
                if(!$this->updateDocumentStatus($document_id, $file_name, $key, $file['size'])) {
                    throw new Exception('문서 상태 업데이트 실패');
                }

                // uploaded_files 테이블에 저장
                $stmt = $this->conn->prepare("
                    INSERT INTO uploaded_files (
                        request_id, document_id, document_name, original_name, stored_name, 
                        wasabi_key, wasabi_bucket, wasabi_region, wasabi_url, 
                        file_size, mime_type, uploaded_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $uploaded_by = $this->settings->userdata('username') ?: 'system';

                $stmt->bind_param("iisssssssiss",
                    $request_id,
                    $document_id,
                    $document_name,
                    $file['name'],
                    $file_name,
                    $key,
                    $this->wasabi_config['bucket'],
                    $this->wasabi_config['region'],
                    $wasabi_url,
                    $file['size'],
                    $mime_type,
                    $uploaded_by
                );

                if(!$stmt->execute()) {
                    throw new Exception('uploaded_files 테이블 저장 실패');
                }

                $this->conn->commit();

                // 업로드 로그 생성
                $this->createUploadLog($request_id, $document_id, 'upload', $file_name);

                // 실시간 알림 생성
                $this->createUploadNotification($request_id, $document_id, $file_name);

                return [
                    'status' => 'success',
                    'msg' => '파일이 Wasabi에 성공적으로 업로드되었습니다.',
                    'file_name' => $file_name,
                    'file_path' => $key,
                    'storage' => 'wasabi'
                ];

            } catch(Exception $e) {
                $this->conn->rollback();
                // Wasabi에서 파일 삭제
                $this->s3Client->deleteObject([
                    'Bucket' => $this->wasabi_config['bucket'],
                    'Key' => $key
                ]);
                throw $e;
            }

        } catch(Exception $e) {
            error_log("Wasabi upload error: " . $e->getMessage());
            return ['status' => 'error', 'msg' => 'Wasabi 업로드 중 오류가 발생했습니다: ' . $e->getMessage()];
        }
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
        }
    }

    // 업로드 알림 생성
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
            return false;
        }

        return true;
    }

    // 파일 삭제 처리
    public function deleteDocument($document_id) {
        try {
            // 문서 정보 조회
            $stmt = $this->conn->prepare("
                SELECT rd.*, uf.wasabi_key, uf.wasabi_bucket
                FROM `request_documents` rd
                LEFT JOIN `uploaded_files` uf ON uf.document_id = rd.id
                WHERE rd.id = ? AND rd.status = 1
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

            $doc_info = $result->fetch_assoc();
            $stmt->close();

            // Wasabi 파일인 경우
            if(!empty($doc_info['wasabi_key']) && $this->s3Client !== null) {
                try {
                    $this->s3Client->deleteObject([
                        'Bucket' => $doc_info['wasabi_bucket'],
                        'Key' => $doc_info['wasabi_key']
                    ]);
                } catch(Exception $e) {
                    error_log("Wasabi delete error: " . $e->getMessage());
                }
            }
            // 로컬 파일인 경우
            else if(!empty($doc_info['file_path'])) {
                $file_path = base_app . $doc_info['file_path'];
                if(file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            // DB에서 파일 정보 제거
            $this->conn->begin_transaction();

            try {
                // request_documents 테이블 업데이트
                $update_stmt = $this->conn->prepare("
                    UPDATE `request_documents` SET 
                    status = 0,
                    file_name = NULL,
                    file_path = NULL,
                    file_size = NULL,
                    uploaded_at = NULL
                    WHERE id = ?
                ");
                $update_stmt->bind_param("i", $document_id);
                $update_stmt->execute();
                $update_stmt->close();

                // uploaded_files 테이블에서 삭제
                $delete_stmt = $this->conn->prepare("DELETE FROM `uploaded_files` WHERE document_id = ?");
                $delete_stmt->bind_param("i", $document_id);
                $delete_stmt->execute();
                $delete_stmt->close();

                $this->conn->commit();

                return ['status' => 'success', 'msg' => '파일이 삭제되었습니다.'];

            } catch(Exception $e) {
                $this->conn->rollback();
                return ['status' => 'error', 'msg' => '데이터베이스 업데이트 중 오류가 발생했습니다.'];
            }

        } catch(Exception $e) {
            error_log("Delete document error: " . $e->getMessage());
            return ['status' => 'error', 'msg' => '파일 삭제 중 오류가 발생했습니다.'];
        }
    }

    // Wasabi 파일 URL 생성
    public function getWasabiFileUrl($key, $expires = 3600) {
        if($this->s3Client === null) {
            return false;
        }

        try {
            $cmd = $this->s3Client->getCommand('GetObject', [
                'Bucket' => $this->wasabi_config['bucket'],
                'Key' => $key
            ]);

            $request = $this->s3Client->createPresignedRequest($cmd, '+' . $expires . ' seconds');
            return (string) $request->getUri();

        } catch(Exception $e) {
            error_log("Wasabi URL generation error: " . $e->getMessage());
            return false;
        }
    }
}
?>
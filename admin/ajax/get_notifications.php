<?php
// admin/ajax/get_notifications.php
require_once('../../config.php');

// 세션 시작
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 오류 출력 방지 (디버깅 시에는 주석 처리)
error_reporting(0);
ini_set('display_errors', 0);

// JSON 헤더 설정
header('Content-Type: application/json');

try {
    // 로그인 체크 - 세션 직접 확인
    if(!isset($_SESSION['userdata']) || !isset($_SESSION['userdata']['id'])) {
        throw new Exception('Unauthorized');
    }

    $user_id = $_SESSION['userdata']['id'];

    $action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : 'get');

    switch($action) {
        case 'get':
            // 읽지 않은 알림 가져오기
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

            $notifications = $conn->query("
                SELECT n.*, s.name as supplier_full_name, s.contact_person,
                       dr.request_no, dr.project_name
                FROM upload_notifications n
                LEFT JOIN document_requests dr ON n.request_id = dr.id
                LEFT JOIN supplier_list s ON n.supplier_id = s.id
                WHERE n.is_read = 0
                ORDER BY n.uploaded_at DESC
                LIMIT {$limit}
            ");

            $data = [];
            $count = 0;

            if($notifications && $notifications->num_rows > 0) {
                while($row = $notifications->fetch_assoc()) {
                    // 시간 포맷팅
                    $time_diff = time() - strtotime($row['uploaded_at']);
                    if($time_diff < 60) {
                        $time_text = "방금 전";
                    } elseif($time_diff < 3600) {
                        $time_text = floor($time_diff / 60) . "분 전";
                    } elseif($time_diff < 86400) {
                        $time_text = floor($time_diff / 3600) . "시간 전";
                    } else {
                        $time_text = date('m월 d일', strtotime($row['uploaded_at']));
                    }

                    $row['time_text'] = $time_text;
                    $row['formatted_time'] = date('Y-m-d H:i', strtotime($row['uploaded_at']));
                    $data[] = $row;
                    $count++;
                }
            }

            // 전체 미읽은 알림 수
            $total_result = $conn->query("SELECT COUNT(*) as count FROM upload_notifications WHERE is_read = 0");
            $total_unread = $total_result ? $total_result->fetch_assoc()['count'] : 0;

            echo json_encode([
                'status' => 'success',
                'notifications' => $data,
                'count' => $count,
                'total_unread' => $total_unread
            ]);
            break;

        case 'mark_read':
            // 알림을 읽음으로 표시
            $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;

            if($notification_id > 0) {
                $stmt = $conn->prepare("UPDATE upload_notifications SET is_read = 1, read_by = ?, read_at = NOW() WHERE id = ?");
                $stmt->bind_param("ii", $user_id, $notification_id);
                $success = $stmt->execute();
                $stmt->close();

                if($success) {
                    echo json_encode(['status' => 'success']);
                } else {
                    echo json_encode(['status' => 'error', 'msg' => 'Failed to update notification']);
                }
            } else {
                echo json_encode(['status' => 'error', 'msg' => 'Invalid notification ID']);
            }
            break;

        case 'mark_all_read':
            // 모든 알림을 읽음으로 표시
            $stmt = $conn->prepare("UPDATE upload_notifications SET is_read = 1, read_by = ?, read_at = NOW() WHERE is_read = 0");
            $stmt->bind_param("i", $user_id);
            $success = $stmt->execute();
            $affected = $conn->affected_rows;
            $stmt->close();

            if($success) {
                echo json_encode(['status' => 'success', 'affected' => $affected]);
            } else {
                echo json_encode(['status' => 'error', 'msg' => 'Failed to mark notifications as read']);
            }
            break;

        case 'get_recent_uploads':
            // 최근 업로드 현황 (테이블 업데이트용)
            $since = isset($_GET['since']) ? $conn->real_escape_string($_GET['since']) : date('Y-m-d H:i:s', strtotime('-1 hour'));

            $uploads = $conn->query("
                SELECT rd.*, dr.request_no, dr.project_name, 
                       s.name as supplier_name, dc.name as document_type,
                       (SELECT COUNT(*) FROM request_documents WHERE request_id = dr.id) as total_docs,
                       (SELECT COUNT(*) FROM request_documents WHERE request_id = dr.id AND status = 1) as completed_docs
                FROM request_documents rd
                LEFT JOIN document_requests dr ON rd.request_id = dr.id
                LEFT JOIN supplier_list s ON dr.supplier_id = s.id
                LEFT JOIN document_categories dc ON rd.category_id = dc.id
                WHERE rd.uploaded_at > '{$since}'
                AND rd.status = 1
                ORDER BY rd.uploaded_at DESC
            ");

            $data = [];
            if($uploads && $uploads->num_rows > 0) {
                while($row = $uploads->fetch_assoc()) {
                    // 진행률 계산
                    $row['progress'] = $row['total_docs'] > 0 ? round(($row['completed_docs'] / $row['total_docs']) * 100) : 0;
                    $data[] = $row;
                }
            }

            echo json_encode([
                'status' => 'success',
                'uploads' => $data,
                'count' => count($data)
            ]);
            break;

        default:
            echo json_encode(['status' => 'error', 'msg' => 'Invalid action']);
    }

} catch(Exception $e) {
    // 모든 오류를 JSON으로 반환
    echo json_encode([
        'status' => 'error',
        'msg' => $e->getMessage()
    ]);
}

// 데이터베이스 연결 종료
if(isset($conn)) {
    $conn->close();
}
?>
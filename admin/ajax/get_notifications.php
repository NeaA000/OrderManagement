<?php
// admin/ajax/get_notifications.php
require_once('../../config.php');

// config.php에서 이미 session_start()를 실행했으므로 제거
// session_start(); <- 이 줄 제거!

// 오류 출력 방지
error_reporting(0);
ini_set('display_errors', 0);

// JSON 헤더 설정
header('Content-Type: application/json');

try {
    // 로그인 체크 - 세션 직접 확인
    if(!isset($_SESSION['userdata']) || !isset($_SESSION['userdata']['id'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'msg' => 'Unauthorized']);
        exit;
    }

    $user_id = $_SESSION['userdata']['id'];

    // 데이터베이스 연결 확인
    if(!isset($conn) || !$conn) {
        throw new Exception('Database connection failed');
    }

    $action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : 'get');

    switch($action) {
        case 'get':
            // 클라이언트의 알림 설정 확인
            $notification_enabled = isset($_GET['notification_enabled']) ? $_GET['notification_enabled'] : 'true';

            // 알림이 꺼져있으면 빈 배열 반환
            if($notification_enabled === 'false') {
                echo json_encode([
                    'status' => 'success',
                    'notifications' => [],
                    'count' => 0,
                    'total_unread' => 0,
                    'message' => 'Notifications disabled'
                ]);
                exit;
            }

            // 읽지 않은 알림 가져오기
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            if($limit <= 0 || $limit > 100) $limit = 10;

            // 쿼리 실행
            $query = "
                SELECT n.*, 
                       s.name as supplier_full_name, 
                       s.contact_person,
                       dr.request_no, 
                       dr.project_name
                FROM upload_notifications n
                LEFT JOIN document_requests dr ON n.request_id = dr.id
                LEFT JOIN supplier_list s ON n.supplier_id = s.id
                WHERE n.is_read = 0
                ORDER BY n.uploaded_at DESC
                LIMIT ?
            ";

            $stmt = $conn->prepare($query);
            if(!$stmt) {
                throw new Exception('Query preparation failed');
            }

            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();

            $data = [];
            $count = 0;

            if($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
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
            $stmt->close();

            // 전체 미읽은 알림 수
            $total_stmt = $conn->prepare("SELECT COUNT(*) as count FROM upload_notifications WHERE is_read = 0");
            if($total_stmt) {
                $total_stmt->execute();
                $total_result = $total_stmt->get_result();
                $total_unread = $total_result ? $total_result->fetch_assoc()['count'] : 0;
                $total_stmt->close();
            } else {
                $total_unread = 0;
            }

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
                if($stmt) {
                    $stmt->bind_param("ii", $user_id, $notification_id);
                    $success = $stmt->execute();
                    $stmt->close();

                    if($success) {
                        echo json_encode(['status' => 'success']);
                    } else {
                        echo json_encode(['status' => 'error', 'msg' => 'Failed to update notification']);
                    }
                } else {
                    echo json_encode(['status' => 'error', 'msg' => 'Query preparation failed']);
                }
            } else {
                echo json_encode(['status' => 'error', 'msg' => 'Invalid notification ID']);
            }
            break;

        case 'mark_all_read':
            // 모든 알림을 읽음으로 표시
            $stmt = $conn->prepare("UPDATE upload_notifications SET is_read = 1, read_by = ?, read_at = NOW() WHERE is_read = 0");
            if($stmt) {
                $stmt->bind_param("i", $user_id);
                $success = $stmt->execute();
                $affected = $conn->affected_rows;
                $stmt->close();

                if($success) {
                    echo json_encode(['status' => 'success', 'affected' => $affected]);
                } else {
                    echo json_encode(['status' => 'error', 'msg' => 'Failed to mark notifications as read']);
                }
            } else {
                echo json_encode(['status' => 'error', 'msg' => 'Query preparation failed']);
            }
            break;

        case 'get_recent_uploads':
            // 최근 업로드 현황 (테이블 업데이트용)
            $since = isset($_GET['since']) ? $_GET['since'] : date('Y-m-d H:i:s', strtotime('-1 hour'));

            // SQL 인젝션 방지
            $since = date('Y-m-d H:i:s', strtotime($since));

            $query = "
                SELECT rd.*, 
                       dr.request_no, 
                       dr.project_name, 
                       s.name as supplier_name, 
                       dc.name as document_type,
                       (SELECT COUNT(*) FROM request_documents WHERE request_id = dr.id) as total_docs,
                       (SELECT COUNT(*) FROM request_documents WHERE request_id = dr.id AND status = 1) as completed_docs
                FROM request_documents rd
                LEFT JOIN document_requests dr ON rd.request_id = dr.id
                LEFT JOIN supplier_list s ON dr.supplier_id = s.id
                LEFT JOIN document_categories dc ON rd.category_id = dc.id
                WHERE rd.uploaded_at > ?
                AND rd.status = 1
                ORDER BY rd.uploaded_at DESC
                LIMIT 20
            ";

            $stmt = $conn->prepare($query);
            if($stmt) {
                $stmt->bind_param("s", $since);
                $stmt->execute();
                $result = $stmt->get_result();

                $data = [];
                if($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        // 진행률 계산
                        $row['progress'] = $row['total_docs'] > 0 ?
                            round(($row['completed_docs'] / $row['total_docs']) * 100) : 0;
                        $data[] = $row;
                    }
                }
                $stmt->close();

                echo json_encode([
                    'status' => 'success',
                    'uploads' => $data,
                    'count' => count($data)
                ]);
            } else {
                echo json_encode(['status' => 'error', 'msg' => 'Query preparation failed']);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'msg' => 'Invalid action']);
    }

} catch(Exception $e) {
    // 에러 로깅
    error_log("get_notifications.php error: " . $e->getMessage());

    // 클라이언트에 에러 반환
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'msg' => 'Internal server error'
    ]);
}
?>
<?php
// AdminLTE 환경에서는 이미 config.php가 로드되어 있으므로 $conn을 직접 사용
// 로그인 체크는 AdminLTE에서 처리되므로 생략

// 편집 모드 확인
$is_edit = isset($_GET['id']) && !empty($_GET['id']);
$request_id = $is_edit ? $_GET['id'] : null;

// 편집 모드일 때 기존 데이터 조회
if ($is_edit) {
    // 기본 정보 조회
    $stmt = $conn->prepare("SELECT * FROM document_requests WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request_data = $result->fetch_assoc();

    if (!$request_data) {
        echo "<script>alert('요청을 찾을 수 없습니다.'); location.href='./?page=document_requests';</script>";
        exit;
    }

    // 상세 정보 조회
    $stmt = $conn->prepare("SELECT * FROM document_request_details WHERE request_id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $details_data = $stmt->get_result()->fetch_assoc();

    // 작성 대상 정보 조회
    $stmt = $conn->prepare("SELECT * FROM document_targets WHERE request_id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $targets_data = $stmt->get_result()->fetch_assoc();

    // 비용 정보 조회
    $stmt = $conn->prepare("SELECT * FROM document_cost_details WHERE request_id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $costs_data = $stmt->get_result()->fetch_assoc();

    // 작성자 정보 조회
    $stmt = $conn->prepare("SELECT * FROM document_writers WHERE request_id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $writers_data = $stmt->get_result()->fetch_assoc();

    // 검토 기관 정보 조회
    $stmt = $conn->prepare("SELECT * FROM review_credentials WHERE request_id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $review_data = $stmt->get_result()->fetch_assoc();

    // 선택된 문서 목록 조회
    $stmt = $conn->prepare("SELECT category_id FROM request_documents WHERE request_id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $selected_documents = [];
    while ($row = $result->fetch_assoc()) {
        $selected_documents[] = $row['category_id'];
    }
}

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 트랜잭션 시작
        $conn->begin_transaction();

        if ($is_edit) {
            // UPDATE 모드
            // 1. 기본 정보 업데이트
            $stmt = $conn->prepare("UPDATE document_requests SET 
                request_no = ?, supplier_id = ?, project_name = ?, due_date = ?, 
                additional_notes = ? WHERE id = ?");
            $stmt->bind_param("sisssi",
                $_POST['request_no'],
                $_POST['supplier_id'],
                $_POST['project_name'],
                $_POST['submission_date'],
                $_POST['additional_notes'],
                $request_id
            );
            $stmt->execute();

            // 2. 상세 정보 업데이트
            // total_cost에서 숫자만 추출
            $total_cost = str_replace(['만원', ',', ' ', '(VAT포함)', '(VAT별도)'], '', $_POST['total_cost']);
            
            $stmt = $conn->prepare("UPDATE document_request_details SET
                construction_method = ?, manager_name = ?, manager_contact = ?, 
                manager_email = ?, director_name = ?, director_contact = ?, order_date = ?,
                total_cost = ?, vat_included = ? WHERE request_id = ?");
            $stmt->bind_param("sssssssiis",
                $_POST['construction_method'],
                $_POST['manager_name'],
                $_POST['manager_contact'],
                $_POST['manager_email'],
                $_POST['director_name'],
                $_POST['director_contact'],
                $_POST['order_date'],
                $total_cost,
                isset($_POST['vat_included']) ? $_POST['vat_included'] : 0,
                $request_id
            );
            $stmt->execute();

            // 3. 선택된 서류 업데이트 (기존 삭제 후 새로 추가)
            $stmt = $conn->prepare("DELETE FROM request_documents WHERE request_id = ?");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();

        } else {
            // INSERT 모드
            // 1. document_requests 테이블에 기본 정보 저장
            $upload_token = bin2hex(random_bytes(32));
            $stmt = $conn->prepare("INSERT INTO document_requests (
                request_no, supplier_id, project_name, due_date, 
                additional_notes, upload_token, status, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, 0, ?)");

            $stmt->bind_param("sissssi",
                $_POST['request_no'],
                $_POST['supplier_id'],
                $_POST['project_name'],
                $_POST['submission_date'],
                $_POST['additional_notes'],
                $upload_token,
                $_SESSION['userdata']['id']
            );
            $stmt->execute();
            $request_id = $conn->insert_id;

            // 2. document_request_details 테이블에 상세 정보 저장
            $stmt = $conn->prepare("INSERT INTO document_request_details (
                request_id, construction_method, manager_name, manager_contact, 
                manager_email, director_name, director_contact, order_date,
                total_cost, vat_included
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $total_cost = str_replace(['만원', ',', ' ', '(VAT포함)', '(VAT별도)'], '', $_POST['total_cost']);
            $vat_included = isset($_POST['vat_included']) ? $_POST['vat_included'] : 0;

            $stmt->bind_param("isssssssii",
                $request_id,
                $_POST['construction_method'],
                $_POST['manager_name'],
                $_POST['manager_contact'],
                $_POST['manager_email'],
                $_POST['director_name'],
                $_POST['director_contact'],
                $_POST['order_date'],
                $total_cost,
                $vat_included
            );
            $stmt->execute();
        }

        // 3. 선택된 서류들을 request_documents 테이블에 저장
        if (isset($_POST['selected_documents'])) {
            $stmt = $conn->prepare("INSERT INTO request_documents (
                request_id, category_id, document_name, is_required, status
            ) VALUES (?, ?, ?, 1, 'pending')");

            foreach (array_unique($_POST['selected_documents']) as $doc_id) {
                // 문서 정보 조회
                $doc_stmt = $conn->prepare("SELECT id, name FROM document_categories WHERE id = ?");
                $doc_stmt->bind_param("i", $doc_id);
                $doc_stmt->execute();
                $doc_result = $doc_stmt->get_result();
                $doc_info = $doc_result->fetch_assoc();

                if ($doc_info) {
                    $stmt->bind_param("iis", $request_id, $doc_id, $doc_info['name']);
                    $stmt->execute();
                }
            }
        }

        // 나머지 테이블들도 UPDATE/INSERT 처리
        if ($is_edit) {
            // 작성 대상 정보 업데이트
            $stmt = $conn->prepare("UPDATE document_targets SET
                safety_plan_type = ?, review_agency = ?, hazard_prevention_type = ?,
                safety_health_agency = ?, safety_health_ledger_type = ?, evaluation_type = ?,
                education_facility = ?, education_office = ?, railway_protection = ?, 
                railway_manager = ? WHERE request_id = ?");
            $stmt->bind_param("ssssssssssi",
                $_POST['safety_plan_type'],
                $_POST['review_agency'],
                $_POST['hazard_prevention_type'],
                $_POST['safety_health_agency'],
                $_POST['safety_health_ledger_type'],
                $_POST['evaluation_type'],
                $_POST['education_facility'],
                $_POST['education_office'],
                $_POST['railway_protection'],
                $_POST['railway_manager'],
                $request_id
            );
            $stmt->execute();

            // 비용 정보 업데이트
            $stmt = $conn->prepare("UPDATE document_cost_details SET
                safety_plan_cost = ?, hazard_prevention_cost = ?, structure_review_cost = ?,
                structure_review_agency = ?, plan_review_cost = ?, plan_review_agency = ?,
                safety_health_cost = ?, education_facility_cost = ?, railway_protection_cost = ?,
                evaluation_cost = ? WHERE request_id = ?");

            // 비용 필드 처리
            $costs = [];
            $cost_fields = ['safety_plan_cost', 'hazard_prevention_cost', 'structure_review_cost',
                'plan_review_cost', 'safety_health_cost', 'education_facility_cost',
                'railway_protection_cost'];
            foreach ($cost_fields as $field) {
                $costs[] = !empty($_POST[$field]) ? str_replace(['만원', ',', ' '], '', $_POST[$field]) : null;
            }

            $stmt->bind_param("iiisississi",
                $costs[0], $costs[1], $costs[2],
                $_POST['structure_review_agency'],
                $costs[3],
                $_POST['plan_review_agency'],
                $costs[4], $costs[5], $costs[6],
                $_POST['evaluation_cost'],  // 문자열로 처리
                $request_id
            );
            $stmt->execute();

            // 작성자 정보 업데이트
            $stmt = $conn->prepare("UPDATE document_writers SET
                main_writer = ?, revenue_manager = ?, field_writers = ?
                WHERE request_id = ?");
            $stmt->bind_param("sssi",
                $_POST['main_writer'],
                $_POST['revenue_manager'],
                $_POST['field_writers'],
                $request_id
            );
            $stmt->execute();

        } else {
            // INSERT 모드일 때 나머지 테이블들도 추가
            // 4. 작성 대상 정보 저장
            $stmt = $conn->prepare("INSERT INTO document_targets (
                request_id, safety_plan_type, review_agency, hazard_prevention_type,
                safety_health_agency, safety_health_ledger_type, evaluation_type,
                education_facility, education_office, railway_protection, railway_manager
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->bind_param("issssssssss",
                $request_id,
                $_POST['safety_plan_type'],
                $_POST['review_agency'],
                $_POST['hazard_prevention_type'],
                $_POST['safety_health_agency'],
                $_POST['safety_health_ledger_type'],
                $_POST['evaluation_type'],
                $_POST['education_facility'],
                $_POST['education_office'],
                $_POST['railway_protection'],
                $_POST['railway_manager']
            );
            $stmt->execute();

            // 5. 비용 정보 저장
            $stmt = $conn->prepare("INSERT INTO document_cost_details (
                request_id, safety_plan_cost, hazard_prevention_cost, structure_review_cost,
                structure_review_agency, plan_review_cost, plan_review_agency,
                safety_health_cost, education_facility_cost, railway_protection_cost,
                evaluation_cost
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            // 비용 필드 처리
            $costs = [];
            $cost_fields = ['safety_plan_cost', 'hazard_prevention_cost', 'structure_review_cost',
                'plan_review_cost', 'safety_health_cost', 'education_facility_cost',
                'railway_protection_cost'];
            foreach ($cost_fields as $field) {
                $costs[] = !empty($_POST[$field]) ? str_replace(['만원', ',', ' '], '', $_POST[$field]) : null;
            }

            $stmt->bind_param("iiiisississ",
                $request_id,
                $costs[0], $costs[1], $costs[2],
                $_POST['structure_review_agency'],
                $costs[3],
                $_POST['plan_review_agency'],
                $costs[4], $costs[5], $costs[6],
                $_POST['evaluation_cost']  // 문자열로 처리
            );
            $stmt->execute();

            // 6. 작성자 정보 저장
            $stmt = $conn->prepare("INSERT INTO document_writers (
                request_id, main_writer, revenue_manager, field_writers
            ) VALUES (?, ?, ?, ?)");

            $stmt->bind_param("isss",
                $request_id,
                $_POST['main_writer'],
                $_POST['revenue_manager'],
                $_POST['field_writers']
            );
            $stmt->execute();

            // 7. 워크플로우 상태 초기화
            $stmt = $conn->prepare("INSERT INTO workflow_status (
                request_id, current_step, step_name, step_description, 
                started_at, assigned_to, is_current
            ) VALUES (?, 'created', '요청 생성', '서류 요청이 생성되었습니다.', NOW(), ?, 1)");

            $stmt->bind_param("ii", $request_id, $_SESSION['userdata']['id']);
            $stmt->execute();
        }

        // 8. 검토 기관 로그인 정보 저장/업데이트
        if (!empty($_POST['csi_id']) || !empty($_POST['kosha_id'])) {
            if ($is_edit) {
                // 기존 데이터가 있는지 확인
                $check_stmt = $conn->prepare("SELECT id FROM review_credentials WHERE request_id = ?");
                $check_stmt->bind_param("i", $request_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows > 0) {
                    // UPDATE
                    $stmt = $conn->prepare("UPDATE review_credentials SET
                        csi_id = ?, csi_password = ?, csi_supervisor = ?, csi_supervisor_info = ?,
                        csi_client = ?, csi_client_info = ?,
                        kosha_id = ?, kosha_password = ?, kosha_notes = ?
                        WHERE request_id = ?");

                    // 비밀번호는 입력된 경우에만 업데이트
                    $csi_password = !empty($_POST['csi_password']) ? password_hash($_POST['csi_password'], PASSWORD_DEFAULT) :
                        ($review_data['csi_password'] ?? null);
                    $kosha_password = !empty($_POST['kosha_password']) ? password_hash($_POST['kosha_password'], PASSWORD_DEFAULT) :
                        ($review_data['kosha_password'] ?? null);

                    $stmt->bind_param("sssssssssi",
                        $_POST['csi_id'],
                        $csi_password,
                        $_POST['csi_supervisor'],
                        $_POST['csi_supervisor_info'],
                        $_POST['csi_client'],
                        $_POST['csi_client_info'],
                        $_POST['kosha_id'],
                        $kosha_password,
                        $_POST['kosha_notes'],
                        $request_id
                    );
                    $stmt->execute();
                } else {
                    // INSERT
                    $stmt = $conn->prepare("INSERT INTO review_credentials (
                        request_id, csi_id, csi_password, csi_supervisor, csi_supervisor_info,
                        csi_client, csi_client_info, kosha_id, kosha_password, kosha_notes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                    $stmt->bind_param("isssssssss",
                        $request_id,
                        $_POST['csi_id'],
                        !empty($_POST['csi_password']) ? password_hash($_POST['csi_password'], PASSWORD_DEFAULT) : null,
                        $_POST['csi_supervisor'],
                        $_POST['csi_supervisor_info'],
                        $_POST['csi_client'],
                        $_POST['csi_client_info'],
                        $_POST['kosha_id'],
                        !empty($_POST['kosha_password']) ? password_hash($_POST['kosha_password'], PASSWORD_DEFAULT) : null,
                        $_POST['kosha_notes']
                    );
                    $stmt->execute();
                }
            } else {
                // INSERT
                $stmt = $conn->prepare("INSERT INTO review_credentials (
                    request_id, csi_id, csi_password, csi_supervisor, csi_supervisor_info,
                    csi_client, csi_client_info, kosha_id, kosha_password, kosha_notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->bind_param("isssssssss",
                    $request_id,
                    $_POST['csi_id'],
                    !empty($_POST['csi_password']) ? password_hash($_POST['csi_password'], PASSWORD_DEFAULT) : null,
                    $_POST['csi_supervisor'],
                    $_POST['csi_supervisor_info'],
                    $_POST['csi_client'],
                    $_POST['csi_client_info'],
                    $_POST['kosha_id'],
                    !empty($_POST['kosha_password']) ? password_hash($_POST['kosha_password'], PASSWORD_DEFAULT) : null,
                    $_POST['kosha_notes']
                );
                $stmt->execute();
            }
        }

        $conn->commit();

        if ($is_edit) {
            $success_message = "서류 요청이 성공적으로 수정되었습니다.";
        } else {
            $success_message = "서류 요청이 성공적으로 저장되었습니다. (요청번호: {$_POST['request_no']})";
        }

    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "저장 중 오류가 발생했습니다: " . $e->getMessage();
    }
}

// 요청번호 자동 생성 (신규일 때만)
if (!$is_edit) {
    $date = date('Ymd');
    $stmt = $conn->query("SELECT COUNT(*) + 1 as cnt FROM document_requests WHERE request_no LIKE 'REQ-$date-%'");
    $count = str_pad($stmt->fetch_assoc()['cnt'], 3, '0', STR_PAD_LEFT);
    $auto_request_no = "REQ-$date-$count";
} else {
    $auto_request_no = $request_data['request_no'];
}

// 의뢰처 목록 조회
$suppliers = [];
$result = $conn->query("SELECT id, name FROM supplier_list WHERE status = 1 ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $suppliers[$row['id']] = $row['name'];
}

// 서류 카테고리 계층 구조로 조회
function getCategoryTree($conn, $parent_id = null) {
    $sql = "SELECT * FROM document_categories WHERE status = 1";
    if ($parent_id === null) {
        $sql .= " AND parent_id IS NULL";
    } else {
        $sql .= " AND parent_id = " . $parent_id;
    }
    $sql .= " ORDER BY display_order, name";

    $result = $conn->query($sql);
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $row['children'] = getCategoryTree($conn, $row['id']);
        $categories[] = $row;
    }

    return $categories;
}

$categories = [];
$result = $conn->query("SELECT id, name FROM document_categories WHERE level = 1 AND status = 1 ORDER BY display_order");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}
$categoryTree = getCategoryTree($conn);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>서류 요청 <?php echo $is_edit ? '수정' : '등록' ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Malgun Gothic', sans-serif;
            font-size: 14px;
            background-color: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 8px;
        }

        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-size: 24px;
        }

        /* 알림 메시지 */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: bold;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* 체크박스 스타일 */
        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-right: 8px;
            cursor: pointer;
            vertical-align: middle;
        }

        label {
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            margin-right: 15px;
        }

        /* 서류 분류 섹션 */
        .document-types {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            padding: 20px;
            background-color: #f9f9f9;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            margin-bottom: 30px;
        }

        .document-types label:hover {
            color: #d32f2f;
        }

        /* 테이블 스타일 */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
            color: #333;
        }

        /* 입력 필드 */
        input[type="text"],
        input[type="date"],
        input[type="password"],
        input[type="email"],
        select,
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
        }

        input[type="text"]:focus,
        input[type="date"]:focus,
        input[type="password"]:focus,
        input[type="email"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #4285f4;
            box-shadow: 0 0 0 2px rgba(66, 133, 244, 0.1);
        }

        /* 섹션 타이틀 */
        .section-title {
            background-color: #e8e8e8;
            padding: 12px 20px;
            margin-bottom: 15px;
            font-weight: bold;
            font-size: 16px;
            border-left: 4px solid #d32f2f;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* 버튼 스타일 */
        .button-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 30px;
        }

        button {
            padding: 12px 30px;
            font-size: 16px;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: #d32f2f;
            color: white;
        }

        .btn-primary:hover {
            background-color: #b71c1c;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #545b62;
        }

        /* 선택된 서류 목록 */
        .selected-documents {
            margin-top: 20px;
            padding: 20px;
            background-color: #e3f2fd;
            border: 2px solid #2196f3;
            border-radius: 5px;
        }

        .selected-documents h3 {
            color: #1976d2;
            margin-bottom: 10px;
        }

        .selected-list {
            list-style-type: none;
            padding-left: 20px;
        }

        .selected-list li:before {
            content: "✓ ";
            color: #2196f3;
            font-weight: bold;
            margin-right: 5px;
        }

        /* 반응형 디자인 */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 5px;
            }

            .document-types {
                grid-template-columns: 1fr;
            }
        }

        /* 인쇄 스타일 */
        @media print {
            body {
                background-color: white;
            }

            .container {
                box-shadow: none;
                padding: 0;
            }

            .button-group {
                display: none;
            }
        }

        /* 모달 팝업 스타일 */
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: #fefefe;
            padding: 0;
            border: 1px solid #888;
            width: 90%;
            max-width: 900px;
            max-height: 85vh;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            margin: 20px;
        }

        .modal-header {
            padding: 15px 20px;
            background-color: #f0f0f0;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 8px 8px 0 0;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 18px;
        }

        .modal-body {
            padding: 20px;
            overflow-y: auto;
            flex: 1;
        }

        .modal-footer {
            padding: 15px 20px;
            background-color: #f0f0f0;
            border-top: 1px solid #ddd;
            text-align: right;
            border-radius: 0 0 8px 8px;
        }

        .close {
            color: #aaa;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            line-height: 20px;
        }

        .close:hover,
        .close:focus {
            color: #000;
        }

        /* 트리 구조 스타일 */
        .tree-view {
            font-family: 'Malgun Gothic', sans-serif;
            user-select: none;
        }

        .tree-item {
            margin: 2px 0;
        }

        .tree-folder {
            font-weight: bold;
            padding: 8px;
            border-radius: 3px;
            cursor: pointer;
            background-color: #f5f5f5;
            border: 1px solid #e0e0e0;
            margin: 3px 0;
        }

        .tree-folder:hover {
            background-color: #e8e8e8;
        }

        .tree-folder::before {
            content: "📁 ";
            margin-right: 5px;
        }

        .folder-icon {
            display: inline-block;
            width: 20px;
            transition: transform 0.2s;
        }

        .folder-icon.open {
            transform: rotate(90deg);
        }

        .tree-document {
            padding: 6px 6px 6px 35px;
            cursor: pointer;
            border-radius: 3px;
        }

        .tree-document:hover {
            background-color: #e3f2fd;
        }

        .tree-document::before {
            content: "📄 ";
            margin-right: 5px;
        }

        .tree-children {
            margin-left: 20px;
            border-left: 1px dotted #ccc;
            padding-left: 10px;
            display: none;
        }

        .tree-children.show {
            display: block;
        }

        .selected-info {
            margin-top: 15px;
            padding: 10px;
            background-color: #e3f2fd;
            border-radius: 4px;
            font-size: 13px;
            color: #1976d2;
        }

        .btn-modal {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-left: 5px;
        }

        .btn-modal-primary {
            background-color: #d32f2f;
            color: white;
        }

        .btn-modal-secondary {
            background-color: #6c757d;
            color: white;
        }

        /* 숨겨진 체크박스들 */
        .hidden-documents {
            display: none;
        }
    </style>
</head>
<body>
<div class="container">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 style="margin: 0;">서류 요청 <?php echo $is_edit ? '수정' : '등록' ?></h1>
        <button type="button" class="btn-secondary" onclick="goBack()" style="padding: 10px 20px;">
            <span style="margin-right: 5px;">←</span>
            목록으로
        </button>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
        <script>
            // 성공 메시지 표시 후 1.5초 뒤에 목록 페이지로 이동
            setTimeout(function() {
                window.location.href = './?page=document_requests';
            }, 1500);
        </script>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-error"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <!-- 서류 분류 체크박스 -->
        <div class="section-title">서류 분류 선택</div>
        <div class="document-types">
            <?php foreach ($categories as $category): ?>
                <label>
                    <input type="checkbox" name="document_types[]" value="<?php echo $category['id']; ?>" data-category-id="<?php echo $category['id']; ?>" class="main-category-checkbox">
                    <?php echo htmlspecialchars($category['name']); ?>
                </label>
            <?php endforeach; ?>
        </div>

        <!-- 숨겨진 문서 체크박스들 (실제 폼 제출용) -->
        <div class="hidden-documents" id="hidden-documents">
            <?php
            function renderHiddenDocuments($categories, $parentPath = '') {
                foreach ($categories as $category) {
                    $currentPath = $parentPath . '/' . $category['name'];
                    if (empty($category['children'])) {
                        // 최하위 노드(문서)만 체크박스 생성
                        echo '<input type="checkbox" name="selected_documents[]" value="' . $category['id'] . '" id="doc-' . $category['id'] . '" data-path="' . htmlspecialchars($currentPath) . '">';
                    } else {
                        renderHiddenDocuments($category['children'], $currentPath);
                    }
                }
            }

            foreach ($categoryTree as $topCategory) {
                renderHiddenDocuments($topCategory['children'], $topCategory['name']);
            }
            ?>
        </div>

        <!-- 관리번호 -->
        <div class="section-title">
            관리번호: <input type="text" name="request_no" value="<?php echo $auto_request_no; ?>" style="width: 200px; margin-left: 10px;" required>
        </div>

        <!-- 의뢰처 -->
        <div class="section-title">의뢰처</div>

        <table>
            <tr>
                <th width="15%">공사명</th>
                <td colspan="2"><input type="text" name="project_name" value="<?php echo $is_edit ? htmlspecialchars($request_data['project_name']) : '' ?>" required></td>
                <th width="15%">시공방법</th>
                <td width="25%">
                    <select name="construction_method">
                        <option value="">선택하세요</option>
                        <option value="단독" <?php echo ($is_edit && $details_data['construction_method'] == '단독') ? 'selected' : '' ?>>단독</option>
                        <option value="공동" <?php echo ($is_edit && $details_data['construction_method'] == '공동') ? 'selected' : '' ?>>공동</option>
                        <option value="분담" <?php echo ($is_edit && $details_data['construction_method'] == '분담') ? 'selected' : '' ?>>분담</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th>회사명</th>
                <td colspan="2">
                    <select name="supplier_id" required>
                        <option value="">선택하세요</option>
                        <?php foreach ($suppliers as $id => $name): ?>
                            <option value="<?php echo $id; ?>" <?php echo ($is_edit && $request_data['supplier_id'] == $id) ? 'selected' : '' ?>><?php echo htmlspecialchars($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <th>수주일</th>
                <td><input type="date" name="order_date" value="<?php echo $is_edit && $details_data['order_date'] ? date('Y-m-d', strtotime($details_data['order_date'])) : '' ?>"></td>
            </tr>
            <tr>
                <th rowspan="2">담당자</th>
                <th width="10%">담당</th>
                <td>
                    <input type="text" name="manager_name" placeholder="담당자 이름" style="width: 40%; display: inline;" value="<?php echo $is_edit ? htmlspecialchars($details_data['manager_name'] ?? '') : '' ?>">
                    <input type="text" name="manager_contact" placeholder="연락처" style="width: 40%; display: inline; margin-left: 10px;" value="<?php echo $is_edit ? htmlspecialchars($details_data['manager_contact'] ?? '') : '' ?>">
                </td>
                <th>이메일</th>
                <td><input type="email" name="manager_email" placeholder="이메일 주소" value="<?php echo $is_edit ? htmlspecialchars($details_data['manager_email'] ?? '') : '' ?>"></td>
            </tr>
            <tr>
                <th>소장</th>
                <td>
                    <input type="text" name="director_name" placeholder="소장 이름" style="width: 40%; display: inline;" value="<?php echo $is_edit ? htmlspecialchars($details_data['director_name'] ?? '') : '' ?>">
                    <input type="text" name="director_contact" placeholder="연락처" style="width: 40%; display: inline; margin-left: 10px;" value="<?php echo $is_edit ? htmlspecialchars($details_data['director_contact'] ?? '') : '' ?>">
                </td>
                <th>제출예정일</th>
                <td><input type="date" name="submission_date" value="<?php echo $is_edit && $request_data['due_date'] ? date('Y-m-d', strtotime($request_data['due_date'])) : '' ?>"></td>
            </tr>
        </table>

        <!-- 작성 대상 -->
        <div class="section-title">작성 대상</div>

        <table>
            <tr>
                <th width="20%">안전관리계획서</th>
                <td width="35%">
                    <select name="safety_plan_type">
                        <option value="">선택 안함</option>
                        <option value="1종" <?php echo ($is_edit && isset($targets_data['safety_plan_type']) && $targets_data['safety_plan_type'] == '1종') ? 'selected' : '' ?>>1종</option>
                        <option value="2종" <?php echo ($is_edit && isset($targets_data['safety_plan_type']) && $targets_data['safety_plan_type'] == '2종') ? 'selected' : '' ?>>2종</option>
                        <option value="기타" <?php echo ($is_edit && isset($targets_data['safety_plan_type']) && $targets_data['safety_plan_type'] == '기타') ? 'selected' : '' ?>>기타(천공기, 동바리)</option>
                    </select>
                </td>
                <th width="15%">검토처</th>
                <td>
                    <select name="review_agency">
                        <option value="">선택 안함</option>
                        <option value="국토안전관리원" <?php echo ($is_edit && isset($targets_data['review_agency']) && $targets_data['review_agency'] == '국토안전관리원') ? 'selected' : '' ?>>국토안전관리원</option>
                        <option value="안전점검기관" <?php echo ($is_edit && isset($targets_data['review_agency']) && $targets_data['review_agency'] == '안전점검기관') ? 'selected' : '' ?>>안전점검기관</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th>유해위험방지계획서</th>
                <td>
                    <select name="hazard_prevention_type">
                        <option value="">선택 안함</option>
                        <option value="높이31m이상" <?php echo ($is_edit && isset($targets_data['hazard_prevention_type']) && $targets_data['hazard_prevention_type'] == '높이31m이상') ? 'selected' : '' ?>>높이 31m 이상</option>
                        <option value="굴착10m이상" <?php echo ($is_edit && isset($targets_data['hazard_prevention_type']) && $targets_data['hazard_prevention_type'] == '굴착10m이상') ? 'selected' : '' ?>>굴착 10m 이상</option>
                        <option value="교량" <?php echo ($is_edit && isset($targets_data['hazard_prevention_type']) && $targets_data['hazard_prevention_type'] == '교량') ? 'selected' : '' ?>>교량</option>
                        <option value="기타" <?php echo ($is_edit && isset($targets_data['hazard_prevention_type']) && $targets_data['hazard_prevention_type'] == '기타') ? 'selected' : '' ?>>기타</option>
                    </select>
                </td>
                <th>안전보건공단</th>
                <td><input type="text" name="safety_health_agency" placeholder="강원동부지사" value="<?php echo $is_edit ? htmlspecialchars($targets_data['safety_health_agency'] ?? '') : '' ?>"></td>
            </tr>
            <tr>
                <th>안전보건대장</th>
                <td>
                    <select name="safety_health_ledger_type">
                        <option value="">선택 안함</option>
                        <option value="기본" <?php echo ($is_edit && isset($targets_data['safety_health_ledger_type']) && $targets_data['safety_health_ledger_type'] == '기본') ? 'selected' : '' ?>>기본</option>
                        <option value="설계" <?php echo ($is_edit && isset($targets_data['safety_health_ledger_type']) && $targets_data['safety_health_ledger_type'] == '설계') ? 'selected' : '' ?>>설계</option>
                        <option value="공사" <?php echo ($is_edit && isset($targets_data['safety_health_ledger_type']) && $targets_data['safety_health_ledger_type'] == '공사') ? 'selected' : '' ?>>공사</option>
                    </select>
                </td>
                <th>적정성평가</th>
                <td>
                    <select name="evaluation_type">
                        <option value="">선택 안함</option>
                        <option value="기본" <?php echo ($is_edit && isset($targets_data['evaluation_type']) && $targets_data['evaluation_type'] == '기본') ? 'selected' : '' ?>>기본</option>
                        <option value="설계" <?php echo ($is_edit && isset($targets_data['evaluation_type']) && $targets_data['evaluation_type'] == '설계') ? 'selected' : '' ?>>설계</option>
                        <option value="공사" <?php echo ($is_edit && isset($targets_data['evaluation_type']) && $targets_data['evaluation_type'] == '공사') ? 'selected' : '' ?>>공사</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th>교육시설</th>
                <td><input type="text" name="education_facility" placeholder="교육시설 정보 입력" value="<?php echo $is_edit ? htmlspecialchars($targets_data['education_facility'] ?? '') : '' ?>"></td>
                <th>관할교육청</th>
                <td><input type="text" name="education_office" placeholder="관할교육청 입력" value="<?php echo $is_edit ? htmlspecialchars($targets_data['education_office'] ?? '') : '' ?>"></td>
            </tr>
            <tr>
                <th>철도보호지구</th>
                <td><input type="text" name="railway_protection" placeholder="철도보호지구 관련정보" value="<?php echo $is_edit ? htmlspecialchars($targets_data['railway_protection'] ?? '') : '' ?>"></td>
                <th>철도보호지구관리자</th>
                <td><input type="text" name="railway_manager" placeholder="철도보호지구관리자 입력" value="<?php echo $is_edit ? htmlspecialchars($targets_data['railway_manager'] ?? '') : '' ?>"></td>
            </tr>
        </table>

        <!-- 작성 비용 -->
        <div class="section-title">작성 비용 (VAT 별도)</div>

        <table>
            <tr>
                <th width="20%">안전관리계획서</th>
                <td width="30%"><input type="text" name="safety_plan_cost" placeholder="안전관리계획비 입력(만원)" class="cost-input" value="<?php echo $is_edit && isset($costs_data['safety_plan_cost']) ? $costs_data['safety_plan_cost'] : '' ?>"></td>
                <th width="20%">유해위험방지계획서</th>
                <td width="30%"><input type="text" name="hazard_prevention_cost" placeholder="유해위험방지계획비 입력(만원)" class="cost-input" value="<?php echo $is_edit && isset($costs_data['hazard_prevention_cost']) ? $costs_data['hazard_prevention_cost'] : '' ?>"></td>
            </tr>
            <tr>
                <th>구조검토비</th>
                <td><input type="text" name="structure_review_cost" placeholder="구조검토비 입력(만원)" class="cost-input" value="<?php echo $is_edit && isset($costs_data['structure_review_cost']) ? $costs_data['structure_review_cost'] : '' ?>"></td>
                <th>위탁처</th>
                <td><input type="text" name="structure_review_agency" placeholder="위탁처 입력" value="<?php echo $is_edit ? htmlspecialchars($costs_data['structure_review_agency'] ?? '') : '' ?>"></td>
            </tr>
            <tr>
                <th>계획서검토비</th>
                <td><input type="text" name="plan_review_cost" placeholder="계획서검토비 입력(만원)" class="cost-input" value="<?php echo $is_edit && isset($costs_data['plan_review_cost']) ? $costs_data['plan_review_cost'] : '' ?>"></td>
                <th>검토처</th>
                <td><input type="text" name="plan_review_agency" placeholder="검토처 입력" value="<?php echo $is_edit ? htmlspecialchars($costs_data['plan_review_agency'] ?? '') : '' ?>"></td>
            </tr>
            <tr>
                <th>안전보건대장</th>
                <td><input type="text" name="safety_health_cost" placeholder="안전관리계획비 입력(만원)" class="cost-input" value="<?php echo $is_edit && isset($costs_data['safety_health_cost']) ? $costs_data['safety_health_cost'] : '' ?>"></td>
                <th>교육시설</th>
                <td><input type="text" name="education_facility_cost" placeholder="교육시설 비용 입력(만원)" class="cost-input" value="<?php echo $is_edit && isset($costs_data['education_facility_cost']) ? $costs_data['education_facility_cost'] : '' ?>"></td>
            </tr>
            <tr>
                <th>철도보호</th>
                <td><input type="text" name="railway_protection_cost" placeholder="철도보호 비용 입력(만원)" class="cost-input" value="<?php echo $is_edit && isset($costs_data['railway_protection_cost']) ? $costs_data['railway_protection_cost'] : '' ?>"></td>
                <th>적정성평가</th>
                <td><input type="text" name="evaluation_cost" placeholder="적정성평가 정보 입력" value="<?php echo $is_edit ? htmlspecialchars($costs_data['evaluation_cost'] ?? '') : '' ?>"></td>
            </tr>
            <tr>
                <th>종합계</th>
                <td colspan="3">
                    <input type="text" name="total_cost" id="total_cost" placeholder="0만원" style="width: 200px; display: inline;" readonly required value="<?php echo $is_edit && isset($details_data['total_cost']) ? $details_data['total_cost'] : '' ?>">
                    <label style="margin-left: 20px;">
                        <input type="radio" name="vat_included" value="1" onchange="calculateTotalCost()" <?php echo ($is_edit && isset($details_data['vat_included']) && $details_data['vat_included'] == 1) ? 'checked' : '' ?>> VAT 포함
                    </label>
                    <label style="margin-left: 20px;">
                        <input type="radio" name="vat_included" value="0" <?php echo (!$is_edit || !isset($details_data['vat_included']) || $details_data['vat_included'] == 0) ? 'checked' : '' ?> onchange="calculateTotalCost()"> VAT 별도
                    </label>
                </td>
            </tr>
        </table>

        <!-- 작성자 -->
        <div class="section-title">작성자</div>

        <table>
            <tr>
                <th width="20%">주관자(의뢰처접담)</th>
                <td width="30%"><input type="text" name="main_writer" value="<?php echo $is_edit ? htmlspecialchars($writers_data['main_writer'] ?? '') : '' ?>"></td>
                <th width="20%">수주관리/소개/수당</th>
                <td width="30%"><input type="text" name="revenue_manager" value="<?php echo $is_edit ? htmlspecialchars($writers_data['revenue_manager'] ?? '') : '' ?>"></td>
            </tr>
            <tr>
                <th>분야별 작성자</th>
                <td colspan="3">
                    <input type="text" name="field_writers" placeholder="※ 정병 구분하여 작성시 기재" value="<?php echo $is_edit ? htmlspecialchars($writers_data['field_writers'] ?? '') : '' ?>">
                </td>
            </tr>
        </table>

        <!-- 검토 접수 관련 사항 -->
        <div class="section-title">검토 접수 관련 사항 (대행업무시 필요)</div>

        <table>
            <tr>
                <th width="15%">구분</th>
                <th width="28%">사업자</th>
                <th width="28%">감리단</th>
                <th width="29%">발주처</th>
            </tr>
            <tr>
                <th rowspan="2">CSI<br>(국토안전관리원)</th>
                <td>
                    <table style="width: 100%; border: none; margin: 0;">
                        <tr>
                            <td style="border: none; padding: 3px; width: 15%; text-align: center;">ID</td>
                            <td style="border: 1px solid #ddd; padding: 3px;"><input type="text" name="csi_id" placeholder="아이디" style="width: 95%; border: none;" autocomplete="off" value="<?php echo $is_edit && isset($review_data['csi_id']) ? htmlspecialchars($review_data['csi_id']) : '' ?>"></td>
                        </tr>
                    </table>
                </td>
                <td><input type="text" name="csi_supervisor" placeholder="" value="<?php echo $is_edit && isset($review_data['csi_supervisor']) ? htmlspecialchars($review_data['csi_supervisor']) : '' ?>"></td>
                <td><input type="text" name="csi_client" placeholder="" value="<?php echo $is_edit && isset($review_data['csi_client']) ? htmlspecialchars($review_data['csi_client']) : '' ?>"></td>
            </tr>
            <tr>
                <td>
                    <table style="width: 100%; border: none; margin: 0;">
                        <tr>
                            <td style="border: none; padding: 3px; width: 15%; text-align: center;">비번</td>
                            <td style="border: 1px solid #ddd; padding: 3px;"><input type="password" name="csi_password" placeholder="<?php echo $is_edit ? '변경시에만 입력' : '비밀번호' ?>" style="width: 95%; border: none;" autocomplete="new-password"></td>
                        </tr>
                    </table>
                </td>
                <td><input type="text" name="csi_supervisor_info" placeholder="" value="<?php echo $is_edit && isset($review_data['csi_supervisor_info']) ? htmlspecialchars($review_data['csi_supervisor_info']) : '' ?>"></td>
                <td><input type="text" name="csi_client_info" placeholder="" value="<?php echo $is_edit && isset($review_data['csi_client_info']) ? htmlspecialchars($review_data['csi_client_info']) : '' ?>"></td>
            </tr>
            <tr>
                <th>안전보건공단</th>
                <td>
                    <table style="width: 100%; border: none; margin: 0;">
                        <tr>
                            <td style="border: none; padding: 3px; width: 15%; text-align: center;">ID</td>
                            <td style="border: 1px solid #ddd; padding: 3px;"><input type="text" name="kosha_id" placeholder="아이디" style="width: 95%; border: none;" autocomplete="off" value="<?php echo $is_edit && isset($review_data['kosha_id']) ? htmlspecialchars($review_data['kosha_id']) : '' ?>"></td>
                        </tr>
                    </table>
                </td>
                <td>
                    <table style="width: 100%; border: none; margin: 0;">
                        <tr>
                            <td style="border: none; padding: 3px; width: 15%; text-align: center;">비번</td>
                            <td style="border: 1px solid #ddd; padding: 3px;"><input type="password" name="kosha_password" placeholder="<?php echo $is_edit ? '변경시에만 입력' : '비밀번호' ?>" style="width: 95%; border: none;" autocomplete="new-password"></td>
                        </tr>
                    </table>
                </td>
                <td>
                    <table style="width: 100%; border: none; margin: 0;">
                        <tr>
                            <td style="border: none; padding: 3px; width: 25%; text-align: center;">기타사항</td>
                            <td style="border: 1px solid #ddd; padding: 3px;"><input type="text" name="kosha_notes" placeholder="" style="width: 95%; border: none;" value="<?php echo $is_edit && isset($review_data['kosha_notes']) ? htmlspecialchars($review_data['kosha_notes']) : '' ?>"></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- 추가 요청사항 -->
        <div class="section-title">추가 요청사항</div>
        <textarea name="additional_notes" rows="4" placeholder="추가로 요청할 사항이 있으시면 입력해주세요."><?php echo $is_edit ? htmlspecialchars($request_data['additional_notes'] ?? '') : '' ?></textarea>

        <!-- 선택된 서류 목록 -->
        <div class="selected-documents" id="selected-docs-container" style="display: none;">
            <h3>선택된 서류 목록</h3>
            <ul class="selected-list" id="selected-docs-list"></ul>
        </div>

        <!-- 버튼 그룹 -->
        <div class="button-group">
            <button type="submit" class="btn-primary"><?php echo $is_edit ? '수정하기' : '저장하기' ?></button>
            <button type="reset" class="btn-secondary">초기화</button>
            <button type="button" class="btn-secondary" onclick="window.print()">인쇄하기</button>
        </div>
    </form>
</div>

<!-- 서류 선택 모달 -->
<div id="documentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modal-title">서류 선택</h3>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div style="margin-bottom: 15px;">
                <button type="button" onclick="expandAll()" style="padding: 5px 10px; margin-right: 10px;">모두 펼치기</button>
                <button type="button" onclick="collapseAll()" style="padding: 5px 10px; margin-right: 10px;">모두 접기</button>
                <button type="button" onclick="selectAllDocuments()" style="padding: 5px 10px; margin-right: 10px;">모든 문서 선택</button>
                <button type="button" onclick="deselectAllDocuments()" style="padding: 5px 10px;">모든 문서 해제</button>
            </div>
            <div id="tree-container" class="tree-view">
                <!-- 트리 구조가 여기에 동적으로 생성됨 -->
            </div>
            <div class="selected-info">
                <strong>선택된 문서:</strong> <span id="selected-count">0</span>개
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-modal btn-modal-primary" onclick="applySelection()">선택 완료</button>
            <button type="button" class="btn-modal btn-modal-secondary" onclick="closeModal()">취소</button>
        </div>
    </div>
</div>

<script>
    // 전역 변수
    let currentParentCheckbox = null;
    let categoryData = <?php echo json_encode($categoryTree); ?>;
    let selectedDocumentsByCategory = {}; // 카테고리별 선택 문서 저장
    let allSelectedDocuments = new Set(); // 전체 선택된 문서
    let tempSelectedDocuments = new Set();

    // 편집 모드일 때 기존 선택 문서 복원
    <?php if ($is_edit && !empty($selected_documents)): ?>
    let existingDocuments = <?php echo json_encode(array_map('strval', $selected_documents)); ?>;
    <?php endif; ?>

    // 페이지 로드 시 체크박스에 이벤트 추가
    document.addEventListener('DOMContentLoaded', function() {
        // 편집 모드일 때 기존 선택 문서 복원을 먼저 수행
        <?php if ($is_edit && !empty($selected_documents)): ?>
        // 선택된 문서들을 Set에 추가
        existingDocuments.forEach(docId => {
            allSelectedDocuments.add(docId.toString());
            // 숨겨진 체크박스 체크
            const hiddenCheckbox = document.querySelector('#doc-' + docId);
            if (hiddenCheckbox) {
                hiddenCheckbox.checked = true;
            }
        });

        // 카테고리별로 선택된 문서 분류
        restoreSelectedDocuments();

        // 선택된 문서 목록 표시
        updateSelectedDocumentsList();
        <?php endif; ?>

        // 이벤트 리스너는 복원 후에 추가
        document.querySelectorAll('.main-category-checkbox').forEach(checkbox => {
            checkbox.addEventListener('click', function(e) {
                e.preventDefault(); // 기본 체크 동작 방지
                openCategoryModal(this);
            });
        });

        // 편집 모드일 때 비용 포맷팅
        <?php if ($is_edit): ?>
        // 비용 필드 초기 포맷팅
        document.querySelectorAll('.cost-input').forEach(input => {
            if (input.value && input.value !== '0') {
                input.value = parseInt(input.value).toLocaleString() + '만원';
            }
        });

        // 총 비용 계산
        calculateTotalCost();
        <?php endif; ?>
    });

    <?php if ($is_edit && !empty($selected_documents)): ?>
    // 선택된 문서를 카테고리별로 분류하여 복원
    function restoreSelectedDocuments() {
        categoryData.forEach(category => {
            const categoryId = category.id.toString();
            const selectedInCategory = findSelectedInCategory(category);

            if (selectedInCategory.length > 0) {
                selectedDocumentsByCategory[categoryId] = new Set(selectedInCategory);

                // 메인 체크박스 체크 및 개수 표시
                const mainCheckbox = document.querySelector(`input[data-category-id="${categoryId}"]`);
                if (mainCheckbox) {
                    mainCheckbox.checked = true;
                    const label = mainCheckbox.parentElement;
                    // 기존 개수 표시 제거
                    const existingCount = label.querySelector('.doc-count');
                    if (existingCount) {
                        existingCount.remove();
                    }
                    // 새로운 개수 표시 추가
                    const countSpan = document.createElement('span');
                    countSpan.className = 'doc-count';
                    countSpan.style.cssText = 'color: #1976d2; font-weight: bold;';
                    countSpan.textContent = ' (' + selectedInCategory.length + ')';
                    label.appendChild(countSpan);
                }
            }
        });
    }

    // 카테고리 내 선택된 문서 찾기
    function findSelectedInCategory(category) {
        let selected = [];
        const categoryId = category.id.toString();
        const processedIds = new Set();

        function searchInChildren(items) {
            items.forEach(item => {
                if (item.children && item.children.length > 0) {
                    searchInChildren(item.children);
                } else {
                    // 최하위 문서
                    if (allSelectedDocuments.has(item.id.toString())) {
                        const docId = item.id.toString();
                        if (!processedIds.has(docId)) {
                            selected.push(docId);
                            processedIds.add(docId);
                        }
                    }
                }
            });
        }

        if (category.children) {
            searchInChildren(category.children);
        }

        return selected;
    }
    <?php endif; ?>

    // 카테고리 모달 열기
    function openCategoryModal(checkbox) {
        currentParentCheckbox = checkbox;
        const categoryId = checkbox.getAttribute('data-category-id').toString();
        const categoryName = checkbox.parentElement.textContent.trim();

        // 모달 제목 설정
        document.getElementById('modal-title').textContent = categoryName + ' - 상세 문서 선택';

        // 해당 카테고리 찾기
        const category = findCategoryById(categoryData, categoryId);
        if (category) {
            console.log('=== openCategoryModal ===');
            console.log('카테고리 ID:', categoryId);
            console.log('기존 저장된 문서:', selectedDocumentsByCategory[categoryId] ? Array.from(selectedDocumentsByCategory[categoryId]) : 'none');

            // 중요: clear()만 사용! 새 객체 할당 금지!
            tempSelectedDocuments.clear();

            if (selectedDocumentsByCategory[categoryId]) {
                selectedDocumentsByCategory[categoryId].forEach(docId => {
                    tempSelectedDocuments.add(docId);
                });
            } else {
                // 편집 모드에서 처음 열 때, 해당 카테고리의 선택된 문서들을 찾아서 추가
                <?php if ($is_edit && !empty($selected_documents)): ?>
                existingDocuments.forEach(docId => {
                    if (isDocumentInCategory(category, docId)) {
                        tempSelectedDocuments.add(docId);
                    }
                });
                <?php endif; ?>
            }

            console.log('tempSelectedDocuments 설정 후:', Array.from(tempSelectedDocuments));

            renderTree(category);
            updateSelectedCount();
            document.getElementById('documentModal').style.display = 'flex';
        }
    }

    // 트리 구조 렌더링
    function renderTree(category) {
        const container = document.getElementById('tree-container');
        container.innerHTML = '';

        if (category.children && category.children.length > 0) {
            const treeHtml = buildTreeHtml(category.children, 0);
            container.innerHTML = treeHtml;
            
            // 트리 렌더링 후 체크박스 상태 복원
            updateModalCheckboxes();
        } else {
            container.innerHTML = '<p style="color: #999;">하위 문서가 없습니다.</p>';
        }
    }
    
    // 모달 체크박스 상태 업데이트
    function updateModalCheckboxes() {
        document.querySelectorAll('.doc-checkbox').forEach(checkbox => {
            const docId = checkbox.value.toString();
            if (tempSelectedDocuments.has(docId)) {
                checkbox.checked = true;
            }
        });
    }

    // 트리 HTML 생성
    function buildTreeHtml(items, level) {
        let html = '';

        items.forEach(item => {
            const hasChildren = item.children && item.children.length > 0;
            const isDocument = !hasChildren; // 자식이 없으면 문서

            html += '<div class="tree-item">';

            if (isDocument) {
                // 문서인 경우 - 체크박스 표시
                const isChecked = tempSelectedDocuments.has(item.id.toString());
                html += '<div class="tree-document">';
                html += '<label>';
                html += '<input type="checkbox" class="doc-checkbox" value="' + item.id + '" ' +
                    (isChecked ? 'checked' : '') + ' onchange="toggleDocument(this)">';
                html += htmlspecialchars(item.name);
                html += '</label>';
                html += '</div>';
            } else {
                // 폴더인 경우
                html += '<div class="tree-folder" onclick="toggleFolder(this)">';
                html += '<span class="folder-icon">▶</span> ';
                html += htmlspecialchars(item.name);
                html += '</div>';
                html += '<div class="tree-children">';
                html += buildTreeHtml(item.children, level + 1);
                html += '</div>';
            }

            html += '</div>';
        });

        return html;
    }

    // 폴더 토글
    function toggleFolder(folderElement) {
        const childrenDiv = folderElement.nextElementSibling;
        const icon = folderElement.querySelector('.folder-icon');

        if (childrenDiv.classList.contains('show')) {
            childrenDiv.classList.remove('show');
            icon.classList.remove('open');
        } else {
            childrenDiv.classList.add('show');
            icon.classList.add('open');
        }
    }

    // 문서 선택/해제
    function toggleDocument(checkbox) {
        const docId = checkbox.value.toString();
        if (checkbox.checked) {
            tempSelectedDocuments.add(docId);
        } else {
            tempSelectedDocuments.delete(docId);
        }
        updateSelectedCount();
    }

    // 선택된 문서 수 업데이트
    function updateSelectedCount() {
        document.getElementById('selected-count').textContent = tempSelectedDocuments.size;
    }

    // 모두 펼치기
    function expandAll() {
        document.querySelectorAll('.tree-children').forEach(div => {
            div.classList.add('show');
        });
        document.querySelectorAll('.folder-icon').forEach(icon => {
            icon.classList.add('open');
        });
    }

    // 모두 접기
    function collapseAll() {
        document.querySelectorAll('.tree-children').forEach(div => {
            div.classList.remove('show');
        });
        document.querySelectorAll('.folder-icon').forEach(icon => {
            icon.classList.remove('open');
        });
    }

    // 모든 문서 선택
    function selectAllDocuments() {
        document.querySelectorAll('.doc-checkbox').forEach(checkbox => {
            checkbox.checked = true;
            const docId = checkbox.value.toString();
            tempSelectedDocuments.add(docId);
        });
        updateSelectedCount();
    }

    // 모든 문서 해제
    function deselectAllDocuments() {
        document.querySelectorAll('.doc-checkbox').forEach(checkbox => {
            checkbox.checked = false;
            const docId = checkbox.value.toString();
            tempSelectedDocuments.delete(docId);
        });
        updateSelectedCount();
    }

    // 선택 적용
    function applySelection() {
        const categoryId = currentParentCheckbox.getAttribute('data-category-id').toString();
        
        console.log('=== applySelection 시작 ===');
        console.log('카테고리 ID:', categoryId);
        console.log('tempSelectedDocuments:', Array.from(tempSelectedDocuments));

        // 현재 카테고리의 선택을 저장
        if (tempSelectedDocuments.size > 0) {
            // 기존 Set이 없으면 새로 생성
            if (!selectedDocumentsByCategory[categoryId]) {
                selectedDocumentsByCategory[categoryId] = new Set();
            } else {
                // 있으면 clear만
                selectedDocumentsByCategory[categoryId].clear();
            }
            tempSelectedDocuments.forEach(docId => {
                selectedDocumentsByCategory[categoryId].add(docId);
            });
            console.log('저장된 문서들:', Array.from(selectedDocumentsByCategory[categoryId]));
        } else {
            delete selectedDocumentsByCategory[categoryId];
        }

        // 부모 체크박스 상태 결정
        currentParentCheckbox.checked = tempSelectedDocuments.size > 0;

        // 부모 체크박스에 선택된 문서 개수 표시
        const label = currentParentCheckbox.parentElement;
        const countSpan = label.querySelector('.doc-count');
        if (countSpan) {
            countSpan.remove();
        }
        if (tempSelectedDocuments.size > 0) {
            const newCountSpan = document.createElement('span');
            newCountSpan.className = 'doc-count';
            newCountSpan.style.cssText = 'color: #1976d2; font-weight: bold;';
            newCountSpan.textContent = ' (' + tempSelectedDocuments.size + ')';
            label.appendChild(newCountSpan);
        }

        // 전체 선택된 문서 업데이트
        allSelectedDocuments.clear();
        Object.values(selectedDocumentsByCategory).forEach(categorySet => {
            categorySet.forEach(docId => allSelectedDocuments.add(docId));
        });

        console.log('전체 선택된 문서:', Array.from(allSelectedDocuments));
        
        updateHiddenCheckboxes();
        updateSelectedDocumentsList();
        closeModal();
    }

    // 모달 닫기
    function closeModal() {
        document.getElementById('documentModal').style.display = 'none';
        currentParentCheckbox = null;
    }

    // 카테고리 ID로 찾기
    function findCategoryById(categories, id) {
        for (let category of categories) {
            if (category.id == id) {
                return category;
            }
            if (category.children) {
                const found = findCategoryById(category.children, id);
                if (found) return found;
            }
        }
        return null;
    }

    // 문서가 특정 카테고리에 속하는지 확인
    function isDocumentInCategory(category, docId) {
        function searchInCategory(cat) {
            if (cat.id.toString() === docId) {
                return true;
            }
            if (cat.children && cat.children.length > 0) {
                for (let child of cat.children) {
                    if (searchInCategory(child)) {
                        return true;
                    }
                }
            }
            return false;
        }
        
        if (category.children) {
            for (let child of category.children) {
                if (searchInCategory(child)) {
                    return true;
                }
            }
        }
        return false;
    }

    // HTML 특수문자 이스케이프
    function htmlspecialchars(str) {
        if (typeof str !== 'string') return '';
        return str.replace(/[&<>"']/g, function(match) {
            const escape = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return escape[match];
        });
    }

    // 모달 외부 클릭 시 닫기
    window.onclick = function(event) {
        const modal = document.getElementById('documentModal');
        if (event.target == modal) {
            closeModal();
        }
    }

    // 숨겨진 체크박스들 업데이트
    function updateHiddenCheckboxes() {
        document.querySelectorAll('#hidden-documents input[type="checkbox"]').forEach(checkbox => {
            const docId = checkbox.value.toString();
            checkbox.checked = allSelectedDocuments.has(docId);
        });
    }

    // 뒤로가기 함수
    function goBack() {
        // AdminLTE 환경에 맞춰 목록 페이지로 이동
        window.location.href = './?page=document_requests';
    }

    // 선택된 서류 목록 업데이트
    function updateSelectedDocumentsList() {
        const selectedDocs = [];
        const selectedPaths = new Map();

        // 선택된 문서들의 경로 수집
        document.querySelectorAll('#hidden-documents input[type="checkbox"]:checked').forEach(checkbox => {
            const path = checkbox.getAttribute('data-path');
            const docId = checkbox.value.toString();
            if (path && allSelectedDocuments.has(docId)) {
                selectedPaths.set(docId, path);
            }
        });

        // 선택된 순서대로 표시
        allSelectedDocuments.forEach(docId => {
            const path = selectedPaths.get(docId);
            if (path) {
                selectedDocs.push(path);
            }
        });

        const container = document.getElementById('selected-docs-container');
        const list = document.getElementById('selected-docs-list');

        if (selectedDocs.length > 0) {
            container.style.display = 'block';
            list.innerHTML = selectedDocs.map(doc => `<li>${doc}</li>`).join('');
        } else {
            container.style.display = 'none';
        }
    }

    // 총 비용 계산 함수
    function calculateTotalCost() {
        let total = 0;

        // 모든 비용 입력 필드 순회
        document.querySelectorAll('.cost-input').forEach(input => {
            if (input.value) {
                // 숫자만 추출 (쉼표, '만원' 등 제거)
                const value = parseInt(input.value.replace(/[^0-9]/g, ''));
                if (!isNaN(value)) {
                    total += value;
                }
            }
        });

        // VAT 포함 여부 확인
        const vatIncluded = document.querySelector('input[name="vat_included"]:checked');
        let displayTotal = total;
        let vatText = '';

        // 실제 저장값은 원본 total 사용
        document.getElementById('total_cost').setAttribute('data-raw-value', total);

        if (vatIncluded && vatIncluded.value === '1') {
            // VAT 포함인 경우 10% 추가
            displayTotal = Math.round(total * 1.1);
            vatText = ' (VAT포함)';
        } else {
            vatText = ' (VAT별도)';
        }

        // 종합계 필드에 합계 표시
        const totalCostInput = document.getElementById('total_cost');
        if (displayTotal > 0) {
            totalCostInput.value = displayTotal.toLocaleString() + '만원' + vatText;
        } else {
            totalCostInput.value = '';
        }
    }

    // 숫자 입력 시 자동 포맷팅
    document.querySelectorAll('.cost-input').forEach(input => {
        // 포커스 시 포맷 제거
        input.addEventListener('focus', function(e) {
            let value = e.target.value.replace(/[^0-9]/g, '');
            e.target.value = value;
        });

        // 포커스 해제 시 포맷 적용
        input.addEventListener('blur', function(e) {
            let value = e.target.value.replace(/[^0-9]/g, '');
            if (value && value !== '0') {
                e.target.value = parseInt(value).toLocaleString() + '만원';
            } else {
                e.target.value = '';
            }
            // 총 비용 재계산
            calculateTotalCost();
        });

        // 입력 중에는 숫자만 허용
        input.addEventListener('input', function(e) {
            let value = e.target.value;
            let numbers = value.replace(/[^0-9]/g, '');

            // 숫자가 아닌 문자가 입력된 경우에만 교체
            if (value !== numbers) {
                e.target.value = numbers;
            }
        });

        // 키 입력 시 총 비용 재계산
        input.addEventListener('keyup', function(e) {
            calculateTotalCost();
        });
    });

    // 페이지 로드 시 초기 계산
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!$is_edit): ?>
        calculateTotalCost();
        <?php endif; ?>

        // 폼 리셋 시 선택 상태도 초기화
        document.querySelector('form').addEventListener('reset', function() {
            selectedDocumentsByCategory = {};
            allSelectedDocuments.clear();
            document.querySelectorAll('.main-category-checkbox').forEach(checkbox => {
                checkbox.checked = false;
                const countSpan = checkbox.parentElement.querySelector('.doc-count');
                if (countSpan) countSpan.remove();
            });
            updateSelectedDocumentsList();
        });
    });
</script>
</body>
</html>
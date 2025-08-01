<?php
// config.php - 데이터베이스 설정
// 세션이 이미 시작되었는지 확인
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 상수가 이미 정의되었는지 확인 - 각각 개별적으로 확인
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'purchase_order_db');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}

// 데이터베이스 연결
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("연결 실패: " . $e->getMessage());
}

// 로그인 체크 (임시)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // 임시로 admin 사용
}

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 트랜잭션 시작
        $pdo->beginTransaction();

        // 1. document_requests 테이블에 기본 정보 저장
        $stmt = $pdo->prepare("INSERT INTO document_requests (
            request_no, supplier_id, project_name, due_date, 
            additional_notes, upload_token, status, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, 0, ?)");

        // 업로드 토큰 생성
        $upload_token = bin2hex(random_bytes(32));

        $stmt->execute([
            $_POST['request_no'],
            $_POST['supplier_id'],
            $_POST['project_name'],
            $_POST['submission_date'],
            $_POST['additional_notes'] ?? '',
            $upload_token,
            $_SESSION['user_id']
        ]);

        $request_id = $pdo->lastInsertId();

        // 2. document_request_details 테이블에 상세 정보 저장 (새 테이블)
        $stmt = $pdo->prepare("INSERT INTO document_request_details (
            request_id, construction_method, manager_name, manager_contact, 
            manager_email, director_name, director_contact, order_date,
            total_cost, vat_included
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $request_id,
            $_POST['construction_method'],
            $_POST['manager_name'],
            $_POST['manager_contact'],
            $_POST['manager_email'],
            $_POST['director_name'] ?? null,
            $_POST['director_contact'] ?? null,
            $_POST['order_date'],
            $_POST['total_cost'],
            isset($_POST['vat_included']) ? 1 : 0
        ]);

        // 3. 선택된 서류들을 request_documents 테이블에 저장
        // 모달에서 선택된 실제 문서들 처리
        if (isset($_POST['selected_documents'])) {
            $stmt = $pdo->prepare("INSERT INTO request_documents (
                request_id, category_id, document_name, is_required, status
            ) VALUES (?, ?, ?, 1, 'pending')");

            foreach ($_POST['selected_documents'] as $doc_id) {
                // 문서 정보 조회
                $doc_stmt = $pdo->prepare("SELECT id, name FROM document_categories WHERE id = ?");
                $doc_stmt->execute([$doc_id]);
                $doc_info = $doc_stmt->fetch();

                if ($doc_info) {
                    $stmt->execute([$request_id, $doc_id, $doc_info['name']]);
                }
            }
        }

        // 4. 작성 대상 정보 저장 (새 테이블)
        $stmt = $pdo->prepare("INSERT INTO document_targets (
            request_id, safety_plan_type, review_agency, hazard_prevention_type,
            safety_health_agency, safety_health_ledger_type, evaluation_type,
            education_facility, education_office, railway_protection, railway_manager
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $request_id,
            $_POST['safety_plan_type'] ?? null,
            $_POST['review_agency'] ?? null,
            $_POST['hazard_prevention_type'] ?? null,
            $_POST['safety_health_agency'] ?? null,
            $_POST['safety_health_ledger_type'] ?? null,
            $_POST['evaluation_type'] ?? null,
            $_POST['education_facility'] ?? null,
            $_POST['education_office'] ?? null,
            $_POST['railway_protection'] ?? null,
            $_POST['railway_manager'] ?? null
        ]);

        // 5. 비용 정보 저장 (새 테이블)
        $stmt = $pdo->prepare("INSERT INTO document_cost_details (
            request_id, safety_plan_cost, hazard_prevention_cost, structure_review_cost,
            structure_review_agency, plan_review_cost, plan_review_agency,
            safety_health_cost, education_facility_cost, railway_protection_cost,
            evaluation_cost
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $request_id,
            $_POST['safety_plan_cost'] ?? null,
            $_POST['hazard_prevention_cost'] ?? null,
            $_POST['structure_review_cost'] ?? null,
            $_POST['structure_review_agency'] ?? null,
            $_POST['plan_review_cost'] ?? null,
            $_POST['plan_review_agency'] ?? null,
            $_POST['safety_health_cost'] ?? null,
            $_POST['education_facility_cost'] ?? null,
            $_POST['railway_protection_cost'] ?? null,
            $_POST['evaluation_cost'] ?? null
        ]);

        // 6. 작성자 정보 저장 (새 테이블)
        $stmt = $pdo->prepare("INSERT INTO document_writers (
            request_id, main_writer, revenue_manager, field_writers
        ) VALUES (?, ?, ?, ?)");

        $stmt->execute([
            $request_id,
            $_POST['main_writer'] ?? null,
            $_POST['revenue_manager'] ?? null,
            $_POST['field_writers'] ?? null
        ]);

        // 7. 검토 기관 로그인 정보 저장 (새 테이블)
        if (!empty($_POST['csi_id']) || !empty($_POST['kosha_id'])) {
            $stmt = $pdo->prepare("INSERT INTO review_credentials (
                request_id, 
                csi_id, csi_password, csi_supervisor, csi_supervisor_info,
                csi_client, csi_client_info,
                kosha_id, kosha_password, kosha_notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                $request_id,
                $_POST['csi_id'] ?? null,
                !empty($_POST['csi_password']) ? password_hash($_POST['csi_password'], PASSWORD_DEFAULT) : null,
                $_POST['csi_supervisor'] ?? null,
                $_POST['csi_supervisor_info'] ?? null,
                $_POST['csi_client'] ?? null,
                $_POST['csi_client_info'] ?? null,
                $_POST['kosha_id'] ?? null,
                !empty($_POST['kosha_password']) ? password_hash($_POST['kosha_password'], PASSWORD_DEFAULT) : null,
                $_POST['kosha_notes'] ?? null
            ]);
        }

        // 8. 워크플로우 상태 초기화
        $stmt = $pdo->prepare("INSERT INTO workflow_status (
            request_id, current_step, step_name, step_description, 
            started_at, assigned_to, is_current
        ) VALUES (?, 'created', '요청 생성', '서류 요청이 생성되었습니다.', NOW(), ?, 1)");


        $stmt->execute([$request_id, $_SESSION['user_id']]);

        $pdo->commit();
        $success_message = "서류 요청이 성공적으로 저장되었습니다. (요청번호: {$_POST['request_no']})";

    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "저장 중 오류가 발생했습니다: " . $e->getMessage();
    }
}

// 요청번호 자동 생성
$date = date('Ymd');
$stmt = $pdo->query("SELECT COUNT(*) + 1 as cnt FROM document_requests WHERE request_no LIKE 'REQ-$date-%'");
$count = str_pad($stmt->fetch()['cnt'], 3, '0', STR_PAD_LEFT);
$auto_request_no = "REQ-$date-$count";

// 의뢰처 목록 조회
$suppliers = $pdo->query("SELECT id, name FROM supplier_list WHERE status = 1 ORDER BY name")->fetchAll(PDO::FETCH_KEY_PAIR);

// 서류 카테고리 계층 구조로 조회
function getCategoryTree($pdo, $parent_id = null) {
    $sql = "SELECT * FROM document_categories WHERE status = 1";
    if ($parent_id === null) {
        $sql .= " AND parent_id IS NULL";
    } else {
        $sql .= " AND parent_id = " . $parent_id;
    }
    $sql .= " ORDER BY display_order, name";

    $stmt = $pdo->query($sql);
    $categories = $stmt->fetchAll();

    foreach ($categories as &$category) {
        $category['children'] = getCategoryTree($pdo, $category['id']);
    }

    return $categories;
}

$categories = $pdo->query("SELECT id, name FROM document_categories WHERE level = 1 AND status = 1 ORDER BY display_order")->fetchAll();
$categoryTree = getCategoryTree($pdo);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>서류 요청 관리 시스템</title>
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
        <h1 style="margin: 0;">서류 요청 관리 시스템</h1>
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
                <td colspan="2"><input type="text" name="project_name" required></td>
                <th width="15%">시공방법</th>
                <td width="25%">
                    <select name="construction_method">
                        <option value="">선택하세요</option>
                        <option value="단독">단독</option>
                        <option value="공동">공동</option>
                        <option value="분담">분담</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th>회사명</th>
                <td colspan="2">
                    <select name="supplier_id" required>
                        <option value="">선택하세요</option>
                        <?php foreach ($suppliers as $id => $name): ?>
                            <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <th>수주일</th>
                <td><input type="date" name="order_date"></td>
            </tr>
            <tr>
                <th rowspan="2">담당자</th>
                <th width="10%">담당</th>
                <td>
                    <input type="text" name="manager_name" placeholder="담당자 이름" style="width: 40%; display: inline;" >
                    <input type="text" name="manager_contact" placeholder="연락처" style="width: 40%; display: inline; margin-left: 10px;">
                </td>
                <th>이메일</th>
                <td><input type="email" name="manager_email" placeholder="이메일 주소"></td>
            </tr>
            <tr>
                <th>소장</th>
                <td>
                    <input type="text" name="director_name" placeholder="소장 이름" style="width: 40%; display: inline;">
                    <input type="text" name="director_contact" placeholder="연락처" style="width: 40%; display: inline; margin-left: 10px;">
                </td>
                <th>제출예정일</th>
                <td><input type="date" name="submission_date"></td>
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
                        <option value="1종">1종</option>
                        <option value="2종">2종</option>
                        <option value="기타">기타(천공기, 동바리)</option>
                    </select>
                </td>
                <th width="15%">검토처</th>
                <td>
                    <select name="review_agency">
                        <option value="">선택 안함</option>
                        <option value="국토안전관리원">국토안전관리원</option>
                        <option value="안전점검기관">안전점검기관</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th>유해위험방지계획서</th>
                <td>
                    <select name="hazard_prevention_type">
                        <option value="">선택 안함</option>
                        <option value="높이31m이상">높이 31m 이상</option>
                        <option value="굴착10m이상">굴착 10m 이상</option>
                        <option value="교량">교량</option>
                        <option value="기타">기타</option>
                    </select>
                </td>
                <th>안전보건공단</th>
                <td><input type="text" name="safety_health_agency" placeholder="강원동부지사"></td>
            </tr>
            <tr>
                <th>안전보건대장</th>
                <td>
                    <select name="safety_health_ledger_type">
                        <option value="">선택 안함</option>
                        <option value="기본">기본</option>
                        <option value="설계">설계</option>
                        <option value="공사">공사</option>
                    </select>
                </td>
                <th>적정성평가</th>
                <td>
                    <select name="evaluation_type">
                        <option value="">선택 안함</option>
                        <option value="기본">기본</option>
                        <option value="설계">설계</option>
                        <option value="공사">공사</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th>교육시설</th>
                <td><input type="text" name="education_facility" placeholder="교육시설 정보 입력"></td>
                <th>관할교육청</th>
                <td><input type="text" name="education_office" placeholder="관할교육청 입력"></td>
            </tr>
            <tr>
                <th>철도보호지구</th>
                <td><input type="text" name="railway_protection" placeholder="철도보호지구 관련정보"></td>
                <th>철도보호지구관리자</th>
                <td><input type="text" name="railway_manager" placeholder="철도보호지구관리자 입력"></td>
            </tr>
        </table>

        <!-- 작성 비용 -->
        <div class="section-title">작성 비용 (VAT 별도)</div>

        <table>
            <tr>
                <th width="20%">안전관리계획서</th>
                <td width="30%"><input type="text" name="safety_plan_cost" placeholder="안전관리계획비 입력(만원)" class="cost-input"></td>
                <th width="20%">유해위험방지계획서</th>
                <td width="30%"><input type="text" name="hazard_prevention_cost" placeholder="유해위험방지계획비 입력(만원)" class="cost-input"></td>
            </tr>
            <tr>
                <th>구조검토비</th>
                <td><input type="text" name="structure_review_cost" placeholder="구조검토비 입력(만원)" class="cost-input"></td>
                <th>위탁처</th>
                <td><input type="text" name="structure_review_agency" placeholder="위탁처 입력(만원)"></td>
            </tr>
            <tr>
                <th>계획서검토비</th>
                <td><input type="text" name="plan_review_cost" placeholder="계획서검토비 입력(만원)" class="cost-input"></td>
                <th>검토처</th>
                <td><input type="text" name="plan_review_agency" placeholder="검토처 입력(만원)"></td>
            </tr>
            <tr>
                <th>안전보건대장</th>
                <td><input type="text" name="safety_health_cost" placeholder="안전관리계획비 입력(만원)" class="cost-input"></td>
                <th>교육시설</th>
                <td><input type="text" name="education_facility_cost" placeholder="교육시설 비용 입력(만원)" class="cost-input"></td>
            </tr>
            <tr>
                <th>철도보호</th>
                <td><input type="text" name="railway_protection_cost" placeholder="철도보호 비용 입력(만원)" class="cost-input"></td>
                <th>적정성평가</th>
                <td><input type="text" name="evaluation_cost" placeholder="적정성평가 비용 입력(만원)" class="cost-input"></td>
            </tr>
            <tr>
                <th>종합계</th>
                <td colspan="3">
                    <input type="text" name="total_cost" id="total_cost" placeholder="0만원" style="width: 200px; display: inline;" readonly required>
                    <label style="margin-left: 20px;">
                        <input type="radio" name="vat_included" value="1" onchange="calculateTotalCost()"> VAT 포함
                    </label>
                    <label style="margin-left: 20px;">
                        <input type="radio" name="vat_included" value="0" checked onchange="calculateTotalCost()"> VAT 별도
                    </label>
                </td>
            </tr>
        </table>

        <!-- 작성자 -->
        <div class="section-title">작성자</div>

        <table>
            <tr>
                <th width="20%">주관자(의뢰처접담)</th>
                <td width="30%"><input type="text" name="main_writer"></td>
                <th width="20%">수주관리/소개/수당</th>
                <td width="30%"><input type="text" name="revenue_manager"></td>
            </tr>
            <tr>
                <th>분야별 작성자</th>
                <td colspan="3">
                    <input type="text" name="field_writers" placeholder="※ 정병 구분하여 작성시 기재">
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
                            <td style="border: 1px solid #ddd; padding: 3px;"><input type="text" name="csi_id" placeholder="아이디" style="width: 95%; border: none;" autocomplete="off"></td>
                        </tr>
                    </table>
                </td>
                <td><input type="text" name="csi_supervisor" placeholder=""></td>
                <td><input type="text" name="csi_client" placeholder=""></td>
            </tr>
            <tr>
                <td>
                    <table style="width: 100%; border: none; margin: 0;">
                        <tr>
                            <td style="border: none; padding: 3px; width: 15%; text-align: center;">비번</td>
                            <td style="border: 1px solid #ddd; padding: 3px;"><input type="password" name="csi_password" placeholder="비밀번호" style="width: 95%; border: none;" autocomplete="new-password"></td>
                        </tr>
                    </table>
                </td>
                <td><input type="text" name="csi_supervisor_info" placeholder=""></td>
                <td><input type="text" name="csi_client_info" placeholder=""></td>
            </tr>
            <tr>
                <th>안전보건공단</th>
                <td>
                    <table style="width: 100%; border: none; margin: 0;">
                        <tr>
                            <td style="border: none; padding: 3px; width: 15%; text-align: center;">ID</td>
                            <td style="border: 1px solid #ddd; padding: 3px;"><input type="text" name="kosha_id" placeholder="아이디" style="width: 95%; border: none;" autocomplete="off"></td>
                        </tr>
                    </table>
                </td>
                <td>
                    <table style="width: 100%; border: none; margin: 0;">
                        <tr>
                            <td style="border: none; padding: 3px; width: 15%; text-align: center;">비번</td>
                            <td style="border: 1px solid #ddd; padding: 3px;"><input type="password" name="kosha_password" placeholder="비밀번호" style="width: 95%; border: none;" autocomplete="new-password"></td>
                        </tr>
                    </table>
                </td>
                <td>
                    <table style="width: 100%; border: none; margin: 0;">
                        <tr>
                            <td style="border: none; padding: 3px; width: 25%; text-align: center;">기타사항</td>
                            <td style="border: 1px solid #ddd; padding: 3px;"><input type="text" name="kosha_notes" placeholder="" style="width: 95%; border: none;"></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- 추가 요청사항 -->
        <div class="section-title">추가 요청사항</div>
        <textarea name="additional_notes" rows="4" placeholder="추가로 요청할 사항이 있으시면 입력해주세요."></textarea>

        <!-- 선택된 서류 목록 -->
        <div class="selected-documents" id="selected-docs-container" style="display: none;">
            <h3>선택된 서류 목록</h3>
            <ul class="selected-list" id="selected-docs-list"></ul>
        </div>

        <!-- 버튼 그룹 -->
        <div class="button-group">
            <button type="submit" class="btn-primary">저장하기</button>
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

    // 페이지 로드 시 체크박스에 이벤트 추가
    document.addEventListener('DOMContentLoaded', function() {
        // 기존 체크박스에 클릭 이벤트 추가
        document.querySelectorAll('.main-category-checkbox').forEach(checkbox => {
            checkbox.addEventListener('click', function(e) {
                e.preventDefault(); // 기본 체크 동작 방지
                openCategoryModal(this);
            });
        });
    });

    // 카테고리 모달 열기
    function openCategoryModal(checkbox) {
        currentParentCheckbox = checkbox;
        const categoryId = checkbox.getAttribute('data-category-id');
        const categoryName = checkbox.parentElement.textContent.trim();

        // 모달 제목 설정
        document.getElementById('modal-title').textContent = categoryName + ' - 상세 문서 선택';

        // 해당 카테고리 찾기
        const category = findCategoryById(categoryData, categoryId);
        if (category) {
            // 해당 카테고리의 기존 선택 문서들을 임시 Set에 복사
            if (selectedDocumentsByCategory[categoryId]) {
                tempSelectedDocuments = new Set(selectedDocumentsByCategory[categoryId]);
            } else {
                tempSelectedDocuments = new Set();
            }

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
        } else {
            container.innerHTML = '<p style="color: #999;">하위 문서가 없습니다.</p>';
        }
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
        if (checkbox.checked) {
            tempSelectedDocuments.add(checkbox.value);
        } else {
            tempSelectedDocuments.delete(checkbox.value);
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
            tempSelectedDocuments.add(checkbox.value);
        });
        updateSelectedCount();
    }

    // 모든 문서 해제
    function deselectAllDocuments() {
        document.querySelectorAll('.doc-checkbox').forEach(checkbox => {
            checkbox.checked = false;
            tempSelectedDocuments.delete(checkbox.value);
        });
        updateSelectedCount();
    }

    // 선택 적용
    function applySelection() {
        const categoryId = currentParentCheckbox.getAttribute('data-category-id');
        
        // 현재 카테고리의 선택을 저장
        if (tempSelectedDocuments.size > 0) {
            selectedDocumentsByCategory[categoryId] = new Set(tempSelectedDocuments);
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
            label.innerHTML += ' <span class="doc-count" style="color: #1976d2; font-weight: bold;">(' + tempSelectedDocuments.size + ')</span>';
        }

        // 전체 선택된 문서 업데이트
        allSelectedDocuments.clear();
        Object.values(selectedDocumentsByCategory).forEach(categorySet => {
            categorySet.forEach(docId => allSelectedDocuments.add(docId));
        });

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
            checkbox.checked = allSelectedDocuments.has(checkbox.value);
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
            const docId = checkbox.value;
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
        calculateTotalCost();
        
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
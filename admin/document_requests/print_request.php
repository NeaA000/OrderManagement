<?php
// 요청 ID 확인
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('잘못된 접근입니다.'); window.close();</script>";
    exit;
}

$request_id = $_GET['id'];

// 기본 정보 조회
$stmt = $conn->prepare("SELECT * FROM document_requests WHERE id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();
$request_data = $result->fetch_assoc();

if (!$request_data) {
    echo "<script>alert('요청을 찾을 수 없습니다.'); window.close();</script>";
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

// 재귀적으로 하위 카테고리의 모든 문서 ID 가져오기
function getAllDocumentIds($conn, $category_id) {
    $ids = [];

    // 현재 카테고리의 하위 카테고리 조회
    $stmt = $conn->prepare("SELECT id FROM document_categories WHERE parent_id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        // 하위 카테고리가 없으면 현재 카테고리가 문서
        $ids[] = $category_id;
    } else {
        // 하위 카테고리가 있으면 재귀적으로 탐색
        while ($row = $result->fetch_assoc()) {
            $ids = array_merge($ids, getAllDocumentIds($conn, $row['id']));
        }
    }

    return $ids;
}

// 카테고리별 선택된 문서 수 계산
function getSelectedCountByCategory($conn, $category_id, $selected_documents) {
    $count = 0;

    $all_doc_ids = getAllDocumentIds($conn, $category_id);

    // 선택된 문서 중 이 카테고리에 속하는 것 카운트
    foreach ($all_doc_ids as $doc_id) {
        if (in_array($doc_id, $selected_documents)) {
            $count++;
        }
    }

    return $count;
}

// 의뢰처 정보 조회
$stmt = $conn->prepare("SELECT name FROM supplier_list WHERE id = ?");
$stmt->bind_param("i", $request_data['supplier_id']);
$stmt->execute();
$supplier_name = $stmt->get_result()->fetch_assoc()['name'];

// 카테고리 정보 조회
$categories = [];
$result = $conn->query("SELECT id, name FROM document_categories WHERE level = 1 AND status = 1 ORDER BY display_order");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>서류 요청서 인쇄</title>
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

        /* 체크박스 스타일 */
        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-right: 8px;
            cursor: pointer;
            vertical-align: middle;
        }

        input[type="radio"] {
            width: 18px;
            height: 18px;
            margin-right: 5px;
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
        div.document-types {
            display: grid !important;
            grid-template-columns: repeat(5, 1fr) !important;
            gap: 15px !important;
            padding: 20px !important;
            background-color: #f9f9f9 !important;
            border: 2px solid #e0e0e0 !important;
            border-radius: 5px !important;
            margin-bottom: 30px !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }

        div.document-types label {
            display: inline-flex !important;
            align-items: center !important;
            margin: 0 !important;
            white-space: nowrap !important;
            width: auto !important;
        }

        div.document-types label:hover {
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
            background-color: #f9f9f9;
        }

        /* 읽기 전용 필드 */
        input[readonly],
        select[disabled],
        textarea[readonly] {
            background-color: #f5f5f5;
            cursor: not-allowed;
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

        /* 인쇄 스타일 */
        @media print {
            body {
                background-color: white;
                padding: 0;
            }

            .container {
                box-shadow: none;
                padding: 20px;
                max-width: 100%;
            }

            .button-group {
                display: none;
            }

            /* 인쇄 시에만 체크박스 비활성화 표시 */
            input[type="checkbox"] {
                pointer-events: none;
            }

            /* 페이지 나눔 방지 */
            table {
                page-break-inside: avoid;
            }

            .section-title {
                page-break-after: avoid;
            }

            /* 서류 분류 섹션도 페이지 나눔 방지 */
            .document-types {
                page-break-inside: avoid;
                grid-template-columns: repeat(5, 1fr);
            }
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
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 480px) {
            .document-types {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* 인쇄용 컨테이너 너비 조정 */
        @media screen and (min-width: 1200px) {
            .container {
                max-width: 1200px;
            }
        }
    </style>
</head>
<body>
<div class="container">


    <!-- 서류 분류 체크박스 -->
    <div class="section-title">서류 분류 선택</div>
    <div class="document-types">
        <?php foreach ($categories as $category): ?>
            <?php
            $selected_count = getSelectedCountByCategory($conn, $category['id'], $selected_documents);
            $is_checked = $selected_count > 0;
            ?><label>
            <input type="checkbox" disabled <?php echo $is_checked ? 'checked' : ''; ?>>
            <?php echo htmlspecialchars($category['name']); ?>
            <?php if ($selected_count > 0): ?>
                <span style="color: #1976d2; font-weight: bold;"> (<?php echo $selected_count; ?>)</span>
            <?php endif; ?>
            </label><?php endforeach; ?>
    </div>

    <!-- 관리번호 -->
    <div class="section-title">
        관리번호: <input type="text" value="<?php echo htmlspecialchars($request_data['request_no']); ?>" style="width: 200px; margin-left: 10px;" readonly>
    </div>

    <!-- 의뢰처 -->
    <div class="section-title">의뢰처</div>
    <table>
        <tr>
            <th width="15%">공사명</th>
            <td colspan="2"><input type="text" value="<?php echo htmlspecialchars($request_data['project_name']); ?>" readonly></td>
            <th width="15%">시공방법</th>
            <td width="25%">
                <select disabled>
                    <option value="">선택하세요</option>
                    <option value="단독" <?php echo ($details_data['construction_method'] == '단독') ? 'selected' : '' ?>>단독</option>
                    <option value="공동" <?php echo ($details_data['construction_method'] == '공동') ? 'selected' : '' ?>>공동</option>
                    <option value="분담" <?php echo ($details_data['construction_method'] == '분담') ? 'selected' : '' ?>>분담</option>
                </select>
            </td>
        </tr>
        <tr>
            <th>회사명</th>
            <td colspan="2">
                <input type="text" value="<?php echo htmlspecialchars($supplier_name); ?>" readonly>
            </td>
            <th>수주일</th>
            <td><input type="date" value="<?php echo $details_data['order_date'] ? date('Y-m-d', strtotime($details_data['order_date'])) : '' ?>" readonly></td>
        </tr>
        <tr>
            <th rowspan="2">담당자</th>
            <th width="10%">담당</th>
            <td>
                <input type="text" placeholder="담당자 이름" style="width: 40%; display: inline;" value="<?php echo htmlspecialchars($details_data['manager_name'] ?? ''); ?>" readonly>
                <input type="text" placeholder="연락처" style="width: 40%; display: inline; margin-left: 10px;" value="<?php echo htmlspecialchars($details_data['manager_contact'] ?? ''); ?>" readonly>
            </td>
            <th>이메일</th>
            <td><input type="email" placeholder="이메일 주소" value="<?php echo htmlspecialchars($details_data['manager_email'] ?? ''); ?>" readonly></td>
        </tr>
        <tr>
            <th>소장</th>
            <td colspan="3">
                <input type="text" placeholder="소장 이름" style="width: 40%; display: inline;" value="<?php echo htmlspecialchars($details_data['director_name'] ?? ''); ?>" readonly>
                <input type="text" placeholder="연락처" style="width: 40%; display: inline; margin-left: 10px;" value="<?php echo htmlspecialchars($details_data['director_contact'] ?? ''); ?>" readonly>
            </td>
        </tr>
    </table>

    <!-- 작성 대상 -->
    <div class="section-title">작성 대상</div>
    <table>
        <tr>
            <th width="20%">안전관리계획서</th>
            <td width="35%">
                <select disabled>
                    <option value="">선택 안함</option>
                    <option value="1종" <?php echo ($targets_data['safety_plan_type'] == '1종') ? 'selected' : '' ?>>1종</option>
                    <option value="2종" <?php echo ($targets_data['safety_plan_type'] == '2종') ? 'selected' : '' ?>>2종</option>
                    <option value="기타" <?php echo ($targets_data['safety_plan_type'] == '기타') ? 'selected' : '' ?>>기타(천공기, 동바리)</option>
                </select>
            </td>
            <th width="15%">검토처</th>
            <td>
                <select disabled>
                    <option value="">선택 안함</option>
                    <option value="국토안전관리원" <?php echo ($targets_data['review_agency'] == '국토안전관리원') ? 'selected' : '' ?>>국토안전관리원</option>
                    <option value="안전점검기관" <?php echo ($targets_data['review_agency'] == '안전점검기관') ? 'selected' : '' ?>>안전점검기관</option>
                </select>
            </td>
        </tr>
        <tr>
            <th>유해위험방지계획서</th>
            <td>
                <select disabled>
                    <option value="">선택 안함</option>
                    <option value="높이31m이상" <?php echo ($targets_data['hazard_prevention_type'] == '높이31m이상') ? 'selected' : '' ?>>높이 31m 이상</option>
                    <option value="굴착10m이상" <?php echo ($targets_data['hazard_prevention_type'] == '굴착10m이상') ? 'selected' : '' ?>>굴착 10m 이상</option>
                    <option value="교량" <?php echo ($targets_data['hazard_prevention_type'] == '교량') ? 'selected' : '' ?>>교량</option>
                    <option value="기타" <?php echo ($targets_data['hazard_prevention_type'] == '기타') ? 'selected' : '' ?>>기타</option>
                </select>
            </td>
            <th>안전보건공단</th>
            <td><input type="text" placeholder="강원동부지사" value="<?php echo htmlspecialchars($targets_data['safety_health_agency'] ?? ''); ?>" readonly></td>
        </tr>
        <tr>
            <th>안전보건대장</th>
            <td>
                <select disabled>
                    <option value="">선택 안함</option>
                    <option value="기본" <?php echo ($targets_data['safety_health_ledger_type'] == '기본') ? 'selected' : '' ?>>기본</option>
                    <option value="설계" <?php echo ($targets_data['safety_health_ledger_type'] == '설계') ? 'selected' : '' ?>>설계</option>
                    <option value="공사" <?php echo ($targets_data['safety_health_ledger_type'] == '공사') ? 'selected' : '' ?>>공사</option>
                </select>
            </td>
            <th>적정성평가</th>
            <td>
                <select disabled>
                    <option value="">선택 안함</option>
                    <option value="기본" <?php echo ($targets_data['evaluation_type'] == '기본') ? 'selected' : '' ?>>기본</option>
                    <option value="설계" <?php echo ($targets_data['evaluation_type'] == '설계') ? 'selected' : '' ?>>설계</option>
                    <option value="공사" <?php echo ($targets_data['evaluation_type'] == '공사') ? 'selected' : '' ?>>공사</option>
                </select>
            </td>
        </tr>
        <tr>
            <th>교육시설</th>
            <td><input type="text" placeholder="교육시설 정보 입력" value="<?php echo htmlspecialchars($targets_data['education_facility'] ?? ''); ?>" readonly></td>
            <th>관할교육청</th>
            <td><input type="text" placeholder="관할교육청 입력" value="<?php echo htmlspecialchars($targets_data['education_office'] ?? ''); ?>" readonly></td>
        </tr>
        <tr>
            <th>철도보호지구</th>
            <td><input type="text" placeholder="철도보호지구 관련정보" value="<?php echo htmlspecialchars($targets_data['railway_protection'] ?? ''); ?>" readonly></td>
            <th>철도보호지구관리자</th>
            <td><input type="text" placeholder="철도보호지구관리자 입력" value="<?php echo htmlspecialchars($targets_data['railway_manager'] ?? ''); ?>" readonly></td>
        </tr>
    </table>

    <!-- 작성 비용 -->
    <div class="section-title">작성 비용 (VAT 별도)</div>
    <table>
        <tr>
            <th width="20%">안전관리계획서</th>
            <td width="30%"><input type="text" placeholder="안전관리계획비 입력(만원)" value="<?php echo $costs_data['safety_plan_cost'] ? number_format($costs_data['safety_plan_cost']) . '만원' : ''; ?>" readonly></td>
            <th width="20%">유해위험방지계획서</th>
            <td width="30%"><input type="text" placeholder="유해위험방지계획비 입력(만원)" value="<?php echo $costs_data['hazard_prevention_cost'] ? number_format($costs_data['hazard_prevention_cost']) . '만원' : ''; ?>" readonly></td>
        </tr>
        <tr>
            <th>구조검토비</th>
            <td><input type="text" placeholder="구조검토비 입력(만원)" value="<?php echo $costs_data['structure_review_cost'] ? number_format($costs_data['structure_review_cost']) . '만원' : ''; ?>" readonly></td>
            <th>위탁처</th>
            <td><input type="text" placeholder="위탁처 입력" value="<?php echo htmlspecialchars($costs_data['structure_review_agency'] ?? ''); ?>" readonly></td>
        </tr>
        <tr>
            <th>계획서검토비</th>
            <td><input type="text" placeholder="계획서검토비 입력(만원)" value="<?php echo $costs_data['plan_review_cost'] ? number_format($costs_data['plan_review_cost']) . '만원' : ''; ?>" readonly></td>
            <th>검토처</th>
            <td><input type="text" placeholder="검토처 입력" value="<?php echo htmlspecialchars($costs_data['plan_review_agency'] ?? ''); ?>" readonly></td>
        </tr>
        <tr>
            <th>안전보건대장</th>
            <td><input type="text" placeholder="안전관리계획비 입력(만원)" value="<?php echo $costs_data['safety_health_cost'] ? number_format($costs_data['safety_health_cost']) . '만원' : ''; ?>" readonly></td>
            <th>교육시설</th>
            <td><input type="text" placeholder="교육시설 비용 입력(만원)" value="<?php echo $costs_data['education_facility_cost'] ? number_format($costs_data['education_facility_cost']) . '만원' : ''; ?>" readonly></td>
        </tr>
        <tr>
            <th>철도보호</th>
            <td><input type="text" placeholder="철도보호 비용 입력(만원)" value="<?php echo $costs_data['railway_protection_cost'] ? number_format($costs_data['railway_protection_cost']) . '만원' : ''; ?>" readonly></td>
            <th>적정성평가</th>
            <td><input type="text" placeholder="적정성평가 정보 입력" value="<?php echo htmlspecialchars($costs_data['evaluation_cost'] ?? ''); ?>" readonly></td>
        </tr>
        <tr>
            <th>종합계</th>
            <td colspan="3">
                <?php
                $vat_text = $details_data['vat_included'] == 1 ? ' (VAT포함)' : ' (VAT별도)';
                ?>
                <input type="text" placeholder="0만원" style="width: 200px; display: inline;" readonly value="<?php echo number_format($details_data['total_cost']) . '만원' . $vat_text; ?>">
                <label style="margin-left: 20px;">
                    <input type="radio" disabled <?php echo ($details_data['vat_included'] == 1) ? 'checked' : ''; ?>> VAT 포함
                </label>
                <label style="margin-left: 20px;">
                    <input type="radio" disabled <?php echo ($details_data['vat_included'] == 0) ? 'checked' : ''; ?>> VAT 별도
                </label>
            </td>
        </tr>
    </table>

    <!-- 작성자 -->
    <div class="section-title">작성자</div>
    <table>
        <tr>
            <th width="20%">주관자(의뢰처접담)</th>
            <td width="30%"><input type="text" value="<?php echo htmlspecialchars($writers_data['main_writer'] ?? ''); ?>" readonly></td>
            <th width="20%">수주관리/소개/수당</th>
            <td width="30%"><input type="text" value="<?php echo htmlspecialchars($writers_data['revenue_manager'] ?? ''); ?>" readonly></td>
        </tr>
        <tr>
            <th>분야별 작성자</th>
            <td colspan="3">
                <input type="text" placeholder="※ 정병 구분하여 작성시 기재" value="<?php echo htmlspecialchars($writers_data['field_writers'] ?? ''); ?>" readonly>
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
                        <td style="border: 1px solid #ddd; padding: 3px;"><input type="text" placeholder="아이디" style="width: 95%; border: none;" value="<?php echo htmlspecialchars($review_data['csi_id'] ?? ''); ?>" readonly></td>
                    </tr>
                </table>
            </td>
            <td><input type="text" placeholder="" value="<?php echo htmlspecialchars($review_data['csi_supervisor'] ?? ''); ?>" readonly></td>
            <td><input type="text" placeholder="" value="<?php echo htmlspecialchars($review_data['csi_client'] ?? ''); ?>" readonly></td>
        </tr>
        <tr>
            <td>
                <table style="width: 100%; border: none; margin: 0;">
                    <tr>
                        <td style="border: none; padding: 3px; width: 15%; text-align: center;">비번</td>
                        <td style="border: 1px solid #ddd; padding: 3px;"><input type="password" placeholder="" style="width: 95%; border: none;" value="****" readonly></td>
                    </tr>
                </table>
            </td>
            <td><input type="text" placeholder="" value="<?php echo htmlspecialchars($review_data['csi_supervisor_info'] ?? ''); ?>" readonly></td>
            <td><input type="text" placeholder="" value="<?php echo htmlspecialchars($review_data['csi_client_info'] ?? ''); ?>" readonly></td>
        </tr>
        <tr>
            <th>안전보건공단</th>
            <td>
                <table style="width: 100%; border: none; margin: 0;">
                    <tr>
                        <td style="border: none; padding: 3px; width: 15%; text-align: center;">ID</td>
                        <td style="border: 1px solid #ddd; padding: 3px;"><input type="text" placeholder="아이디" style="width: 95%; border: none;" value="<?php echo htmlspecialchars($review_data['kosha_id'] ?? ''); ?>" readonly></td>
                    </tr>
                </table>
            </td>
            <td>
                <table style="width: 100%; border: none; margin: 0;">
                    <tr>
                        <td style="border: none; padding: 3px; width: 15%; text-align: center;">비번</td>
                        <td style="border: 1px solid #ddd; padding: 3px;"><input type="password" placeholder="" style="width: 95%; border: none;" value="****" readonly></td>
                    </tr>
                </table>
            </td>
            <td>
                <table style="width: 100%; border: none; margin: 0;">
                    <tr>
                        <td style="border: none; padding: 3px; width: 25%; text-align: center;">기타사항</td>
                        <td style="border: 1px solid #ddd; padding: 3px;"><input type="text" placeholder="" style="width: 95%; border: none;" value="<?php echo htmlspecialchars($review_data['kosha_notes'] ?? ''); ?>" readonly></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- 추가 요청사항 숨김 필드 (데이터 보존용) -->
    <input type="hidden" name="additional_notes" value="<?php echo htmlspecialchars($request_data['additional_notes'] ?? ''); ?>">

    <!-- 버튼 그룹 -->
    <div class="button-group">
        <button type="button" class="btn-primary" onclick="window.print();">인쇄하기</button>
        <button type="button" class="btn-secondary" onclick="window.close();">닫기</button>
    </div>
</div>

<script>
    // 페이지 로드 시 자동 인쇄
    window.onload = function() {
        // 0.5초 대기 후 인쇄 (페이지 렌더링 완료 대기)
        setTimeout(function() {
            window.print();
        }, 500);
    };

    // 인쇄 대화상자가 닫히면 창 닫기
    window.onafterprint = function() {
        // 인쇄 후 자동으로 창 닫기
        setTimeout(function() {
            window.close();
        }, 100);
    };

    // Ctrl+P 또는 Cmd+P 단축키 처리
    document.addEventListener('keydown', function(event) {
        if ((event.ctrlKey || event.metaKey) && event.key === 'p') {
            event.preventDefault();
            window.print();
        }
    });
</script>
</body>
</html>
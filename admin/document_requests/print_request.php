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
    <title>서류 요청서 인쇄</title>
    <style>
        @page {
            size: A4;
            margin: 5mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Malgun Gothic', sans-serif;
            font-size: 9px;
            line-height: 1.2;
            color: #000;
            background: white;
        }

        .container {
            width: 200mm;
            margin: 0 auto;
        }

        /* 섹션 타이틀 */
        .section-title {
            background-color: #e8e8e8;
            padding: 3px 10px;
            margin: 5px 0 3px 0;
            font-weight: bold;
            font-size: 10px;
            border-left: 3px solid #d32f2f;
        }

        /* 체크박스 */
        input[type="checkbox"] {
            width: 12px;
            height: 12px;
            margin-right: 3px;
            vertical-align: middle;
        }

        input[type="radio"] {
            width: 10px;
            height: 10px;
            margin: 0 2px;
            vertical-align: middle;
        }

        label {
            display: inline-flex;
            align-items: center;
            margin-right: 10px;
            font-size: 9px;
        }

        /* 서류 분류 섹션 */
        .document-types {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 5px;
            padding: 8px;
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            margin-bottom: 10px;
        }

        /* 테이블 */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        th, td {
            border: 1px solid #000;
            padding: 2px 4px;
            font-size: 9px;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        /* 입력 필드 스타일 */
        .input-field {
            display: inline-block;
            border-bottom: 1px solid #000;
            min-width: 50px;
            padding: 0 3px;
            font-size: 9px;
        }

        .input-full {
            width: 95%;
        }

        textarea {
            width: 100%;
            border: 1px solid #000;
            padding: 3px;
            min-height: 30px;
            font-size: 9px;
            font-family: 'Malgun Gothic', sans-serif;
            resize: none;
        }

        /* 관리번호 */
        .management-no {
            display: inline-block;
            margin-left: 10px;
            border-bottom: 1px solid #000;
            padding: 0 5px;
            min-width: 150px;
        }

        /* 인라인 테이블 */
        .inline-table {
            display: inline-table;
            width: auto;
            vertical-align: middle;
            border: none;
        }

        .inline-table td {
            border: none;
            padding: 0 3px;
        }

        /* 인쇄 스타일 */
        @media print {
            /* 인쇄 시 숨길 요소들 */
            .no-print {
                display: none !important;
            }

            /* 페이지 설정 */
            body {
                margin: 0;
                padding: 0;
            }

            .container {
                margin: 0;
                padding: 0;
                width: 100%;
            }

            /* 테이블 페이지 나눔 방지 */
            table {
                page-break-inside: avoid;
            }

            /* 섹션 타이틀 페이지 나눔 방지 */
            .section-title {
                page-break-after: avoid;
            }

            /* 입력 필드 테두리 진하게 */
            .input-field {
                border-bottom: 1px solid #000 !important;
            }

            /* textarea 인쇄 시 전체 내용 표시 */
            textarea {
                height: auto !important;
                overflow: visible !important;
            }
        }

        /* 화면에서만 보이는 스타일 */
        @media screen {
            /* 인쇄 버튼 스타일 */
            button {
                background-color: #2196F3;
                color: white;
                border: none;
                border-radius: 4px;
                font-weight: bold;
                padding: 10px 30px;
                font-size: 14px;
                cursor: pointer;
            }

            button:hover {
                background-color: #1976D2;
            }

            button:active {
                transform: scale(0.98);
            }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- 서류 분류 선택 -->
    <div class="section-title">서류 분류 선택</div>
    <div class="document-types">
        <?php foreach ($categories as $category): ?>
            <label>
                <input type="checkbox" <?php echo in_array($category['id'], $selected_documents) ? 'checked' : ''; ?> onclick="return false;">
                <?php echo htmlspecialchars($category['name']); ?>
            </label>
        <?php endforeach; ?>
    </div>

    <!-- 관리번호 -->
    <div class="section-title">
        관리번호: <span class="management-no"><?php echo htmlspecialchars($request_data['request_no']); ?></span>
    </div>

    <!-- 의뢰처 -->
    <div class="section-title">의뢰처</div>
    <table>
        <tr>
            <th width="15%">공사명</th>
            <td colspan="2"><span class="input-field input-full"><?php echo htmlspecialchars($request_data['project_name']); ?></span></td>
            <th width="15%">시공방법</th>
            <td width="25%">
                <label><input type="checkbox" <?php echo ($details_data['construction_method'] == '단독') ? 'checked' : ''; ?> onclick="return false;">단독</label>
                <label><input type="checkbox" <?php echo ($details_data['construction_method'] == '공동') ? 'checked' : ''; ?> onclick="return false;">공동</label>
                <label><input type="checkbox" <?php echo ($details_data['construction_method'] == '분담') ? 'checked' : ''; ?> onclick="return false;">분담</label>
            </td>
        </tr>
        <tr>
            <th>회사명</th>
            <td colspan="2"><span class="input-field input-full"><?php echo htmlspecialchars($supplier_name); ?></span></td>
            <th>수주일</th>
            <td><span class="input-field input-full"><?php echo $details_data['order_date'] ? date('Y-m-d', strtotime($details_data['order_date'])) : ''; ?></span></td>
        </tr>
        <tr>
            <th rowspan="2">담당자</th>
            <th width="8%">담당</th>
            <td>
                <table class="inline-table">
                    <tr>
                        <td>이름: <span class="input-field"><?php echo htmlspecialchars($details_data['manager_name'] ?? ''); ?></span></td>
                        <td>연락처: <span class="input-field"><?php echo htmlspecialchars($details_data['manager_contact'] ?? ''); ?></span></td>
                    </tr>
                </table>
            </td>
            <th>이메일</th>
            <td><span class="input-field input-full"><?php echo htmlspecialchars($details_data['manager_email'] ?? ''); ?></span></td>
        </tr>
        <tr>
            <th>소장</th>
            <td colspan="3">
                <table class="inline-table">
                    <tr>
                        <td>이름: <span class="input-field"><?php echo htmlspecialchars($details_data['director_name'] ?? ''); ?></span></td>
                        <td>연락처: <span class="input-field"><?php echo htmlspecialchars($details_data['director_contact'] ?? ''); ?></span></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- 작성 대상 -->
    <div class="section-title">작성 대상</div>
    <table>
        <tr>
            <th width="20%">안전관리계획서</th>
            <td width="30%">
                <label><input type="checkbox" <?php echo ($targets_data['safety_plan_type'] == '1종') ? 'checked' : ''; ?> onclick="return false;">1종</label>
                <label><input type="checkbox" <?php echo ($targets_data['safety_plan_type'] == '2종') ? 'checked' : ''; ?> onclick="return false;">2종</label>
                <label><input type="checkbox" <?php echo ($targets_data['safety_plan_type'] == '기타') ? 'checked' : ''; ?> onclick="return false;">기타</label>
            </td>
            <th width="15%">검토처</th>
            <td><span class="input-field input-full"><?php echo htmlspecialchars($targets_data['review_agency'] ?? ''); ?></span></td>
        </tr>
        <tr>
            <th>유해위험방지계획서</th>
            <td><span class="input-field input-full"><?php echo htmlspecialchars($targets_data['hazard_prevention_type'] ?? ''); ?></span></td>
            <th>안전보건공단</th>
            <td><span class="input-field input-full"><?php echo htmlspecialchars($targets_data['safety_health_agency'] ?? ''); ?></span></td>
        </tr>
        <tr>
            <th>안전보건대장</th>
            <td><span class="input-field input-full"><?php echo htmlspecialchars($targets_data['safety_health_ledger_type'] ?? ''); ?></span></td>
            <th>적정성평가</th>
            <td><span class="input-field input-full"><?php echo htmlspecialchars($targets_data['evaluation_type'] ?? ''); ?></span></td>
        </tr>
        <tr>
            <th>교육시설</th>
            <td><span class="input-field input-full"><?php echo htmlspecialchars($targets_data['education_facility'] ?? ''); ?></span></td>
            <th>관할교육청</th>
            <td><span class="input-field input-full"><?php echo htmlspecialchars($targets_data['education_office'] ?? ''); ?></span></td>
        </tr>
        <tr>
            <th>철도보호지구</th>
            <td><span class="input-field input-full"><?php echo htmlspecialchars($targets_data['railway_protection'] ?? ''); ?></span></td>
            <th>철도보호지구관리자</th>
            <td><span class="input-field input-full"><?php echo htmlspecialchars($targets_data['railway_manager'] ?? ''); ?></span></td>
        </tr>
    </table>

    <!-- 작성 비용 (VAT 별도) -->
    <div class="section-title">작성 비용 (VAT 별도)</div>
    <table>
        <tr>
            <th width="20%">안전관리계획서</th>
            <td width="30%"><span class="input-field input-full"><?php echo $costs_data['safety_plan_cost'] ? number_format($costs_data['safety_plan_cost']) . '만원' : ''; ?></span></td>
            <th width="20%">유해위험방지계획서</th>
            <td width="30%"><span class="input-field input-full"><?php echo $costs_data['hazard_prevention_cost'] ? number_format($costs_data['hazard_prevention_cost']) . '만원' : ''; ?></span></td>
        </tr>
        <tr>
            <th>구조검토비</th>
            <td><span class="input-field input-full"><?php echo $costs_data['structure_review_cost'] ? number_format($costs_data['structure_review_cost']) . '만원' : ''; ?></span></td>
            <th>위탁처</th>
            <td><span class="input-field input-full"><?php echo htmlspecialchars($costs_data['structure_review_agency'] ?? ''); ?></span></td>
        </tr>
        <tr>
            <th>계획서검토비</th>
            <td><span class="input-field input-full"><?php echo $costs_data['plan_review_cost'] ? number_format($costs_data['plan_review_cost']) . '만원' : ''; ?></span></td>
            <th>검토처</th>
            <td><span class="input-field input-full"><?php echo htmlspecialchars($costs_data['plan_review_agency'] ?? ''); ?></span></td>
        </tr>
        <tr>
            <th>안전보건대장</th>
            <td><span class="input-field input-full"><?php echo $costs_data['safety_health_cost'] ? number_format($costs_data['safety_health_cost']) . '만원' : ''; ?></span></td>
            <th>교육시설</th>
            <td><span class="input-field input-full"><?php echo $costs_data['education_facility_cost'] ? number_format($costs_data['education_facility_cost']) . '만원' : ''; ?></span></td>
        </tr>
        <tr>
            <th>철도보호</th>
            <td><span class="input-field input-full"><?php echo $costs_data['railway_protection_cost'] ? number_format($costs_data['railway_protection_cost']) . '만원' : ''; ?></span></td>
            <th>적정성평가</th>
            <td><span class="input-field input-full"><?php echo htmlspecialchars($costs_data['evaluation_cost'] ?? ''); ?></span></td>
        </tr>
        <tr>
            <th>종합계</th>
            <td colspan="3">
                <span class="input-field" style="min-width: 150px;">
                    <?php
                    echo number_format($details_data['total_cost']) . '만원';
                    ?>
                </span>
                <label style="margin-left: 20px;">
                    <input type="radio" <?php echo ($details_data['vat_included'] == 1) ? 'checked' : ''; ?> onclick="return false;"> VAT 포함
                </label>
                <label>
                    <input type="radio" <?php echo ($details_data['vat_included'] == 0) ? 'checked' : ''; ?> onclick="return false;"> VAT 별도
                </label>
            </td>
        </tr>
    </table>

    <!-- 작성자 -->
    <div class="section-title">작성자</div>
    <table>
        <tr>
            <th width="20%">주관자(의뢰처접담)</th>
            <td width="30%"><span class="input-field input-full"><?php echo htmlspecialchars($writers_data['main_writer'] ?? ''); ?></span></td>
            <th width="20%">수주관리/소개/수당</th>
            <td width="30%"><span class="input-field input-full"><?php echo htmlspecialchars($writers_data['revenue_manager'] ?? ''); ?></span></td>
        </tr>
        <tr>
            <th>분야별 작성자</th>
            <td colspan="3"><span class="input-field input-full"><?php echo htmlspecialchars($writers_data['field_writers'] ?? ''); ?></span></td>
        </tr>
    </table>

    <!-- 검토 접수 관련 사항 (대행업무시 필요) -->
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
            <td>ID: <span class="input-field" style="width: 80%;"><?php echo htmlspecialchars($review_data['csi_id'] ?? ''); ?></span></td>
            <td><span class="input-field input-full"><?php echo htmlspecialchars($review_data['csi_supervisor'] ?? ''); ?></span></td>
            <td><span class="input-field input-full"><?php echo htmlspecialchars($review_data['csi_client'] ?? ''); ?></span></td>
        </tr>
        <tr>
            <td>비번: <span class="input-field" style="width: 80%;">****</span></td>
            <td><span class="input-field input-full"><?php echo htmlspecialchars($review_data['csi_supervisor_info'] ?? ''); ?></span></td>
            <td><span class="input-field input-full"><?php echo htmlspecialchars($review_data['csi_client_info'] ?? ''); ?></span></td>
        </tr>
        <tr>
            <th>안전보건공단</th>
            <td>ID: <span class="input-field" style="width: 80%;"><?php echo htmlspecialchars($review_data['kosha_id'] ?? ''); ?></span></td>
            <td>비번: <span class="input-field" style="width: 80%;">****</span></td>
            <td>기타사항: <span class="input-field" style="width: 70%;"><?php echo htmlspecialchars($review_data['kosha_notes'] ?? ''); ?></span></td>
        </tr>
    </table>

    <!-- 추가 요청사항 -->
    <div class="section-title">추가 요청사항</div>
    <textarea readonly><?php echo htmlspecialchars($request_data['additional_notes'] ?? ''); ?></textarea>

    <!-- 인쇄 버튼 (인쇄 시 숨김) -->
    <div class="no-print" style="text-align: center; margin: 20px 0;">
        <button onclick="window.print();">인쇄하기</button>
        <button onclick="window.close();" style="margin-left: 10px;">닫기</button>
    </div>
</div>

<script>
    // 페이지 로드 시 자동 인쇄
    window.onload = function() {
        // 0.3초 대기 후 인쇄 (페이지 렌더링 완료 대기)
        setTimeout(function() {
            window.print();
        }, 300);
    };

    // 인쇄 대화상자가 닫히면 창 닫기 (선택사항)
    window.onafterprint = function() {
        // 인쇄 후 자동으로 창을 닫으려면 아래 주석 해제
        // window.close();
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
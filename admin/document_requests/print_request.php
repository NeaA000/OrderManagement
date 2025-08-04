<?php
// 요청 ID 확인
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('잘못된 접근입니다.'); window.close();</script>";
    exit;
}

$request_id = $_GET['id'];

// 데이터 조회
$stmt = $conn->prepare("
    SELECT r.*, s.name as supplier_name
    FROM document_requests r
    LEFT JOIN supplier_list s ON r.supplier_id = s.id
    WHERE r.id = ?
");
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
$stmt = $conn->prepare("
    SELECT rd.*, dc.name as doc_name, dc.parent_id 
    FROM request_documents rd 
    LEFT JOIN document_categories dc ON rd.category_id = dc.id 
    WHERE rd.request_id = ?
");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();
$selected_documents = [];
while ($row = $result->fetch_assoc()) {
    $selected_documents[] = $row['doc_name'];
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>서류 요청서 인쇄 - <?php echo $request_data['request_no']; ?></title>
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Malgun Gothic', sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #000;
        }

        .container {
            width: 100%;
            max-width: 190mm;
            margin: 0 auto;
        }

        h1 {
            text-align: center;
            font-size: 18px;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 2px solid #000;
        }

        .section-title {
            background-color: #e0e0e0;
            padding: 3px 8px;
            margin: 8px 0 3px 0;
            font-weight: bold;
            font-size: 12px;
            border-left: 3px solid #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        th, td {
            border: 1px solid #000;
            padding: 2px 4px;
            font-size: 10px;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
            width: 15%;
        }

        td {
            text-align: left;
        }

        .management-no {
            text-align: right;
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 11px;
        }

        .selected-docs {
            margin: 5px 0;
            padding: 5px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            font-size: 10px;
        }

        .selected-docs h4 {
            font-size: 11px;
            margin-bottom: 3px;
        }

        .doc-list {
            column-count: 3;
            column-gap: 10px;
            font-size: 9px;
            line-height: 1.4;
        }

        .doc-list span {
            display: block;
            break-inside: avoid;
        }

        .notes {
            border: 1px solid #000;
            padding: 5px;
            min-height: 30px;
            font-size: 10px;
            margin-bottom: 5px;
        }

        /* 2열 레이아웃 */
        .two-col {
            display: flex;
            gap: 10px;
        }

        .two-col > div {
            flex: 1;
        }

        /* 비용 테이블 특별 처리 */
        .cost-table td {
            text-align: right;
        }

        /* 인쇄 시 페이지 분리 방지 */
        .no-break {
            page-break-inside: avoid;
        }

        @media print {
            body {
                margin: 0;
            }

            .container {
                width: 100%;
                max-width: none;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>서류 요청서</h1>

    <div class="management-no">관리번호: <?php echo htmlspecialchars($request_data['request_no']); ?></div>

    <!-- 선택된 서류 목록 -->
    <?php if (!empty($selected_documents)): ?>
        <div class="selected-docs no-break">
            <h4>요청 서류 목록</h4>
            <div class="doc-list">
                <?php foreach ($selected_documents as $doc): ?>
                    <span>• <?php echo htmlspecialchars($doc); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- 의뢰처 정보 -->
    <div class="section-title">의뢰처</div>
    <table class="no-break">
        <tr>
            <th>공사명</th>
            <td colspan="2"><?php echo htmlspecialchars($request_data['project_name']); ?></td>
            <th>시공방법</th>
            <td><?php echo htmlspecialchars($details_data['construction_method'] ?? ''); ?></td>
        </tr>
        <tr>
            <th>회사명</th>
            <td colspan="2"><?php echo htmlspecialchars($request_data['supplier_name']); ?></td>
            <th>수주일</th>
            <td><?php echo $details_data['order_date'] ? date('Y-m-d', strtotime($details_data['order_date'])) : ''; ?></td>
        </tr>
        <tr>
            <th>담당자</th>
            <td><?php echo htmlspecialchars($details_data['manager_name'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($details_data['manager_contact'] ?? ''); ?></td>
            <th>이메일</th>
            <td><?php echo htmlspecialchars($details_data['manager_email'] ?? ''); ?></td>
        </tr>
        <tr>
            <th>소장</th>
            <td colspan="2"><?php echo htmlspecialchars($details_data['director_name'] ?? ''); ?></td>
            <td colspan="2"><?php echo htmlspecialchars($details_data['director_contact'] ?? ''); ?></td>
        </tr>
    </table>

    <!-- 작성 대상과 비용을 2열로 배치 -->
    <div class="two-col no-break">
        <div>
            <div class="section-title">작성 대상</div>
            <table>
                <tr>
                    <th>안전관리계획서</th>
                    <td><?php echo htmlspecialchars($targets_data['safety_plan_type'] ?? ''); ?></td>
                </tr>
                <tr>
                    <th>검토처</th>
                    <td><?php echo htmlspecialchars($targets_data['review_agency'] ?? ''); ?></td>
                </tr>
                <tr>
                    <th>유해위험방지</th>
                    <td><?php echo htmlspecialchars($targets_data['hazard_prevention_type'] ?? ''); ?></td>
                </tr>
                <tr>
                    <th>안전보건공단</th>
                    <td><?php echo htmlspecialchars($targets_data['safety_health_agency'] ?? ''); ?></td>
                </tr>
                <tr>
                    <th>안전보건대장</th>
                    <td><?php echo htmlspecialchars($targets_data['safety_health_ledger_type'] ?? ''); ?></td>
                </tr>
                <tr>
                    <th>적정성평가</th>
                    <td><?php echo htmlspecialchars($targets_data['evaluation_type'] ?? ''); ?></td>
                </tr>
                <tr>
                    <th>교육시설</th>
                    <td><?php echo htmlspecialchars($targets_data['education_facility'] ?? ''); ?></td>
                </tr>
                <tr>
                    <th>철도보호지구</th>
                    <td><?php echo htmlspecialchars($targets_data['railway_protection'] ?? ''); ?></td>
                </tr>
            </table>
        </div>

        <div>
            <div class="section-title">작성 비용</div>
            <table class="cost-table">
                <tr>
                    <th>안전관리계획서</th>
                    <td><?php echo $costs_data['safety_plan_cost'] ? number_format($costs_data['safety_plan_cost']) . '만원' : ''; ?></td>
                </tr>
                <tr>
                    <th>유해위험방지</th>
                    <td><?php echo $costs_data['hazard_prevention_cost'] ? number_format($costs_data['hazard_prevention_cost']) . '만원' : ''; ?></td>
                </tr>
                <tr>
                    <th>구조검토비</th>
                    <td><?php echo $costs_data['structure_review_cost'] ? number_format($costs_data['structure_review_cost']) . '만원' : ''; ?></td>
                </tr>
                <tr>
                    <th>계획서검토비</th>
                    <td><?php echo $costs_data['plan_review_cost'] ? number_format($costs_data['plan_review_cost']) . '만원' : ''; ?></td>
                </tr>
                <tr>
                    <th>안전보건대장</th>
                    <td><?php echo $costs_data['safety_health_cost'] ? number_format($costs_data['safety_health_cost']) . '만원' : ''; ?></td>
                </tr>
                <tr>
                    <th>교육시설</th>
                    <td><?php echo $costs_data['education_facility_cost'] ? number_format($costs_data['education_facility_cost']) . '만원' : ''; ?></td>
                </tr>
                <tr>
                    <th>철도보호</th>
                    <td><?php echo $costs_data['railway_protection_cost'] ? number_format($costs_data['railway_protection_cost']) . '만원' : ''; ?></td>
                </tr>
                <tr style="font-weight: bold; background-color: #f0f0f0;">
                    <th>종합계</th>
                    <td>
                        <?php
                        echo number_format($details_data['total_cost']) . '만원';
                        echo $details_data['vat_included'] ? ' (VAT포함)' : ' (VAT별도)';
                        ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- 작성자 정보 -->
    <div class="section-title">작성자</div>
    <table class="no-break">
        <tr>
            <th width="25%">주관자</th>
            <td width="25%"><?php echo htmlspecialchars($writers_data['main_writer'] ?? ''); ?></td>
            <th width="25%">수주관리/소개/수당</th>
            <td width="25%"><?php echo htmlspecialchars($writers_data['revenue_manager'] ?? ''); ?></td>
        </tr>
        <?php if (!empty($writers_data['field_writers'])): ?>
            <tr>
                <th>분야별 작성자</th>
                <td colspan="3"><?php echo htmlspecialchars($writers_data['field_writers']); ?></td>
            </tr>
        <?php endif; ?>
    </table>

    <!-- 검토 기관 정보 (있는 경우만) -->
    <?php if ($review_data && (!empty($review_data['csi_id']) || !empty($review_data['kosha_id']))): ?>
        <div class="section-title">검토 기관 정보</div>
        <table class="no-break">
            <?php if (!empty($review_data['csi_id'])): ?>
                <tr>
                    <th rowspan="2">CSI</th>
                    <th width="10%">ID</th>
                    <td><?php echo htmlspecialchars($review_data['csi_id']); ?></td>
                    <th width="15%">감리단</th>
                    <td><?php echo htmlspecialchars($review_data['csi_supervisor'] ?? ''); ?></td>
                </tr>
                <tr>
                    <th>발주처</th>
                    <td colspan="3"><?php echo htmlspecialchars($review_data['csi_client'] ?? ''); ?></td>
                </tr>
            <?php endif; ?>
            <?php if (!empty($review_data['kosha_id'])): ?>
                <tr>
                    <th>안전보건공단</th>
                    <th>ID</th>
                    <td><?php echo htmlspecialchars($review_data['kosha_id']); ?></td>
                    <th>비고</th>
                    <td><?php echo htmlspecialchars($review_data['kosha_notes'] ?? ''); ?></td>
                </tr>
            <?php endif; ?>
        </table>
    <?php endif; ?>

    <!-- 추가 요청사항 -->
    <?php if (!empty($request_data['additional_notes'])): ?>
        <div class="section-title">추가 요청사항</div>
        <div class="notes no-break">
            <?php echo nl2br(htmlspecialchars($request_data['additional_notes'])); ?>
        </div>
    <?php endif; ?>

    <!-- 작성일시 -->
    <div style="text-align: right; margin-top: 10px; font-size: 10px;">
        작성일시: <?php echo date('Y-m-d H:i', strtotime($request_data['created_at'])); ?>
    </div>
</div>

<script>
    // 페이지 로드 시 자동 인쇄
    window.onload = function() {
        window.print();

        // 인쇄 대화상자가 닫히면 창 닫기
        window.onafterprint = function() {
            window.close();
        };
    };
</script>
</body>
</html>
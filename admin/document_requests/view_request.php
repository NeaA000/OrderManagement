<?php
// 데이터베이스 연결 - AdminLTE에서는 config.php가 이미 로드되어 있으므로 경로 수정 불필요
// $conn 변수를 직접 사용

// ID 파라미터 확인
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('잘못된 접근입니다.'); location.href='./?page=document_requests';</script>";
    exit;
}

$request_id = $_GET['id'];

// 요청 기본 정보 조회 - status = 1로 수정 (숫자)
$stmt = $conn->prepare("
    SELECT r.*, s.name as supplier_name, s.contact_person, s.contact, s.email,
           u.firstname, u.lastname,
           (SELECT COUNT(*) FROM request_documents WHERE request_id = r.id) as total_docs,
           (SELECT COUNT(*) FROM request_documents WHERE request_id = r.id AND status = 1) as completed_docs
    FROM document_requests r
    LEFT JOIN supplier_list s ON r.supplier_id = s.id
    LEFT JOIN users u ON r.created_by = u.id
    WHERE r.id = ?
");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();

if (!$request) {
    echo "<script>alert('요청을 찾을 수 없습니다.'); location.href='./?page=document_requests';</script>";
    exit;
}

// 상세 정보 조회
$details_stmt = $conn->prepare("SELECT * FROM document_request_details WHERE request_id = ?");
$details_stmt->bind_param("i", $request_id);
$details_stmt->execute();
$details = $details_stmt->get_result()->fetch_assoc();

// 작성 대상 정보 조회
$targets_stmt = $conn->prepare("SELECT * FROM document_targets WHERE request_id = ?");
$targets_stmt->bind_param("i", $request_id);
$targets_stmt->execute();
$targets = $targets_stmt->get_result()->fetch_assoc();

// 비용 정보 조회
$costs_stmt = $conn->prepare("SELECT * FROM document_cost_details WHERE request_id = ?");
$costs_stmt->bind_param("i", $request_id);
$costs_stmt->execute();
$costs = $costs_stmt->get_result()->fetch_assoc();

// 작성자 정보 조회
$writers_stmt = $conn->prepare("SELECT * FROM document_writers WHERE request_id = ?");
$writers_stmt->bind_param("i", $request_id);
$writers_stmt->execute();
$writers = $writers_stmt->get_result()->fetch_assoc();

// 요청된 서류 목록 조회
$docs_stmt = $conn->prepare("
    SELECT rd.*, dc.name as category_name,
           (SELECT COUNT(*) FROM document_uploads WHERE document_id = rd.id) as upload_count,
           uf.document_name as wasabi_document_name
    FROM request_documents rd
    LEFT JOIN document_categories dc ON rd.category_id = dc.id
    LEFT JOIN uploaded_files uf ON uf.document_id = rd.id AND uf.is_deleted = 0
    WHERE rd.request_id = ?
    ORDER BY dc.display_order, rd.id, uf.id DESC
");
$docs_stmt->bind_param("i", $request_id);
$docs_stmt->execute();
$documents = $docs_stmt->get_result();

// 워크플로우 이력 조회
$workflow_stmt = $conn->prepare("
    SELECT w.*, u.firstname, u.lastname
    FROM workflow_status w
    LEFT JOIN users u ON w.assigned_to = u.id
    WHERE w.request_id = ?
    ORDER BY w.started_at DESC
");
$workflow_stmt->bind_param("i", $request_id);
$workflow_stmt->execute();
$workflows = $workflow_stmt->get_result();

// 진행률 계산
$progress = $request['total_docs'] > 0 ? round(($request['completed_docs'] / $request['total_docs']) * 100) : 0;
?>

<style>
    .info-box {
        min-height: 60px;
    }
    .info-box-icon {
        width: 60px;
        font-size: 30px;
    }
    .timeline-item {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        padding: 10px;
        margin-bottom: 10px;
    }
    .document-item {
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        padding: 15px;
        margin-bottom: 10px;
        background: #fff;
    }
    .document-item.completed {
        background: #d4edda;
        border-color: #c3e6cb;
    }
    .cost-table td {
        padding: 8px !important;
    }
    .detail-label {
        font-weight: bold;
        color: #495057;
    }
</style>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">서류 요청 상세 정보</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" onclick="window.print()">
                <i class="fas fa-print"></i> 인쇄
            </button>
            <a href="./?page=document_requests/manage_request&id=<?php echo $request_id ?>" class="btn btn-flat btn-sm btn-primary">
                <i class="fas fa-edit"></i> 수정
            </a>
            <a href="./?page=document_requests" class="btn btn-flat btn-sm btn-default">
                <i class="fas fa-arrow-left"></i> 목록으로
            </a>
        </div>
    </div>
    <div class="card-body">
        <!-- 진행 상태 요약 -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="info-box">
                    <span class="info-box-icon bg-info"><i class="fas fa-file-alt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">전체 서류</span>
                        <span class="info-box-number"><?php echo $request['total_docs'] ?>개</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box">
                    <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">완료 서류</span>
                        <span class="info-box-number"><?php echo $request['completed_docs'] ?>개</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box">
                    <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">진행률</span>
                        <span class="info-box-number"><?php echo $progress ?>%</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box">
                    <?php
                    $status_icon = 'fa-hourglass-half';
                    $status_color = 'bg-secondary';
                    $status_text = '대기';

                    if($request['status'] == 1) {
                        $status_icon = 'fa-spinner';
                        $status_color = 'bg-primary';
                        $status_text = '진행중';
                    } elseif($request['status'] == 2) {
                        $status_icon = 'fa-check-double';
                        $status_color = 'bg-success';
                        $status_text = '완료';
                    }
                    ?>
                    <span class="info-box-icon <?php echo $status_color ?>"><i class="fas <?php echo $status_icon ?>"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">상태</span>
                        <span class="info-box-number"><?php echo $status_text ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 탭 메뉴 -->
        <ul class="nav nav-tabs" id="custom-tabs">
            <li class="nav-item">
                <a class="nav-link active" data-toggle="pill" href="#tab-basic">기본 정보</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="pill" href="#tab-documents">요청 서류</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="pill" href="#tab-costs">비용 정보</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="pill" href="#tab-workflow">진행 이력</a>
            </li>
        </ul>

        <div class="tab-content mt-3">
            <!-- 기본 정보 탭 -->
            <div class="tab-pane fade show active" id="tab-basic">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <td class="detail-label" width="30%">요청번호</td>
                                <td><?php echo $request['request_no'] ?></td>
                            </tr>
                            <tr>
                                <td class="detail-label">프로젝트명</td>
                                <td><?php echo $request['project_name'] ?></td>
                            </tr>
                            <tr>
                                <td class="detail-label">의뢰처</td>
                                <td><?php echo $request['supplier_name'] ?></td>
                            </tr>
                            <tr>
                                <td class="detail-label">시공방법</td>
                                <td><?php echo $details['construction_method'] ?? '-' ?></td>
                            </tr>
                            <tr>
                                <td class="detail-label">수주일</td>
                                <td><?php echo $details['order_date'] ? date("Y-m-d", strtotime($details['order_date'])) : '-' ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <td class="detail-label" width="30%">담당자</td>
                                <td><?php echo $details['manager_name'] ?? '-' ?></td>
                            </tr>
                            <tr>
                                <td class="detail-label">담당자 연락처</td>
                                <td><?php echo $details['manager_contact'] ?? '-' ?></td>
                            </tr>
                            <tr>
                                <td class="detail-label">담당자 이메일</td>
                                <td><?php echo $details['manager_email'] ?? '-' ?></td>
                            </tr>
                            <tr>
                                <td class="detail-label">소장</td>
                                <td><?php echo $details['director_name'] ?? '-' ?></td>
                            </tr>
                            <tr>
                                <td class="detail-label">작성자</td>
                                <td><?php echo $request['firstname'] . ' ' . $request['lastname'] ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <?php if ($request['additional_notes']): ?>
                    <div class="mt-3">
                        <h5>추가 요청사항</h5>
                        <div class="alert alert-info">
                            <?php echo nl2br(htmlspecialchars($request['additional_notes'])) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 요청 서류 탭 -->
            <div class="tab-pane fade" id="tab-documents">
                <div class="mb-3">
                    <a href="<?php echo base_url ?>admin/upload_portal/?token=<?php echo $request['upload_token'] ?>"
                       target="_blank" class="btn btn-info btn-sm">
                        <i class="fas fa-external-link-alt"></i> 업로드 페이지 열기
                    </a>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="copyUploadLink()">
                        <i class="fas fa-copy"></i> 링크 복사
                    </button>
                </div>

                <?php while($doc = $documents->fetch_assoc()): ?>
                    <div class="document-item <?php echo $doc['status'] == 1 ? 'completed' : '' ?>">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h6 class="mb-1">
                                    <?php echo $doc['document_name'] ?>
                                    <?php if($doc['is_required'] == 1): ?>
                                        <span class="badge badge-danger ml-2">필수</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary ml-2">선택</span>
                                    <?php endif; ?>
                                </h6>
                                <small class="text-muted">카테고리: <?php echo $doc['category_name'] ?></small>
                            </div>
                            <div class="col-md-3">
                                <?php if ($doc['status'] == 1): ?>
                                    <span class="badge badge-success">
                                        <i class="fas fa-check-circle"></i> 제출완료
                                    </span>
                                    <?php if($doc['uploaded_at']): ?>
                                        <br><small class="text-muted"><?php echo date("Y-m-d H:i", strtotime($doc['uploaded_at'])) ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge badge-warning">
                                        <i class="fas fa-clock"></i> 미제출
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3 text-right">
                                <?php if ($doc['status'] == 1 && $doc['file_path']): ?>
                                    <button type="button"
                                            class="btn btn-sm btn-primary"
                                            onclick="downloadFile(<?php echo $doc['id'] ?>)">
                                        <i class="fas fa-download"></i> 다운로드
                                    </button>
                                    <button type="button"
                                            class="btn btn-sm btn-info"
                                            onclick="previewFile('<?php echo $doc['id'] ?>')">
                                        <i class="fas fa-eye"></i> 미리보기
                                    </button>
                                <?php elseif ($doc['upload_count'] > 0): ?>
                                    <button type="button" class="btn btn-sm btn-primary" onclick="viewUploads(<?php echo $doc['id'] ?>)">
                                        <i class="fas fa-file"></i> 파일 보기 (<?php echo $doc['upload_count'] ?>)
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">업로드 대기중</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- 비용 정보 탭 -->
            <div class="tab-pane fade" id="tab-costs">
                <table class="table table-bordered cost-table">
                    <tr>
                        <td class="detail-label" width="25%">안전관리계획서</td>
                        <td><?php echo $costs['safety_plan_cost'] ? number_format($costs['safety_plan_cost']) . '만원' : '-' ?></td>
                        <td class="detail-label" width="25%">유해위험방지계획서</td>
                        <td><?php echo $costs['hazard_prevention_cost'] ? number_format($costs['hazard_prevention_cost']) . '만원' : '-' ?></td>
                    </tr>
                    <tr>
                        <td class="detail-label">구조검토비</td>
                        <td><?php echo $costs['structure_review_cost'] ? number_format($costs['structure_review_cost']) . '만원' : '-' ?></td>
                        <td class="detail-label">계획서검토비</td>
                        <td><?php echo $costs['plan_review_cost'] ? number_format($costs['plan_review_cost']) . '만원' : '-' ?></td>
                    </tr>
                    <tr>
                        <td class="detail-label">안전보건대장</td>
                        <td><?php echo $costs['safety_health_cost'] ? number_format($costs['safety_health_cost']) . '만원' : '-' ?></td>
                        <td class="detail-label">교육시설</td>
                        <td><?php echo $costs['education_facility_cost'] ? number_format($costs['education_facility_cost']) . '만원' : '-' ?></td>
                    </tr>
                    <tr>
                        <td class="detail-label">철도보호</td>
                        <td><?php echo $costs['railway_protection_cost'] ? number_format($costs['railway_protection_cost']) . '만원' : '-' ?></td>
                        <td class="detail-label">적정성평가</td>
                        <td><?php echo $costs['evaluation_cost'] ?? '-' ?></td>
                    </tr>
                    <tr class="bg-light">
                        <td class="detail-label">종합계</td>
                        <td colspan="3">
                            <strong>
                                <?php
                                if($details['total_cost']) {
                                    echo number_format($details['total_cost']) . '만원';
                                    echo $details['vat_included'] ? ' (VAT 포함)' : ' (VAT 별도)';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </strong>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- 진행 이력 탭 -->
            <div class="tab-pane fade" id="tab-workflow">
                <div class="timeline">
                    <?php while($workflow = $workflows->fetch_assoc()): ?>
                        <div class="timeline-item">
                            <div class="row">
                                <div class="col-md-3">
                                    <small class="text-muted">
                                        <?php echo date("Y-m-d H:i", strtotime($workflow['started_at'])) ?>
                                    </small>
                                </div>
                                <div class="col-md-6">
                                    <strong><?php echo $workflow['step_name'] ?></strong><br>
                                    <?php echo $workflow['step_description'] ?>
                                    <?php if($workflow['notes']): ?>
                                        <br><small class="text-muted"><?php echo $workflow['notes'] ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-3 text-right">
                                    <?php if($workflow['firstname'] && $workflow['lastname']): ?>
                                        <small>담당자: <?php echo $workflow['firstname'] . ' ' . $workflow['lastname'] ?></small>
                                    <?php endif; ?>
                                    <?php if($workflow['is_current'] == 1): ?>
                                        <br><span class="badge badge-primary">현재 단계</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 파일 보기 모달 -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">업로드된 파일</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="uploadModalBody">
                <!-- 파일 목록이 여기에 표시됩니다 -->
            </div>
        </div>
    </div>
</div>

<script>
    function copyUploadLink() {
        const link = '<?php echo base_url ?>admin/upload_portal/?token=<?php echo $request['upload_token'] ?>';

        // 임시 텍스트 영역 생성
        const textarea = document.createElement('textarea');
        textarea.value = link;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);

        alert('업로드 링크가 복사되었습니다.');
    }

    function downloadFile(documentId) {
        // download.php 경로를 정확하게 지정 - internal_download 파라미터 추가
        const downloadUrl = '<?php echo base_url ?>admin/upload_portal/download.php?id=' + documentId + '&internal_download=1';

        // 새 창에서 다운로드 (현재 페이지 유지)
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.target = '_blank';
        link.click();
    }

    function viewUploads(documentId) {
        // AJAX로 업로드된 파일 목록 가져오기
        $.ajax({
            url: 'document_requests/get_uploads.php',
            method: 'GET',
            data: { document_id: documentId },
            dataType: 'json',  // JSON 응답 기대
            success: function(response) {
                if(response.status === 'success') {
                    // 파일 목록 HTML 생성
                    let html = '<div class="table-responsive"><table class="table table-bordered">';
                    html += '<thead><tr><th>파일명</th><th>크기</th><th>업로드 일시</th><th>작업</th></tr></thead><tbody>';

                    if(response.data && response.data.length > 0) {
                        response.data.forEach(function(file) {
                            html += '<tr>';
                            html += '<td><i class="fas fa-file"></i> ' + file.file_name + '</td>';
                            html += '<td>' + file.file_size + '</td>';
                            html += '<td>' + file.uploaded_at + '</td>';
                            html += '<td>';
                            html += '<a href="' + file.download_url + '" class="btn btn-sm btn-primary" target="_blank">';
                            html += '<i class="fas fa-download"></i> 다운로드</a>';
                            if(file.preview_url) {
                                html += ' <a href="' + file.preview_url + '" class="btn btn-sm btn-info" target="_blank">';
                                html += '<i class="fas fa-eye"></i> 미리보기</a>';
                            }
                            html += '</td>';
                            html += '</tr>';
                        });
                    } else {
                        html += '<tr><td colspan="4" class="text-center text-muted">업로드된 파일이 없습니다.</td></tr>';
                    }

                    html += '</tbody></table></div>';

                    $('#uploadModalBody').html(html);
                    $('#uploadModal').modal('show');
                } else {
                    // 에러 응답 처리
                    alert('파일 목록을 불러오는데 실패했습니다: ' + (response.msg || '알 수 없는 오류'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response:', xhr.responseText);

                // 응답이 HTML인 경우 (PHP 에러 등)
                if(xhr.responseText && xhr.responseText.includes('<')) {
                    $('#uploadModalBody').html('<div class="alert alert-danger">서버 오류가 발생했습니다.</div>');
                    $('#uploadModal').modal('show');
                } else {
                    alert('파일 목록을 불러오는데 실패했습니다.');
                }
            }
        });
    }

    function previewFile(documentId) {
        // view_file.php를 통해 파일 보기 - internal_download 파라미터 추가
        window.open('<?php echo base_url ?>admin/upload_portal/view_file.php?id=' + documentId + '&internal_download=1', '_blank');
    }
</script>
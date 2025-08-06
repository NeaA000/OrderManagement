<?php
if(isset($_GET['id'])){
    $supplier = $conn->query("SELECT * FROM supplier_list WHERE id = '{$_GET['id']}'")->fetch_assoc();
}

// 의뢰처가 없으면 목록으로 리다이렉트
if(!$supplier){
    echo "<script>alert('의뢰처를 찾을 수 없습니다.'); location.replace('./?page=suppliers');</script>";
    exit;
}

// 필터 상태
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-building"></i> <?php echo $supplier['name'] ?> - 공사별 서류 현황
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="./?page=suppliers">의뢰처 관리</a></li>
                    <li class="breadcrumb-item active"><?php echo $supplier['name'] ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- 의뢰처 정보 카드 -->
        <div class="card card-default">
            <div class="card-header">
                <h3 class="card-title">의뢰처 정보</h3>
                <div class="card-tools d-flex align-items-center">
                    <!-- 필터 추가 -->
                    <select class="form-control form-control-sm mr-2" style="width: 150px;" onchange="filterByStatus(this.value)">
                        <option value="">전체 공사</option>
                        <option value="0" <?php echo $status_filter === '0' ? 'selected' : '' ?>>대기중</option>
                        <option value="1" <?php echo $status_filter === '1' ? 'selected' : '' ?>>진행중</option>
                        <option value="2" <?php echo $status_filter === '2' ? 'selected' : '' ?>>완료</option>
                    </select>

                    <button type="button" class="btn btn-sm btn-default" onclick="location.href='./?page=suppliers'">
                        <i class="fa fa-arrow-left"></i> 목록으로
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" onclick="editSupplier(<?php echo $supplier['id'] ?>)">
                        <i class="fa fa-edit"></i> 의뢰처 수정
                    </button>
                    <button type="button" class="btn btn-sm btn-success" onclick="createNewRequest(<?php echo $supplier['id'] ?>)">
                        <i class="fa fa-plus"></i> 새 서류요청
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <p class="mb-1"><strong>담당자:</strong> <?php echo $supplier['contact_person'] ?></p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1"><strong>연락처:</strong> <?php echo $supplier['contact'] ?></p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1"><strong>이메일:</strong> <?php echo $supplier['email'] ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 공사 목록 -->
        <?php
        $where_clause = "WHERE dr.supplier_id = '{$_GET['id']}'";
        if($status_filter !== ''){
            $where_clause .= " AND dr.status = '{$status_filter}'";
        }

        $projects = $conn->query("SELECT dr.*, 
            (SELECT COUNT(*) FROM request_documents WHERE request_id = dr.id) as total_docs,
            (SELECT COUNT(*) FROM request_documents WHERE request_id = dr.id AND status = 1) as submitted_docs,
            (SELECT current_step FROM workflow_status WHERE request_id = dr.id AND is_current = 1 LIMIT 1) as workflow_step
            FROM document_requests dr 
            {$where_clause}
            ORDER BY dr.date_created DESC");

        if($projects && $projects->num_rows > 0):
            while($project = $projects->fetch_assoc()):
                $progress = $project['total_docs'] > 0 ? round(($project['submitted_docs'] / $project['total_docs']) * 100) : 0;

                // 워크플로우 단계별 라벨
                $workflow_labels = [
                    'created' => ['text' => '생성됨', 'color' => 'secondary'],
                    'documents_requested' => ['text' => '서류요청', 'color' => 'info'],
                    'in_progress' => ['text' => '진행중', 'color' => 'primary'],
                    'under_review' => ['text' => '검토중', 'color' => 'warning'],
                    'completed' => ['text' => '완료', 'color' => 'success'],
                    'rejected' => ['text' => '반려', 'color' => 'danger']
                ];

                $current_workflow = $workflow_labels[$project['workflow_step']] ?? $workflow_labels['created'];
                ?>
                <div class="card shadow-sm mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-hard-hat"></i> <?php echo $project['project_name'] ?>
                            <span class="badge badge-light float-right">
                                <?php echo $current_workflow['text'] ?>
                            </span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- 프로젝트 정보 -->
                        <div class="row mb-3">
                            <div class="col-6 col-md-3">
                                <div class="description-block border-right">
                                    <span class="description-text">요청번호</span>
                                    <h5 class="description-header"><?php echo $project['request_no'] ?></h5>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="description-block border-right">
                                    <span class="description-text">요청일</span>
                                    <h5 class="description-header"><?php echo date("Y-m-d", strtotime($project['date_created'])) ?></h5>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="description-block">
                                    <span class="description-text">진행률</span>
                                    <div class="progress progress-sm mt-1 mb-0">
                                        <div class="progress-bar bg-<?php echo $progress == 100 ? 'success' : ($progress >= 50 ? 'warning' : 'danger') ?>"
                                             style="width: <?php echo $progress ?>%">
                                        </div>
                                    </div>
                                    <span class="progress-description">
                                        <?php echo $project['submitted_docs'] ?>/<?php echo $project['total_docs'] ?> (<?php echo $progress ?>%)
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- 서류 상세 현황 -->
                        <h6 class="mb-2"><i class="fas fa-file-alt"></i> 서류 제출 현황</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-striped">
                                <thead class="thead-light">
                                <tr>
                                    <th width="5%" class="text-center">#</th>
                                    <th width="35%">서류명</th>
                                    <th width="10%" class="text-center">필수여부</th>
                                    <th width="15%" class="text-center">제출상태</th>
                                    <th width="15%" class="text-center">업무진행상태</th>
                                    <th width="15%" class="text-center">제출일시</th>
                                    <th width="5%" class="text-center">파일</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $docs = $conn->query("SELECT rd.*, dc.name as category_name 
                                    FROM request_documents rd 
                                    LEFT JOIN document_categories dc ON rd.category_id = dc.id 
                                    WHERE rd.request_id = '{$project['id']}' 
                                    ORDER BY rd.is_required DESC, dc.name ASC");

                                if($docs && $docs->num_rows > 0):
                                    $doc_no = 1;
                                    while($doc = $docs->fetch_assoc()):
                                        ?>
                                        <tr>
                                            <td class="text-center"><?php echo $doc_no++ ?></td>
                                            <td>
                                                <?php echo $doc['document_name'] ?>
                                                <small class="text-muted d-block"><?php echo $doc['category_name'] ?></small>
                                            </td>
                                            <td class="text-center">
                                                <?php if($doc['is_required']): ?>
                                                    <span class="badge badge-danger">필수</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">선택</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if($doc['status'] == 1): ?>
                                                    <span class="badge badge-success"><i class="fa fa-check"></i> 제출완료</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger"><i class="fa fa-times"></i> 미제출</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                            <span class="badge badge-<?php echo $current_workflow['color'] ?>">
                                                <?php echo $current_workflow['text'] ?>
                                            </span>
                                            </td>
                                            <td class="text-center">
                                                <?php echo $doc['uploaded_at'] ? date("Y-m-d H:i", strtotime($doc['uploaded_at'])) : '-' ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if($doc['file_name']): ?>
                                                    <button class="btn btn-xs btn-info" onclick="downloadFile(<?php echo $doc['id'] ?>)">
                                                        <i class="fa fa-download"></i>
                                                    </button>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php
                                    endwhile;
                                else:
                                    ?>
                                    <tr>
                                        <td colspan="7" class="text-center">등록된 서류가 없습니다.</td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <button class="btn btn-sm btn-success" onclick="downloadAll(<?php echo $project['id'] ?>)">
                                <i class="fa fa-download"></i> 전체 다운로드
                            </button>
                        </div>
                    </div>
                </div>
            <?php
            endwhile;
        else:
            ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-hard-hat fa-5x text-muted mb-3"></i>
                    <h4 class="text-muted">
                        <?php if($status_filter !== ''): ?>
                            해당 상태의 공사가 없습니다.
                        <?php else: ?>
                            아직 등록된 공사가 없습니다.
                        <?php endif; ?>
                    </h4>
                    <p class="text-muted mb-4">새로운 서류 요청을 생성하여 공사를 시작하세요.</p>
                    <button class="btn btn-lg btn-primary" onclick="createNewRequest(<?php echo $supplier['id'] ?>)">
                        <i class="fa fa-plus"></i> 첫 서류요청 만들기
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
    function filterByStatus(status){
        location.href = './?page=suppliers/view_projects&id=<?php echo $_GET['id'] ?>&status=' + status;
    }

    function editSupplier(id){
        uni_modal("<i class='fa fa-edit'></i> 의뢰처 정보 수정", "suppliers/manage_supplier.php?id=" + id);
    }

    function createNewRequest(supplier_id){
        location.href = './?page=document_requests/create_request&supplier_id=' + supplier_id;
    }

    function downloadAll(request_id){
        start_loader();
        window.location.href = _base_url_ + "classes/Master.php?f=download_all&request_id=" + request_id;
        setTimeout(function() {
            end_loader();
        }, 3000);
    }

    function downloadFile(filepath){
        // document_requests/view_request.php의 downloadFile 함수와 동일하게 수정
        window.location.href = '<?php echo base_url ?>admin/upload_portal/download.php?id=' + filepath + '&internal_download=1';
    }
</script>
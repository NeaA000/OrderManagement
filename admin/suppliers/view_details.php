<?php
require_once('../../config.php');
if(isset($_GET['id']) && $_GET['id'] > 0){
    $qry = $conn->query("SELECT * from `supplier_list` where id = '{$_GET['id']}' ");
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k=stripslashes($v);
        }
    }
}
?>
<style>
    #uni_modal .modal-footer{
        display:none
    }
</style>
<div class="container fluid">
    <callout class="callout-primary">
        <dl class="row">
            <dt class="col-md-4">의뢰처명</dt>
            <dd class="col-md-8">: <?php echo $name ?></dd>
            <dt class="col-md-4">주소</dt>
            <dd class="col-md-8">: <span><?php echo $address ?></span></dd>
            <dt class="col-md-4">담당자</dt>
            <dd class="col-md-8">: <?php echo $contact_person ?></dd>
            <dt class="col-md-4">연락처</dt>
            <dd class="col-md-8">: <?php echo $contact ?></dd>
            <dt class="col-md-4">이메일</dt>
            <dd class="col-md-8">: <?php echo $email ?></dd>
            <dt class="col-md-4">상태</dt>
            <dd class="col-md-8">:&nbsp;
                <?php if($status == 1): ?>
                    <span class="badge badge-success">활성</span>
                <?php else: ?>
                    <span class="badge badge-secondary">비활성</span>
                <?php endif; ?>
            </dd>
        </dl>
    </callout>
    
    <!-- 공사 현황 추가 -->
    <h5 class="mt-3"><i class="fas fa-hard-hat"></i> 진행중인 공사 현황</h5>
    <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead>
                <tr class="bg-light">
                    <th>공사명</th>
                    <th>요청일</th>
                    <th>마감일</th>
                    <th>진행률</th>
                    <th>상태</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $projects = $conn->query("SELECT dr.*, 
                    (SELECT COUNT(*) FROM request_documents WHERE request_id = dr.id) as total_docs,
                    (SELECT COUNT(*) FROM request_documents WHERE request_id = dr.id AND status = 1) as submitted_docs
                    FROM document_requests dr 
                    WHERE dr.supplier_id = '{$id}' 
                    ORDER BY dr.date_created DESC");
                
                if($projects->num_rows > 0):
                    while($proj = $projects->fetch_assoc()):
                        $progress = $proj['total_docs'] > 0 ? round(($proj['submitted_docs'] / $proj['total_docs']) * 100) : 0;
                ?>
                <tr>
                    <td><?php echo $proj['project_name'] ?></td>
                    <td><?php echo date("Y-m-d", strtotime($proj['date_created'])) ?></td>
                    <td><?php echo date("Y-m-d", strtotime($proj['due_date'])) ?></td>
                    <td>
                        <div class="progress" style="height: 15px;">
                            <div class="progress-bar bg-<?php echo $progress == 100 ? 'success' : ($progress >= 50 ? 'warning' : 'danger') ?>" 
                                 style="width: <?php echo $progress ?>%">
                                <?php echo $progress ?>%
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if($proj['status'] == 0): ?>
                            <span class="badge badge-secondary">대기</span>
                        <?php elseif($proj['status'] == 1): ?>
                            <span class="badge badge-primary">진행중</span>
                        <?php else: ?>
                            <span class="badge badge-success">완료</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center text-muted">진행중인 공사가 없습니다.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="row px-2 justify-content-end">
        <div class="col-auto">
            <button class="btn btn-primary btn-flat btn-sm" type="button" onclick="viewProjects(<?php echo $id ?>)">
                <i class="fa fa-folder-open"></i> 상세 현황
            </button>
        </div>
        <div class="col-1">
            <button class="btn btn-dark btn-flat btn-sm" type="button" data-dismiss="modal">닫기</button>
        </div>
    </div>
</div>
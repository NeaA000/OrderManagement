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
            <dt class="col-md-4">공급업체명</dt>
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
    <div class="row px-2 justify-content-end">
        <div class="col-1">
            <button class="btn btn-dark btn-flat btn-sm" type="button" data-dismiss="modal">닫기</button>
        </div>
    </div>
</div>
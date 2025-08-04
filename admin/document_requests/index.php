<?php if($_settings->chk_flashdata('success')): ?>
    <script>
        alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
    </script>
<?php endif;?>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">서류 요청 목록</h3>
        <div class="card-tools">
            <a href="./?page=document_requests/manage_request" class="btn btn-flat btn-primary">
                <span class="fas fa-plus"></span> 새 요청 생성
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <table class="table table-bordered table-stripped" id="request-table">
                <colgroup>
                    <col width="5%">
                    <col width="15%">
                    <col width="20%">
                    <col width="25%">
                    <col width="10%">
                    <col width="10%">
                    <col width="15%">
                </colgroup>
                <thead>
                <tr>
                    <th>#</th>
                    <th>요청번호</th>
                    <th>의뢰처</th>
                    <th>프로젝트명</th>
                    <th>진행률</th>
                    <th>상태</th>
                    <th>작업</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $i = 1;
                // 진행률 계산 수정: status = 1 (숫자)로 비교
                $qry = $conn->query("SELECT dr.*, sl.name as supplier_name, 
                                        (SELECT COUNT(*) FROM request_documents WHERE request_id = dr.id) as total_docs,
                                        (SELECT COUNT(*) FROM request_documents WHERE request_id = dr.id AND status = 1) as completed_docs
                                        FROM document_requests dr 
                                        LEFT JOIN supplier_list sl ON dr.supplier_id = sl.id 
                                        ORDER BY dr.date_created DESC");

                while($row = $qry->fetch_assoc()):
                    $progress = $row['total_docs'] > 0 ? round(($row['completed_docs'] / $row['total_docs']) * 100) : 0;
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $i++; ?></td>
                        <td><?php echo $row['request_no'] ?></td>
                        <td><?php echo $row['supplier_name'] ?></td>
                        <td><?php echo $row['project_name'] ?></td>
                        <td>
                            <div class="progress">
                                <div class="progress-bar <?php echo $progress == 100 ? 'bg-success' : ($progress >= 50 ? 'bg-warning' : 'bg-danger') ?>"
                                     role="progressbar"
                                     style="width: <?php echo $progress ?>%">
                                    <?php echo $progress ?>%
                                </div>
                            </div>
                            <small><?php echo $row['completed_docs'] ?>/<?php echo $row['total_docs'] ?> 완료</small>
                        </td>
                        <td class="text-center">
                            <?php
                            $status_badge = '';
                            switch($row['status']) {
                                case 0:
                                    $status_badge = '<span class="badge badge-secondary">대기</span>';
                                    break;
                                case 1:
                                    $status_badge = '<span class="badge badge-primary">진행중</span>';
                                    break;
                                case 2:
                                    $status_badge = '<span class="badge badge-success">완료</span>';
                                    break;
                            }
                            echo $status_badge;
                            ?>
                        </td>
                        <td align="center">
                            <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                작업
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <div class="dropdown-menu" role="menu">
                                <a class="dropdown-item" href="./?page=document_requests/view_request&id=<?php echo $row['id'] ?>">
                                    <span class="fa fa-eye text-primary"></span> 보기
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="./?page=document_requests/manage_request&id=<?php echo $row['id'] ?>">
                                    <span class="fa fa-edit text-info"></span> 수정
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item send_email" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
                                    <span class="fa fa-envelope text-success"></span> 이메일 전송
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item copy_link" href="javascript:void(0)"
                                   data-link="<?php echo base_url ?>admin/upload_portal/?token=<?php echo $row['upload_token'] ?>">
                                    <span class="fa fa-link text-warning"></span> 링크 복사
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
                                    <span class="fa fa-trash text-danger"></span> 삭제
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        $('.delete_data').click(function(){
            _conf("정말로 이 요청을 삭제하시겠습니까?","delete_request",[$(this).attr('data-id')])
        })

        // 이메일 전송 수정
        $('.send_email').click(function(){
            var id = $(this).attr('data-id');
            _conf("이메일을 전송하시겠습니까?", "send_email", [id]);
        })

        $('.copy_link').click(function(){
            var link = $(this).attr('data-link');
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val(link).select();
            document.execCommand("copy");
            $temp.remove();
            alert_toast("링크가 복사되었습니다!",'success');
        })

        $('#request-table').dataTable({
            columnDefs: [
                { orderable: false, targets: [6] }
            ],
            order:[0,'asc']
        });

        $('.dataTable td,.dataTable th').addClass('py-1 px-2 align-middle')
    })

    function delete_request($id){
        start_loader();
        $.ajax({
            url:_base_url_+"classes/Master.php?f=delete_request",
            method:"POST",
            data:{id: $id},
            dataType:"json",
            error:err=>{
                console.log(err)
                alert_toast("An error occured.",'error');
                end_loader();
            },
            success:function(resp){
                if(typeof resp== 'object' && resp.status == 'success'){
                    location.reload();
                }else{
                    alert_toast("An error occured.",'error');
                    end_loader();
                }
            }
        })
    }

    // 이메일 전송 함수 추가
    function send_email(id){
        start_loader();
        $.ajax({
            url:_base_url_+"classes/Master.php?f=send_request_email",
            method:"POST",
            data:{id: id},
            dataType:"json",
            error:err=>{
                console.log(err)
                alert_toast("이메일 전송 중 오류가 발생했습니다.",'error');
                end_loader();
            },
            success:function(resp){
                if(typeof resp== 'object' && resp.status == 'success'){
                    alert_toast("이메일이 성공적으로 전송되었습니다.",'success');
                    setTimeout(function(){
                        location.reload();
                    }, 1500);
                }else{
                    alert_toast(resp.msg || "이메일 전송에 실패했습니다.",'error');
                    end_loader();
                }
            }
        })
    }
</script>
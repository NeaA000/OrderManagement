<?php if($_settings->chk_flashdata('success')): ?>
    <script>
        alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
    </script>
<?php endif;?>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">서류 요청 목록</h3>
        <div class="card-tools">
            <a href="./?page=document_requests/manage_request" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span> 새 요청 추가</a>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <table class="table table-bordered table-striped" id="request-list">
                <colgroup>
                    <col width="5%">
                    <col width="15%">
                    <col width="20%">
                    <col width="20%">
                    <col width="15%">
                    <col width="10%">
                    <col width="15%">
                </colgroup>
                <thead>
                <tr>
                    <th>#</th>
                    <th>요청번호</th>
                    <th>프로젝트명</th>
                    <th>의뢰처</th>
                    <th>제출기한</th>
                    <th>상태</th>
                    <th>작업</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $i = 1;
                $qry = $conn->query("SELECT r.*, s.name as supplier_name, 
						(SELECT COUNT(*) FROM request_documents WHERE request_id = r.id) as total_docs,
						(SELECT COUNT(*) FROM request_documents WHERE request_id = r.id AND status = 'completed') as completed_docs
						FROM `document_requests` r 
						LEFT JOIN `supplier_list` s ON r.supplier_id = s.id 
						ORDER BY r.date_created DESC");
                while($row = $qry->fetch_assoc()):
                    $progress = $row['total_docs'] > 0 ? round(($row['completed_docs'] / $row['total_docs']) * 100) : 0;
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $i++; ?></td>
                        <td><?php echo $row['request_no'] ?></td>
                        <td><?php echo $row['project_name'] ?></td>
                        <td><?php echo $row['supplier_name'] ?></td>
                        <td><?php echo date("Y-m-d", strtotime($row['due_date'])) ?></td>
                        <td class="text-center">
                            <?php
                            $status_badge = '';
                            switch($row['status']) {
                                case 0:
                                    $status_badge = '<span class="badge badge-secondary">대기중</span>';
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
                            <div class="progress mt-1" style="height: 10px;">
                                <div class="progress-bar" role="progressbar" style="width: <?php echo $progress ?>%"></div>
                            </div>
                            <small><?php echo $row['completed_docs'] ?>/<?php echo $row['total_docs'] ?> 서류</small>
                        </td>
                        <td align="center">
                            <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                작업
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <div class="dropdown-menu" role="menu">
                                <a class="dropdown-item" href="./?page=document_requests/view_request&id=<?php echo $row['id'] ?>">
                                    <span class="fa fa-eye text-dark"></span> 보기
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="./?page=document_requests/manage_request&id=<?php echo $row['id'] ?>">
                                    <span class="fa fa-edit text-primary"></span> 수정
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="./document_requests/upload.php?token=<?php echo $row['upload_token'] ?>" target="_blank">
                                    <span class="fa fa-upload text-info"></span> 업로드 링크
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item send_email" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
                                    <span class="fa fa-envelope text-success"></span> 이메일 발송
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
        $('#request-list').dataTable({
            columnDefs: [
                { orderable: false, targets: [6] }
            ],
            order: [[0, 'desc']],
            language: {
                "emptyTable": "데이터가 없습니다.",
                "lengthMenu": "_MENU_ 개씩 보기",
                "info": "_START_ - _END_ / _TOTAL_건",
                "infoEmpty": "데이터 없음",
                "infoFiltered": "( _MAX_건의 데이터에서 필터링됨 )",
                "search": "검색: ",
                "zeroRecords": "일치하는 데이터가 없습니다.",
                "loadingRecords": "로딩중...",
                "processing": "처리중...",
                "paginate": {
                    "first": "첫 페이지",
                    "last": "마지막 페이지",
                    "next": "다음",
                    "previous": "이전"
                }
            }
        });

        $('.delete_data').click(function(){
            _conf("이 서류 요청을 삭제하시겠습니까?", "delete_request", [$(this).attr('data-id')])
        })

        $('.send_email').click(function(){
            _conf("의뢰처에 서류 요청 이메일을 발송하시겠습니까?", "send_email", [$(this).attr('data-id')])
        })
    })

    function delete_request($id){
        start_loader();
        $.ajax({
            url: _base_url_+"classes/Master.php?f=delete_request",
            method: "POST",
            data: {id: $id},
            dataType: "json",
            error: err => {
                console.log(err)
                alert_toast("An error occurred.", 'error');
                end_loader();
            },
            success: function(resp){
                if(typeof resp == 'object' && resp.status == 'success'){
                    location.reload();
                } else {
                    alert_toast("An error occurred.", 'error');
                    end_loader();
                }
            }
        })
    }

    function send_email($id){
        start_loader();
        $.ajax({
            url: _base_url_+"classes/Master.php?f=send_request_email",
            method: "POST",
            data: {id: $id},
            dataType: "json",
            error: err => {
                console.log(err)
                alert_toast("An error occurred.", 'error');
                end_loader();
            },
            success: function(resp){
                if(typeof resp == 'object' && resp.status == 'success'){
                    alert_toast("이메일이 성공적으로 발송되었습니다.", 'success');
                    end_loader();
                } else {
                    alert_toast("An error occurred.", 'error');
                    end_loader();
                }
            }
        })
    }
</script>
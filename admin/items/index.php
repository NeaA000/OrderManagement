<?php if($_settings->chk_flashdata('success')): ?>
    <script>
        alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
    </script>
<?php endif;?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">품목 목록</h3>
        <div class="card-tools">
            <a href="javascript:void(0)" id="create_new" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span>  새로 만들기</a>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <div class="container-fluid">
                <table class="table table-hover table-striped">
                    <colgroup>
                        <col width="5%">
                        <col width="15%">
                        <col width="25%">
                        <col width="25%">
                        <col width="15%">
                        <col width="15%">
                    </colgroup>
                    <thead>
                    <tr class="bg-navy disabled">
                        <th>#</th>
                        <th>생성일</th>
                        <th>품목명</th>
                        <th>설명</th>
                        <th>상태</th>
                        <th>작업</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $i = 1;
                    $qry = $conn->query("SELECT * from `item_list` order by (`name`) asc ");
                    while($row = $qry->fetch_assoc()):
                        $row['description'] = html_entity_decode($row['description']);
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $i++; ?></td>
                            <td><?php echo date("Y-m-d H:i",strtotime($row['date_created'])) ?></td>
                            <td><?php echo $row['name'] ?></td>
                            <td class='truncate-3' title="<?php echo $row['description'] ?>"><?php echo $row['description'] ?></td>
                            <td class="text-center">
                                <?php if($row['status'] == 1): ?>
                                    <span class="badge badge-success">활성</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">비활성</span>
                                <?php endif; ?>
                            </td>
                            <td align="center">
                                <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon py-0" data-toggle="dropdown">
                                    작업
                                    <span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <div class="dropdown-menu" role="menu">
                                    <a class="dropdown-item view_data" href="javascript:void(0)" data-id = "<?php echo $row['id'] ?>"><span class="fa fa-info text-primary"></span> 보기</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item edit_data" href="javascript:void(0)" data-id = "<?php echo $row['id'] ?>"><span class="fa fa-edit text-primary"></span> 수정</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-trash text-danger"></span> 삭제</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function(){
        $('.delete_data').click(function(){
            _conf("정말로 이 품목을 영구적으로 삭제하시겠습니까?","delete_item",[$(this).attr('data-id')])
        })
        $('#create_new').click(function(){
            uni_modal("<i class='fa fa-plus'></i> 새 품목 생성","items/manage_item.php")
        })
        $('.view_data').click(function(){
            uni_modal("<i class='fa fa-info-circle'></i> 품목 세부정보","items/view_details.php?id="+$(this).attr('data-id'),"")
        })
        $('.edit_data').click(function(){
            uni_modal("<i class='fa fa-edit'></i> 품목 세부정보 수정","items/manage_item.php?id="+$(this).attr('data-id'))
        })
        $('.table th,.table td').addClass('px-1 py-0 align-middle')
        $('.table').dataTable();
    })
    function delete_item($id){
        start_loader();
        $.ajax({
            url:_base_url_+"classes/Master.php?f=delete_item",
            method:"POST",
            data:{id: $id},
            dataType:"json",
            error:err=>{
                console.log(err)
                alert_toast("오류가 발생했습니다.",'error');
                end_loader();
            },
            success:function(resp){
                if(typeof resp== 'object' && resp.status == 'success'){
                    location.reload();
                }else{
                    alert_toast("오류가 발생했습니다.",'error');
                    end_loader();
                }
            }
        })
    }
</script>
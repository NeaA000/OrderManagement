<?php if($_settings->chk_flashdata('success')): ?>
    <script>
        alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
    </script>
<?php endif;?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">의뢰처 목록</h3>
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
                        <col width="12%">
                        <col width="18%">
                        <col width="20%">
                        <col width="25%">
                        <col width="10%">
                        <col width="10%">
                    </colgroup>
                    <thead>
                    <tr class="bg-navy disabled">
                        <th>#</th>
                        <th>생성일</th>
                        <th>의뢰처명</th>
                        <th>담당자</th>
                        <th>주소</th>
                        <th>진행중인 공사</th>
                        <th>서류 현황</th>
                        <th>작업</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $i = 1;
                    $qry = $conn->query("SELECT * from `supplier_list` order by (`name`) asc ");
                    while($row = $qry->fetch_assoc()):
                        // 해당 의뢰처의 공사 현황 조회
                        $projects = $conn->query("SELECT 
                            dr.*,
                            (SELECT COUNT(*) FROM request_documents WHERE request_id = dr.id) as total_docs,
                            (SELECT COUNT(*) FROM request_documents WHERE request_id = dr.id AND status = 1) as submitted_docs
                            FROM document_requests dr 
                            WHERE dr.supplier_id = '{$row['id']}' 
                            ORDER BY dr.date_created DESC");
                        
                        $project_count = $projects->num_rows;
                        $latest_project = $projects->fetch_assoc();
                        ?>
                        <tr class="supplier-row" data-id="<?php echo $row['id'] ?>" style="cursor: pointer;">
                            <td class="text-center"><?php echo $i++; ?></td>
                            <td><?php echo date("Y-m-d H:i",strtotime($row['date_created'])) ?></td>
                            <td><?php echo $row['name'] ?></td>
                            <td>
                                <p class="m-0">
                                    <?php echo $row['contact_person'] ?><br>
                                    <?php echo $row['contact'] ?>
                                </p>
                            </td>
                            <td class='truncate-3' title="<?php echo $row['address'] ?>"><?php echo $row['address'] ?></td>
                            <td>
                                <?php if($project_count > 0): ?>
                                    <span class="badge badge-primary"><?php echo $project_count ?>개 프로젝트</span>
                                    <?php if($latest_project): ?>
                                        <br><small class="text-muted">최근: <?php echo $latest_project['project_name'] ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">진행중인 공사 없음</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($latest_project && $latest_project['total_docs'] > 0): ?>
                                    <div class="progress" style="height: 20px;">
                                        <?php 
                                        $progress = round(($latest_project['submitted_docs'] / $latest_project['total_docs']) * 100);
                                        ?>
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress ?>%">
                                            <?php echo $latest_project['submitted_docs'] ?>/<?php echo $latest_project['total_docs'] ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td align="center">
                                <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon py-0" data-toggle="dropdown" data-boundary="viewport">
                                    작업
                                    <span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right" role="menu">
                                    <a class="dropdown-item" href="javascript:void(0)" onclick="viewProjects(<?php echo $row['id'] ?>)"><span class="fa fa-folder-open text-info"></span> 공사 목록</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="javascript:void(0)" onclick="viewSupplierDetails(<?php echo $row['id'] ?>)"><span class="fa fa-eye text-primary"></span> 상세보기</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="javascript:void(0)" onclick="editSupplier(<?php echo $row['id'] ?>)"><span class="fa fa-edit text-success"></span> 정보수정</a>
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
</div>
<script>
    $(document).ready(function(){
        $('.delete_data').click(function(){
            _conf("정말로 이 의뢰처를 영구적으로 삭제하시겠습니까?","delete_supplier",[$(this).attr('data-id')])
        })
        $('#create_new').click(function(){
            uni_modal("<i class='fa fa-plus'></i> 새 의뢰처 등록","suppliers/manage_supplier.php")
        })
        $('.view_data').click(function(){
            uni_modal("<i class='fa fa-info-circle'></i> 의뢰처 세부정보","suppliers/view_details.php?id="+$(this).attr('data-id'),"")
        })
        $('.edit_data').click(function(){
            uni_modal("<i class='fa fa-edit'></i> 의뢰처 정보 수정","suppliers/manage_supplier.php?id="+$(this).attr('data-id'))
        })
        $('.table th,.table td').addClass('px-1 py-0 align-middle')
        $('.table').dataTable();
        
        // 행 클릭 시 상세 페이지로 이동
        $('.supplier-row').click(function(){
            var id = $(this).data('id');
            window.location.href = '?page=suppliers/view_projects&id=' + id;
        });
    })
    
    function viewProjects(supplier_id){
        window.location.href = '?page=suppliers/view_projects&id=' + supplier_id;
    }
    
    function viewSupplierDetails(supplier_id){
        window.location.href = '?page=suppliers/view_projects&id=' + supplier_id;
    }
    
    function editSupplier(supplier_id){
        uni_modal("<i class='fa fa-edit'></i> 의뢰처 정보 수정","suppliers/manage_supplier.php?id="+supplier_id)
    }
    
    function delete_supplier($id){
        start_loader();
        $.ajax({
            url:_base_url_+"classes/Master.php?f=delete_supplier",
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
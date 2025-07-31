<?php if($_settings->chk_flashdata('success')): ?>
    <script>
        alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
    </script>
<?php endif;?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">구매 주문 목록</h3>
        <div class="card-tools">
            <a href="?page=purchase_orders/manage_po" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span>  새로 만들기</a>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <div class="container-fluid">
                <table class="table table-hover table-striped">
                    <colgroup>
                        <col width="5%">
                        <col width="15%">
                        <col width="15%">
                        <col width="20%">
                        <col width="10%">
                        <col width="15%">
                        <col width="10%">
                        <col width="10%">
                    </colgroup>
                    <thead>
                    <tr class="bg-navy disabled">
                        <th>#</th>
                        <th>생성일</th>
                        <th>주문번호</th>
                        <th>공급업체</th>
                        <th>품목</th>
                        <th>총 금액</th>
                        <th>상태</th>
                        <th>작업</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $i = 1;
                    $qry = $conn->query("SELECT po.*, s.name as sname FROM `po_list` po inner join `supplier_list` s on po.supplier_id = s.id order by unix_timestamp(po.date_updated) ");
                    while($row = $qry->fetch_assoc()):
                        $row['item_count'] = $conn->query("SELECT * FROM order_items where po_id = '{$row['id']}'")->num_rows;
                        $row['total_amount'] = $conn->query("SELECT sum(quantity * unit_price) as total FROM order_items where po_id = '{$row['id']}'")->fetch_array()['total'];
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $i++; ?></td>
                            <td class=""><?php echo date("M d,Y H:i",strtotime($row['date_created'])) ; ?></td>
                            <td class=""><?php echo $row['po_no'] ?></td>
                            <td class=""><?php echo $row['sname'] ?></td>
                            <td class="text-right"><?php echo number_format($row['item_count']) ?></td>
                            <td class="text-right"><?php echo number_format($row['total_amount']) ?></td>
                            <td>
                                <?php
                                switch ($row['status']) {
                                    case '1':
                                        echo '<span class="badge badge-success">승인됨</span>';
                                        break;
                                    case '2':
                                        echo '<span class="badge badge-danger">거부됨</span>';
                                        break;
                                    default:
                                        echo '<span class="badge badge-secondary">대기중</span>';
                                        break;
                                }
                                ?>
                            </td>
                            <td align="center">
                                <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                    작업
                                    <span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <div class="dropdown-menu" role="menu">
                                    <a class="dropdown-item" href="?page=purchase_orders/view_po&id=<?php echo $row['id'] ?>"><span class="fa fa-eye text-primary"></span> 보기</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="?page=purchase_orders/manage_po&id=<?php echo $row['id'] ?>"><span class="fa fa-edit text-primary"></span> 수정</a>
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
            _conf("정말로 이 항목을 영구적으로 삭제하시겠습니까?","delete_rent",[$(this).attr('data-id')])
        })
        $('.view_details').click(function(){
            uni_modal("예약 세부정보","purchase_orders/view_details.php?id="+$(this).attr('data-id'),'mid-large')
        })
        $('.renew_data').click(function(){
            _conf("정말로 이 데이터를 갱신하시겠습니까?","renew_rent",[$(this).attr('data-id')]);
        })
        $('.table th,.table td').addClass('px-1 py-0 align-middle')
        $('.table').dataTable();
    })
    function delete_rent($id){
        start_loader();
        $.ajax({
            url:_base_url_+"classes/Master.php?f=delete_rent",
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
    function renew_rent($id){
        start_loader();
        $.ajax({
            url:_base_url_+"classes/Master.php?f=renew_rent",
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
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
            <table class="table table-hover table-striped" id="request-table">
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
                                        (SELECT COUNT(*) FROM request_documents WHERE request_id = dr.id AND status = 1) as completed_docs,
                                        dr.email_sent_at
                                        FROM document_requests dr 
                                        LEFT JOIN supplier_list sl ON dr.supplier_id = sl.id 
                                        ORDER BY dr.date_created DESC");

                while($row = $qry->fetch_assoc()):
                    $progress = $row['total_docs'] > 0 ? round(($row['completed_docs'] / $row['total_docs']) * 100) : 0;
                    ?>
                    <tr class="clickable-row" data-id="<?php echo $row['id'] ?>" style="cursor: pointer;">
                        <td class="text-center"><?php echo $i++; ?></td>
                        <td><?php echo $row['request_no'] ?></td>
                        <td><?php echo $row['supplier_name'] ?></td>
                        <td><?php echo $row['project_name'] ?></td>
                        <td>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar <?php echo $progress == 100 ? 'bg-success' : ($progress >= 50 ? 'bg-warning' : 'bg-danger') ?>"
                                     role="progressbar"
                                     style="width: <?php echo $progress ?>%">
                                    <?php echo $progress ?>%
                                </div>
                            </div>
                            <small class="text-muted"><?php echo $row['completed_docs'] ?>/<?php echo $row['total_docs'] ?> 완료</small>
                        </td>
                        <td class="text-center">
                            <?php
                            $status_badge = '';
                            switch($row['status']) {
                                case 0:
                                    $status_badge = '<span class="badge badge-secondary badge-pill">대기</span>';
                                    break;
                                case 1:
                                    $status_badge = '<span class="badge badge-primary badge-pill">진행중</span>';
                                    break;
                                case 2:
                                    $status_badge = '<span class="badge badge-success badge-pill">완료</span>';
                                    break;
                            }
                            echo $status_badge;
                            ?>
                        </td>
                        <td align="center" class="action-cell">
                            <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                작업
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <div class="dropdown-menu" role="menu">
                                <a class="dropdown-item view-request" href="./?page=document_requests/view_request&id=<?php echo $row['id'] ?>">
                                    <span class="fa fa-eye text-primary"></span> 보기
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="./?page=document_requests/manage_request&id=<?php echo $row['id'] ?>">
                                    <span class="fa fa-edit text-info"></span> 수정
                                </a>
                                <div class="dropdown-divider"></div>
                                <?php if(empty($row['email_sent_at'])): ?>
                                    <a class="dropdown-item send_email" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
                                        <span class="fa fa-envelope text-success"></span> 이메일 전송
                                    </a>
                                <?php else: ?>
                                    <a class="dropdown-item send_email" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
                                        <span class="fa fa-envelope text-info"></span> 이메일 재전송
                                        <small class="text-muted d-block" style="margin-left: 25px; font-size: 11px;">
                                            마지막: <?php echo date('m/d H:i', strtotime($row['email_sent_at'])) ?>
                                        </small>
                                    </a>
                                <?php endif; ?>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item copy_link" href="javascript:void(0)"
                                   data-link="<?php echo base_url ?>admin/upload_portal/?token=<?php echo $row['upload_token'] ?>">
                                    <span class="fa fa-link text-warning"></span> 링크 복사
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item print_document" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
                                    <span class="fa fa-print text-success"></span> 문서 인쇄
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

<style>
    /* 드롭다운 메뉴 z-index 수정 - 최상단에 표시 */
    .dropdown-menu {
        z-index: 1050 !important;
    }

    /* DataTable 컨테이너 overflow 해결 */
    .dataTables_wrapper {
        overflow: visible !important;
    }

    /* 행 hover 효과 - 작업 셀 제외 */
    .clickable-row:hover td:not(.action-cell) {
        background-color: #f5f5f5;
        transition: background-color 0.2s ease;
    }

    /* 행 hover 시 shadow 효과 */
    .clickable-row:hover {
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: box-shadow 0.2s ease;
    }

    /* 드롭다운이 열려있을 때 hover 효과 비활성화 */
    .clickable-row.dropdown-active:hover td {
        background-color: transparent;
    }

    .clickable-row.dropdown-active:hover {
        box-shadow: none;
    }

    /* 상태 뱃지 스타일 개선 */
    .badge-pill {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }

    /* 진행률 바 스타일 */
    .progress {
        background-color: #e9ecef;
    }

    /* 액션 버튼이 있는 셀은 클릭 방지 */
    .action-cell {
        position: static !important;
    }

    /* 로딩 오버레이 */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255,255,255,0.8);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }

    .loading-overlay.show {
        display: flex;
    }
</style>

<!-- 로딩 오버레이 -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="text-center">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Loading...</span>
        </div>
        <p class="mt-2">처리중...</p>
    </div>
</div>

<script>
    $(document).ready(function(){
        // 드롭다운 열림/닫힘 이벤트 처리
        $(document).on('show.bs.dropdown', '.clickable-row .dropdown', function() {
            $(this).closest('.clickable-row').addClass('dropdown-active');
        });

        $(document).on('hide.bs.dropdown', '.clickable-row .dropdown', function() {
            $(this).closest('.clickable-row').removeClass('dropdown-active');
        });

        // 드롭다운 버튼 클릭 시 이벤트 전파 중단
        $(document).on('click', '.dropdown-toggle', function(e) {
            e.stopPropagation();
        });

        $(document).on('click', '.dropdown-menu', function(e) {
            e.stopPropagation();
        });

        // 행 클릭 이벤트
        $('.clickable-row').click(function(e) {
            // 드롭다운 버튼이나 메뉴를 클릭한 경우는 제외
            if($(e.target).closest('.dropdown-toggle, .dropdown-menu').length) {
                return;
            }

            var id = $(this).data('id');
            window.location.href = './?page=document_requests/view_request&id=' + id;
        });

        $('.delete_data').click(function(e){
            e.preventDefault();
            e.stopPropagation();
            _conf("정말로 이 요청을 삭제하시겠습니까?","delete_request",[$(this).attr('data-id')])
        })

        // 이메일 전송
        $('.send_email').click(function(e){
            e.preventDefault();
            e.stopPropagation();
            var id = $(this).attr('data-id');
            _conf("이메일을 전송하시겠습니까?", "send_email", [id]);
        })

        // 링크 복사
        $('.copy_link').click(function(e){
            e.preventDefault();
            e.stopPropagation();
            var link = $(this).attr('data-link');
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val(link).select();
            document.execCommand("copy");
            $temp.remove();
            alert_toast("링크가 복사되었습니다!",'success');
        })

        // 문서 인쇄
        $('.print_document').click(function(e){
            e.preventDefault();
            e.stopPropagation();
            var id = $(this).attr('data-id');

            // 새 창에서 인쇄 페이지 열기
            var printWindow = window.open(_base_url_+'admin/?page=document_requests/print_request&id=' + id,
                'printWindow',
                'width=1200,height=800,scrollbars=yes,resizable=yes'
            );

            // 창이 차단되었는지 확인
            if (!printWindow || printWindow.closed || typeof printWindow.closed == 'undefined') {
                alert_toast("팝업이 차단되었습니다. 팝업 차단을 해제해주세요.", 'warning');
            }
        })

        // DataTable 설정
        var table = $('#request-table').DataTable({
            columnDefs: [
                { orderable: false, targets: [6] }
            ],
            order: [[0, 'asc']],
            language: {
                "lengthMenu": "페이지당 _MENU_ 개씩 보기",
                "zeroRecords": "검색 결과가 없습니다",
                "info": "전체 _TOTAL_개 중 _START_ - _END_ 표시",
                "infoEmpty": "데이터가 없습니다",
                "infoFiltered": "(전체 _MAX_개 중 검색)",
                "search": "검색:",
                "paginate": {
                    "first": "처음",
                    "last": "마지막",
                    "next": "다음",
                    "previous": "이전"
                }
            }
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

    // 이메일 전송 함수
    function send_email(id){
        showLoading();
        $.ajax({
            url:_base_url_+"classes/Master.php?f=send_request_email",
            method:"POST",
            data:{id: id},
            dataType:"json",
            error:err=>{
                console.log(err)
                alert_toast("이메일 전송 중 오류가 발생했습니다.",'error');
                hideLoading();
            },
            success:function(resp){
                if(typeof resp== 'object' && resp.status == 'success'){
                    alert_toast("이메일이 성공적으로 전송되었습니다.",'success');
                    setTimeout(function(){
                        location.reload();
                    }, 1500);
                }else{
                    alert_toast(resp.msg || "이메일 전송에 실패했습니다.",'error');
                    hideLoading();
                }
            }
        })
    }

    // 로딩 표시/숨기기
    function showLoading() {
        $('#loadingOverlay').addClass('show');
    }

    function hideLoading() {
        $('#loadingOverlay').removeClass('show');
    }
</script>
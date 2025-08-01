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
                    <col width="10%">
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
                $qry = $conn->query("SELECT dr.*, sl.name as supplier_name, 
                                        (SELECT COUNT(*) FROM request_documents WHERE request_id = dr.id) as total_docs,
                                        (SELECT COUNT(*) FROM request_documents WHERE request_id = dr.id AND status = 'completed') as completed_docs
                                        FROM document_requests dr 
                                        LEFT JOIN supplier_list sl ON dr.supplier_id = sl.id 
                                        ORDER BY dr.date_created DESC");

                while($row = $qry->fetch_assoc()):
                    $progress = $row['total_docs'] > 0 ? round(($row['completed_docs'] / $row['total_docs']) * 100) : 0;

                    // 상태에 따른 색상
                    $status_class = '';
                    $status_text = '';
                    switch($row['status']) {
                        case 0:
                            $status_class = 'badge-secondary';
                            $status_text = '대기';
                            break;
                        case 1:
                            $status_class = 'badge-primary';
                            $status_text = '진행중';
                            break;
                        case 2:
                            $status_class = 'badge-success';
                            $status_text = '완료';
                            break;
                    }
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $i++; ?></td>
                        <td><?php echo $row['request_no'] ?></td>
                        <td><?php echo $row['supplier_name'] ?></td>
                        <td><?php echo $row['project_name'] ?></td>
                        <td>
                            <div class="progress">
                                <div class="progress-bar <?php echo $progress == 100 ? 'bg-success' : 'bg-primary' ?>"
                                     style="width: <?php echo $progress ?>%">
                                    <?php echo $progress ?>%
                                </div>
                            </div>
                            <small><?php echo $row['completed_docs'] ?>/<?php echo $row['total_docs'] ?>개</small>
                        </td>
                        <td class="text-center">
                            <span class="badge <?php echo $status_class ?>"><?php echo $status_text ?></span>
                        </td>
                        <td align="center">
                            <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
                                작업
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <div class="dropdown-menu" role="menu">
                                <a class="dropdown-item" href="./?page=document_requests/view_request&id=<?php echo $row['id'] ?>">
                                    <span class="fa fa-eye text-primary"></span> 상태 보기
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="./?page=document_requests/manage_request&id=<?php echo $row['id'] ?>">
                                    <span class="fa fa-edit text-success"></span> 수정하기
                                </a>
                                <div class="dropdown-divider"></div>
                                <?php if(empty($row['email_sent_at'])): ?>
                                    <a class="dropdown-item send-email" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
                                        <span class="fa fa-paper-plane text-info"></span> 이메일 발송
                                    </a>
                                <?php else: ?>
                                    <a class="dropdown-item send-email" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>">
                                        <span class="fa fa-redo text-warning"></span> 리마인더 발송
                                    </a>
                                <?php endif; ?>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item copy-link" href="javascript:void(0)"
                                   data-link="<?php echo base_url ?>upload_portal/?token=<?php echo $row['upload_token'] ?>">
                                    <span class="fa fa-copy text-secondary"></span> 업로드 링크 복사
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
        // DataTable 초기화 (한국어 파일 없이)
        $('#request-table').dataTable({
            order: [[0, 'desc']],
            language: {
                "decimal": "",
                "emptyTable": "데이터가 없습니다.",
                "info": "_START_ - _END_ / _TOTAL_건",
                "infoEmpty": "0건",
                "infoFiltered": "(전체 _MAX_ 건 중 검색결과)",
                "infoPostFix": "",
                "thousands": ",",
                "lengthMenu": "_MENU_ 건씩 보기",
                "loadingRecords": "로딩중...",
                "processing": "처리중...",
                "search": "검색:",
                "zeroRecords": "검색된 데이터가 없습니다.",
                "paginate": {
                    "first": "첫 페이지",
                    "last": "마지막 페이지",
                    "next": "다음",
                    "previous": "이전"
                },
                "aria": {
                    "sortAscending": ": 오름차순 정렬",
                    "sortDescending": ": 내림차순 정렬"
                }
            }
        });

        // 이메일 발송
        $('.send-email').click(function(){
            var id = $(this).data('id');
            var button = $(this);

            if(confirm('이메일을 발송하시겠습니까?')) {
                // 버튼 비활성화
                button.prop('disabled', true);
                start_loader();

                console.log('Sending email for request ID:', id);
                console.log('URL:', _base_url_ + 'admin/document_requests/send_email.php');

                $.ajax({
                    url: _base_url_ + 'admin/document_requests/send_email.php',
                    method: 'POST',
                    data: {request_id: id},
                    dataType: 'json',
                    success: function(resp) {
                        console.log('Email send response:', resp);

                        if(resp.status == 'success') {
                            alert_toast('이메일이 성공적으로 발송되었습니다!', 'success');

                            // Mailtrap에서 확인하라는 메시지 추가
                            setTimeout(function() {
                                if(confirm('이메일이 발송되었습니다. Mailtrap에서 확인하시겠습니까?')) {
                                    window.open('https://mailtrap.io', '_blank');
                                }
                                location.reload();
                            }, 2000);
                        } else {
                            alert_toast('이메일 발송 실패: ' + resp.msg, 'error');
                        }
                        end_loader();
                        button.prop('disabled', false);
                    },
                    error: function(xhr, status, error) {
                        console.error('Email send error:', {
                            status: xhr.status,
                            statusText: xhr.statusText,
                            responseText: xhr.responseText,
                            error: error
                        });

                        // PHP 에러가 있는지 확인
                        if(xhr.responseText && (xhr.responseText.includes('Fatal error') || xhr.responseText.includes('Warning'))) {
                            console.error('PHP Error detected in response');
                            alert('PHP 에러가 발생했습니다. 콘솔을 확인하세요.');
                        } else {
                            alert_toast('이메일 발송 중 오류가 발생했습니다.', 'error');
                        }
                        end_loader();
                        button.prop('disabled', false);
                    }
                });
            }
        });

        // 링크 복사
        $('.copy-link').click(function(){
            var link = $(this).data('link');

            // 임시 textarea 생성
            var $temp = $("<textarea>");
            $("body").append($temp);
            $temp.val(link).select();
            document.execCommand("copy");
            $temp.remove();

            alert_toast('업로드 링크가 클립보드에 복사되었습니다!', 'success');
        });

        // 삭제
        $('.delete_data').click(function(){
            var id = $(this).data('id');

            if(confirm('정말로 이 서류 요청을 삭제하시겠습니까?')) {
                start_loader();
                $.ajax({
                    url: _base_url_ + "classes/Master.php?f=delete_request",
                    method: "POST",
                    data: {id: id},
                    dataType: "json",
                    error: err => {
                        console.log(err);
                        alert_toast("An error occured.", 'error');
                        end_loader();
                    },
                    success: function(resp){
                        if(typeof resp == 'object' && resp.status == 'success'){
                            location.reload();
                        } else {
                            alert_toast("An error occured.", 'error');
                            end_loader();
                        }
                    }
                });
            }
        });
    })
</script>
<h1 class="text-dark"><?php echo $_settings->info('name') ?>에 오신 것을 환영합니다</h1>
<hr class="border-dark">
<div class="row">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-navy elevation-1"><i class="fas fa-truck-loading"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">전체 의뢰처</span>
                <span class="info-box-number">
                  <?php
                  $supplier = $conn->query("SELECT * FROM supplier_list")->num_rows;
                  echo number_format($supplier);
                  ?>
                  <?php ?>
                </span>
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
    <!-- /.col -->
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box mb-3">
            <span class="info-box-icon bg-success elevation-1"><i class="fas fa-boxes"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">전체 품목</span>
                <span class="info-box-number">
                  <?php
                  $item = $conn->query("SELECT * FROM item_list where `status` =0 ")->num_rows;
                  echo number_format($item);
                  ?>
                </span>
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
    <!-- /.col -->
    <!-- /.col -->
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box mb-3">
            <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-file-invoice"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">진행중인 서류요청</span>
                <span class="info-box-number">
                  <?php
                  $po_appoved = $conn->query("SELECT * FROM document_requests where `status` = 1")->num_rows;
                  echo number_format($po_appoved);
                  ?>
                </span>
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
    <!-- /.col -->
    <!-- /.col -->
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box mb-3">
            <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-file-invoice"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">대기중인 서류요청</span>
                <span class="info-box-number">
                  <?php
                  $po = $conn->query("SELECT * FROM document_requests where `status` = 0")->num_rows;
                  echo number_format($po);
                  ?>
                </span>
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
    <!-- /.col -->
</div>

<!-- 최근 업로드 알림 추가 -->
<div class="row mt-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-bell"></i> 최근 업로드 알림
                </h3>
            </div>
            <div class="card-body">
                <div id="recent-uploads-list">
                    <p class="text-center text-muted">최근 업로드 내역을 불러오는 중...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">

</div>

<script>
    // 최근 업로드 표시
    $(document).ready(function() {
        loadRecentUploads();

        // 30초마다 자동 갱신
        setInterval(loadRecentUploads, 30000);
    });

    function loadRecentUploads() {
        $.ajax({
            url: 'ajax/get_notifications.php',
            type: 'GET',
            data: { action: 'get', limit: 5 },
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success' && response.notifications.length > 0) {
                    let html = '<div class="list-group">';
                    response.notifications.forEach(function(notif) {
                        html += `
                        <a href="./?page=document_requests/view_request&id=${notif.request_id}" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">${notif.supplier_name || '알 수 없는 업체'}</h6>
                                <small>${notif.time_text}</small>
                            </div>
                            <p class="mb-1">${notif.document_name} 업로드</p>
                            <small class="text-muted">${notif.file_name || ''}</small>
                        </a>
                    `;
                    });
                    html += '</div>';
                    $('#recent-uploads-list').html(html);
                } else {
                    $('#recent-uploads-list').html('<p class="text-center text-muted">최근 업로드 내역이 없습니다.</p>');
                }
            },
            error: function() {
                $('#recent-uploads-list').html('<p class="text-center text-danger">데이터를 불러올 수 없습니다.</p>');
            }
        });
    }
</script>
<h1 class="text-dark"><?php echo $_settings->info('name') ?>에 오신 것을 환영합니다</h1>
<hr class="border-dark">
<div class="row">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box">
            <span class="info-box-icon bg-navy elevation-1"><i class="fas fa-truck-loading"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">전체 공급업체</span>
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
                <span class="info-box-text">승인된 구매주문</span>
                <span class="info-box-number">
                  <?php
                  $po_appoved = $conn->query("SELECT * FROM po_list where `status` =1 ")->num_rows;
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
                <span class="info-box-text">거부된 구매주문</span>
                <span class="info-box-number">
                  <?php
                  $po = $conn->query("SELECT * FROM po_list where `status` =2 ")->num_rows;
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
<div class="container">

</div>
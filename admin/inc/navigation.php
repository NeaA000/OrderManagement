<?php
$user_avatar = validate_image($_settings->userdata('avatar'));
$type = $_settings->userdata('type');
?>
<aside class="main-sidebar sidebar-light-primary elevation-4 sidebar-no-expand">
    <!-- Brand Logo -->
    <a href="<?php echo base_url ?>admin" class="brand-link bg-primary text-sm">
        <img src="<?php echo validate_image($_settings->info('logo'))?>" alt="Store Logo" class="brand-image img-circle elevation-3" style="opacity: .8;width: 2.5rem;height: 2.5rem;max-height: unset">
        <span class="brand-text font-weight-light"><?php echo $_settings->info('short_name') ?></span>
    </a>
    <!-- Sidebar -->
    <div class="sidebar os-host os-theme-light os-host-overflow os-host-overflow-y os-host-resize-disabled os-host-transition os-host-scrollbar-horizontal-hidden">
        <div class="os-resize-observer-host observed">
            <div class="os-resize-observer" style="left: 0px; right: auto;"></div>
        </div>
        <div class="os-size-auto-observer observed" style="height: calc(100% + 1px); float: left;">
            <div class="os-resize-observer"></div>
        </div>
        <div class="os-content">
            <!-- Sidebar user panel (optional) -->
            <div class="clearfix"></div>
            <!-- Sidebar Menu -->
            <nav class="mt-4">
                <ul class="nav nav-pills nav-sidebar flex-column text-sm nav-compact nav-flat nav-child-indent nav-collapse-hide-child" data-widget="treeview" role="menu" data-accordion="false">

                    <!-- 대시보드 -->
                    <li class="nav-item dropdown">
                        <a href="<?php echo base_url ?>admin" class="nav-link nav-home">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>대시보드</p>
                        </a>
                    </li>

                    <!-- 기존 메뉴들 (모든 사용자 공통) -->
                    <li class="nav-item dropdown">
                        <a href="<?php echo base_url ?>admin/?page=suppliers" class="nav-link nav-suppliers">
                            <i class="nav-icon fas fa-building"></i>
                            <p>의뢰처 목록</p>
                        </a>
                    </li>

                    <!-- 서류 카테고리 관리 -->
                    <li class="nav-item dropdown">
                        <a href="<?php echo base_url ?>admin/?page=document_categories" class="nav-link nav-document_categories">
                            <i class="nav-icon fas fa-folder"></i>
                            <p>서류 카테고리</p>
                        </a>
                    </li>

                    <!-- 서류 요청 관리 -->
                    <li class="nav-item dropdown">
                        <a href="<?php echo base_url ?>admin/?page=document_requests" class="nav-link nav-document_requests">
                            <i class="nav-icon fas fa-file-alt"></i>
                            <p>서류 요청 관리</p>
                        </a>
                    </li>

                    <!-- 진행 현황 -->
                    <li class="nav-item dropdown">
                        <a href="<?php echo base_url ?>admin/?page=document_status" class="nav-link nav-document_status">
                            <i class="nav-icon fas fa-chart-bar"></i>
                            <p>진행 현황</p>
                        </a>
                    </li>

                    <!-- 이메일 설정 - 모든 사용자가 볼 수 있도록 이동 -->
                    <li class="nav-item dropdown">
                        <a href="<?php echo base_url ?>admin/?page=email_settings" class="nav-link nav-email_settings">
                            <i class="nav-icon fas fa-envelope"></i>
                            <p>이메일 설정</p>
                        </a>
                    </li>

                    <!-- 관리자 전용 메뉴 -->
                    <?php if($_settings->userdata('type') == 1): ?>
                        <li class="nav-header">관리</li>
                        <li class="nav-item dropdown">
                            <a href="<?php echo base_url ?>admin/?page=user/list" class="nav-link nav-user_list">
                                <i class="nav-icon fas fa-users"></i>
                                <p>사용자 목록</p>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a href="<?php echo base_url ?>admin/?page=system_info" class="nav-link nav-system_info">
                                <i class="nav-icon fas fa-cogs"></i>
                                <p>시스템 설정</p>
                            </a>
                        </li>
                    <?php endif; ?>

                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
    </div>
</aside>

<script>
    $(document).ready(function(){
        var page = '<?php echo isset($_GET['page']) ? $_GET['page'] : 'home' ?>';
        var s = '<?php echo isset($_GET['s']) ? $_GET['s'] : '' ?>';
        page = page.split('/');
        page = page[0];
        if(s!='')
            page = page+'_'+s;

        if($('.nav-link.nav-'+page).length > 0){
            $('.nav-link.nav-'+page).addClass('active')
            if($('.nav-link.nav-'+page).hasClass('tree-item') == true){
                $('.nav-link.nav-'+page).closest('.nav-treeview').siblings('a').addClass('active')
                $('.nav-link.nav-'+page).closest('.nav-treeview').parent().addClass('menu-open')
            }
            if($('.nav-link.nav-'+page).hasClass('nav-is-tree') == true){
                $('.nav-link.nav-'+page).parent().addClass('menu-open')
            }
        }
    })
</script>
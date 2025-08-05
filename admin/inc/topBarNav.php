<style>
    .user-img{
        position: absolute;
        height: 27px;
        width: 27px;
        object-fit: cover;
        left: -7%;
        top: -12%;
    }
    .btn-rounded{
        border-radius: 50px;
    }

    /* 알림음 토글 버튼 스타일 */
    .sound-toggle-btn {
        background: none;
        border: none;
        color: #6c757d;
        cursor: pointer;
        font-size: 14px;
        padding: 2px 8px;
        transition: all 0.2s;
        margin-right: 5px;
    }

    .sound-toggle-btn:hover {
        color: #495057;
        background: rgba(0,0,0,0.05);
        border-radius: 3px;
    }

    .sound-toggle-btn.muted {
        color: #dc3545;
    }

    .sound-toggle-btn.muted:hover {
        background: rgba(220,53,69,0.1);
    }

    /* 알림 토글 버튼 */
    .notification-toggle-btn.muted {
        color: #6c757d;
        opacity: 0.7;
    }
</style>
<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-light border border-light shadow  border-top-0  border-left-0 border-right-0 navbar-light text-sm">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="<?php echo base_url ?>" class="nav-link"><?php echo (!isMobileDevice()) ? $_settings->info('name'):$_settings->info('short_name'); ?> - 관리자</a>
        </li>
    </ul>
    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <!-- Navbar Search -->
        <!-- <li class="nav-item">
          <a class="nav-link" data-widget="navbar-search" href="#" role="button">
          <i class="fas fa-search"></i>
          </a>
          <div class="navbar-search-block">
            <form class="form-inline">
              <div class="input-group input-group-sm">
                <input class="form-control form-control-navbar" type="search" placeholder="검색" aria-label="검색">
                <div class="input-group-append">
                  <button class="btn btn-navbar" type="submit">
                  <i class="fas fa-search"></i>
                  </button>
                  <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                  <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
            </form>
          </div>
        </li> -->

        <!-- 알림 드롭다운 -->
        <li class="nav-item dropdown notification-bell-wrapper">
            <a class="nav-link notification-bell" href="#" role="button" data-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-bell"></i>
                <span class="notification-badge badge badge-danger" style="display: none;">0</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right notification-dropdown">
                <div class="notification-header">
                    <h6 class="mb-0">알림</h6>
                    <div>
                        <button class="sound-toggle-btn notification-toggle-btn" onclick="toggleNotification(); return false;" title="알림 켜기/끄기">
                            <i class="fas fa-bell" id="notification-toggle-icon"></i>
                        </button>
                        <button class="sound-toggle-btn" onclick="toggleNotificationSound(); return false;" title="알림음 켜기/끄기">
                            <i class="fas fa-volume-up" id="sound-toggle-icon"></i>
                        </button>
                        <a href="#" class="btn btn-sm btn-link" onclick="NotificationSystem.markAllRead(); return false;">모두 읽음</a>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <div class="notification-list">
                    <div class="notification-empty text-center py-3">
                        <i class="fas fa-bell-slash fa-2x text-muted"></i>
                        <p class="text-muted">새로운 알림이 없습니다</p>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <div class="notification-footer text-center">
                    <a href="./?page=document_requests">전체 알림 보기</a>
                </div>
            </div>
        </li>

        <!-- Messages Dropdown Menu -->
        <li class="nav-item">
            <div class="btn-group nav-link">
                <button type="button" class="btn btn-rounded badge badge-light dropdown-toggle dropdown-icon" data-toggle="dropdown">
                    <span><img src="<?php echo validate_image($_settings->userdata('avatar')) ?>" class="img-circle elevation-2 user-img" alt="User Image"></span>
                    <span class="ml-3"><?php echo ucwords($_settings->userdata('firstname').' '.$_settings->userdata('lastname')) ?></span>
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <div class="dropdown-menu" role="menu">
                    <a class="dropdown-item" href="<?php echo base_url.'admin/?page=user' ?>"><span class="fa fa-user"></span> 내 계정</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?php echo base_url.'/classes/Login.php?f=logout' ?>"><span class="fas fa-sign-out-alt"></span> 로그아웃</a>
                </div>
            </div>
        </li>
        <li class="nav-item">

        </li>
        <!--  <li class="nav-item">
           <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
           <i class="fas fa-th-large"></i>
           </a>
         </li> -->
    </ul>
</nav>
<!-- /.navbar -->

<script>
    // 알림음 토글 함수
    function toggleNotificationSound() {
        if (typeof NotificationSystem === 'undefined' || !NotificationSystem.isInitialized) {
            console.error('NotificationSystem이 아직 로드되지 않았습니다.');
            alert('알림 시스템이 초기화 중입니다. 잠시 후 다시 시도해주세요.');
            return;
        }

        const isEnabled = NotificationSystem.toggleSound();
        const icon = document.getElementById('sound-toggle-icon');
        const btn = icon.parentElement;

        if (isEnabled) {
            icon.className = 'fas fa-volume-up';
            btn.classList.remove('muted');
            btn.title = '알림음 끄기';
        } else {
            icon.className = 'fas fa-volume-mute';
            btn.classList.add('muted');
            btn.title = '알림음 켜기';
        }
    }

    // 알림 전체 토글 함수
    function toggleNotification() {
        if (typeof NotificationSystem === 'undefined' || !NotificationSystem.isInitialized) {
            console.error('NotificationSystem이 아직 로드되지 않았습니다.');
            alert('알림 시스템이 초기화 중입니다. 잠시 후 다시 시도해주세요.');
            return;
        }

        const isEnabled = NotificationSystem.toggleNotification();
        const icon = document.getElementById('notification-toggle-icon');
        const btn = icon.parentElement;

        if (isEnabled) {
            icon.className = 'fas fa-bell';
            btn.classList.remove('muted');
            btn.title = '알림 끄기';
        } else {
            icon.className = 'fas fa-bell-slash';
            btn.classList.add('muted');
            btn.title = '알림 켜기';
        }
    }

    // 페이지 로드 시 알림 상태 확인
    $(document).ready(function() {
        // NotificationSystem이 초기화될 때까지 대기
        function checkNotificationSystem() {
            if (typeof NotificationSystem !== 'undefined' && NotificationSystem.isInitialized) {
                // 알림음 상태
                if (!NotificationSystem.soundEnabled) {
                    const soundIcon = document.getElementById('sound-toggle-icon');
                    if (soundIcon) {
                        soundIcon.className = 'fas fa-volume-mute';
                        soundIcon.parentElement.classList.add('muted');
                        soundIcon.parentElement.title = '알림음 켜기';
                    }
                }

                // 알림 상태
                if (!NotificationSystem.notificationEnabled) {
                    const notifIcon = document.getElementById('notification-toggle-icon');
                    if (notifIcon) {
                        notifIcon.className = 'fas fa-bell-slash';
                        notifIcon.parentElement.classList.add('muted');
                        notifIcon.parentElement.title = '알림 켜기';
                    }
                }

                console.log('NotificationSystem 상태:', {
                    soundEnabled: NotificationSystem.soundEnabled,
                    notificationEnabled: NotificationSystem.notificationEnabled
                });
            } else {
                // 아직 초기화되지 않았으면 다시 시도
                setTimeout(checkNotificationSystem, 100);
            }
        }

        // 초기 체크 시작
        checkNotificationSystem();
    });
</script>
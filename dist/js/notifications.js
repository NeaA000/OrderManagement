/**
 * 실시간 업로드 알림 시스템
 * Purchase Order Management System
 */

const NotificationSystem = {
    // 설정
    checkInterval: 5000, // 5초마다 체크
    maxNotifications: 20, // 최대 표시 알림 수
    toastDuration: 5000, // 토스트 표시 시간
    soundEnabled: true, // 알림음 활성화

    // 상태
    lastCheck: null,
    notificationSound: null,
    isInitialized: false,
    unreadCount: 0,

    /**
     * 시스템 초기화
     */
    init: function() {
        if (this.isInitialized) return;

        console.log('NotificationSystem: 초기화 시작');

        // 초기 설정
        this.lastCheck = new Date().toISOString();
        this.loadSettings();

        // UI 설정
        this.setupNotificationBell();
        this.bindEvents();

        // 알림음 로드
        this.loadNotificationSound();

        // 체크 시작
        this.startChecking();

        // 초기 로드
        this.checkNewNotifications();

        this.isInitialized = true;
        console.log('NotificationSystem: 초기화 완료');
    },

    /**
     * 설정 로드
     */
    loadSettings: function() {
        // 로컬 스토리지에서 설정 로드
        const saved = localStorage.getItem('notificationSettings');
        if (saved) {
            try {
                const settings = JSON.parse(saved);
                this.soundEnabled = settings.soundEnabled !== undefined ? settings.soundEnabled : true;
                this.checkInterval = settings.checkInterval || 5000;
            } catch (e) {
                console.error('설정 로드 실패:', e);
            }
        }
    },

    /**
     * 설정 저장
     */
    saveSettings: function() {
        const settings = {
            soundEnabled: this.soundEnabled,
            checkInterval: this.checkInterval
        };
        localStorage.setItem('notificationSettings', JSON.stringify(settings));
    },

    /**
     * 알림 벨 UI 설정
     */
    setupNotificationBell: function() {
        // 이미 존재하는지 확인
        if ($('.notification-bell-wrapper').length > 0) {
            return;
        }

        // 헤더 네비게이션 찾기
        const navbarNav = $('.navbar-nav.ml-auto');
        if (navbarNav.length === 0) {
            console.warn('NotificationSystem: 네비게이션 바를 찾을 수 없습니다');
            return;
        }

        // 알림 벨 HTML
        const bellHtml = `
            <li class="nav-item dropdown notification-bell-wrapper">
                <a class="nav-link notification-bell" href="#" role="button" data-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge" style="display: none;">0</span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right notification-dropdown">
                    <div class="notification-header">
                        <h5>알림</h5>
                        <button class="btn btn-sm btn-link" onclick="NotificationSystem.markAllRead()">
                            모두 읽음
                        </button>
                    </div>
                    <div class="notification-list">
                        <div class="notification-empty">
                            <i class="fas fa-bell-slash"></i>
                            <p>새로운 알림이 없습니다</p>
                        </div>
                    </div>
                    <div class="notification-footer">
                        <a href="#" onclick="NotificationSystem.showAllNotifications(); return false;">
                            전체 알림 보기
                        </a>
                    </div>
                </div>
            </li>
        `;

        // 첫 번째 nav-item 앞에 삽입
        const firstNavItem = navbarNav.find('.nav-item').first();
        if (firstNavItem.length > 0) {
            firstNavItem.before(bellHtml);
        } else {
            navbarNav.append(bellHtml);
        }
    },

    /**
     * 이벤트 바인딩
     */
    bindEvents: function() {
        // 알림 벨 클릭
        $(document).off('click.notification').on('click.notification', '.notification-bell', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('.notification-dropdown').toggleClass('show');

            // 열릴 때 새로고침
            if ($('.notification-dropdown').hasClass('show')) {
                NotificationSystem.checkNewNotifications();
            }
        });

        // 외부 클릭시 닫기
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.notification-bell-wrapper').length) {
                $('.notification-dropdown').removeClass('show');
            }
        });

        // 알림 아이템 클릭
        $(document).on('click', '.notification-item', function(e) {
            e.preventDefault();
            const notificationId = $(this).data('notification-id');
            const requestId = $(this).data('request-id');

            // 읽음 처리
            if (notificationId) {
                NotificationSystem.markAsRead(notificationId);
            }

            // 해당 요청으로 이동
            if (requestId) {
                window.location.href = `./?page=document_requests/view_request&id=${requestId}`;
            }
        });

        // 토스트 닫기 버튼
        $(document).on('click', '.upload-toast-close', function() {
            const toast = $(this).closest('.upload-toast');
            NotificationSystem.removeToast(toast);
        });
    },

    /**
     * 정기 체크 시작
     */
    startChecking: function() {
        // 기존 인터벌 제거
        if (this.checkTimer) {
            clearInterval(this.checkTimer);
        }

        // 새 인터벌 설정
        this.checkTimer = setInterval(() => {
            this.checkNewNotifications();
        }, this.checkInterval);
    },

    /**
     * 정기 체크 중지
     */
    stopChecking: function() {
        if (this.checkTimer) {
            clearInterval(this.checkTimer);
            this.checkTimer = null;
        }
    },

    /**
     * 새 알림 체크
     */
    // notifications.js의 checkNewNotifications 함수 수정 부분
// 206번째 줄 근처를 다음과 같이 수정하세요:

    checkNewNotifications: function() {
        $.ajax({
            url: 'ajax/get_notifications.php',
            type: 'GET',
            data: {
                action: 'get',
                limit: this.maxNotifications
            },
            dataType: 'json',
            success: function(response) {
                if (response && response.status === 'success') {
                    NotificationSystem.updateNotificationUI(response);

                    // 새 알림 토스트 표시
                    if (response.notifications && response.notifications.length > 0) {
                        response.notifications.forEach(notif => {
                            // 1분 이내 알림만 토스트 표시
                            const uploadTime = new Date(notif.uploaded_at);
                            const now = new Date();
                            const diffMinutes = (now - uploadTime) / 60000;

                            if (diffMinutes <= 1) {
                                NotificationSystem.showToast(notif);
                            }
                        });
                    }

                    // 테이블 업데이트 (document_requests 페이지인 경우)
                    if (window.location.href.includes('page=document_requests') && !window.location.href.includes('view_request')) {
                        NotificationSystem.updateRequestsTable();
                    }
                }
            },
            error: function(xhr, status, error) {
                // 오류를 콘솔에만 기록하고 사용자에게는 표시하지 않음
                console.error('알림 체크 실패:', error);

                // 401 에러인 경우 로그인 페이지로 리다이렉트
                if (xhr.status === 401) {
                    window.location.href = './login.php';
                }
            }
        });
    },

    /**
     * 알림 UI 업데이트
     */
    updateNotificationUI: function(data) {
        // 배지 업데이트
        const badge = $('.notification-badge');
        if (data.total_unread > 0) {
            badge.text(data.total_unread > 99 ? '99+' : data.total_unread);
            badge.show();
            this.unreadCount = data.total_unread;
        } else {
            badge.hide();
            this.unreadCount = 0;
        }

        // 리스트 업데이트
        const list = $('.notification-list');
        if (data.notifications && data.notifications.length > 0) {
            let html = '';

            data.notifications.forEach(notif => {
                const iconClass = this.getIconForDocument(notif.document_name);
                const timeText = notif.time_text || this.formatTime(notif.uploaded_at);

                html += `
                    <div class="notification-item unread" 
                         data-notification-id="${notif.id}"
                         data-request-id="${notif.request_id}">
                        <div class="notification-content">
                            <div class="notification-icon">
                                <i class="${iconClass}"></i>
                            </div>
                            <div class="notification-text">
                                <div class="notification-title">
                                    ${notif.supplier_name || notif.supplier_full_name || '알 수 없는 업체'}
                                </div>
                                <div class="notification-desc">
                                    ${notif.document_name} 업로드
                                    ${notif.file_name ? `<br><small>${notif.file_name}</small>` : ''}
                                </div>
                                <div class="notification-time">
                                    ${timeText}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            list.html(html);
        } else {
            list.html(`
                <div class="notification-empty">
                    <i class="fas fa-bell-slash"></i>
                    <p>새로운 알림이 없습니다</p>
                </div>
            `);
        }
    },

    /**
     * 토스트 표시
     */
    showToast: function(notification) {
        // 토스트 컨테이너 확인/생성
        if (!$('.toast-container').length) {
            $('body').append('<div class="toast-container"></div>');
        }

        const toastId = 'toast-' + notification.id;

        // 이미 표시된 토스트인지 확인
        if ($(`#${toastId}`).length > 0) {
            return;
        }

        // 토스트 HTML
        const toastHtml = `
            <div class="upload-toast" id="${toastId}" data-notification-id="${notification.id}">
                <div class="upload-toast-header">
                    <div class="upload-toast-title">
                        <i class="fas fa-check-circle"></i>
                        새 파일 업로드
                    </div>
                    <button class="upload-toast-close" type="button">
                        ×
                    </button>
                </div>
                <div class="upload-toast-body">
                    <strong>${notification.supplier_name}</strong>에서
                    <strong>${notification.document_name}</strong>을(를) 업로드했습니다.
                    ${notification.file_name ? `<br><small class="text-muted">${notification.file_name}</small>` : ''}
                </div>
                <div class="upload-toast-time">방금 전</div>
            </div>
        `;

        // 토스트 추가
        $('.toast-container').prepend(toastHtml);

        // 사운드 재생
        this.playNotificationSound();

        // 자동 제거
        setTimeout(() => {
            this.removeToast($(`#${toastId}`));
        }, this.toastDuration);
    },

    /**
     * 토스트 제거
     */
    removeToast: function(toast) {
        toast.addClass('removing');
        setTimeout(() => {
            toast.remove();
        }, 300);
    },

    /**
     * 알림 읽음 처리
     */
    markAsRead: function(notificationId) {
        $.ajax({
            url: 'ajax/get_notifications.php',
            type: 'POST',
            data: {
                action: 'mark_read',
                notification_id: notificationId
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // UI 업데이트
                    $(`.notification-item[data-notification-id="${notificationId}"]`).removeClass('unread');

                    // 카운트 업데이트
                    NotificationSystem.unreadCount--;
                    if (NotificationSystem.unreadCount <= 0) {
                        $('.notification-badge').hide();
                    } else {
                        $('.notification-badge').text(NotificationSystem.unreadCount);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('읽음 처리 실패:', error);
            }
        });
    },

    /**
     * 모든 알림 읽음 처리
     */
    markAllRead: function() {
        $.ajax({
            url: 'ajax/get_notifications.php',
            type: 'POST',
            data: { action: 'mark_all_read' },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('.notification-item').removeClass('unread');
                    $('.notification-badge').hide();
                    NotificationSystem.unreadCount = 0;

                    // 성공 메시지
                    if (typeof toastr !== 'undefined') {
                        toastr.success('모든 알림을 읽음으로 표시했습니다.');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('모두 읽음 처리 실패:', error);
                if (typeof toastr !== 'undefined') {
                    toastr.error('처리 중 오류가 발생했습니다.');
                }
            }
        });
    },

    /**
     * 요청 테이블 업데이트
     */
    updateRequestsTable: function() {
        $.ajax({
            url: 'ajax/get_notifications.php',
            type: 'GET',
            data: {
                action: 'get_recent_uploads',
                since: this.lastCheck
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.uploads && response.uploads.length > 0) {
                    response.uploads.forEach(upload => {
                        const row = $(`tr[data-request-id="${upload.request_id}"]`);
                        if (row.length > 0) {
                            // 진행률 업데이트
                            const progressBar = row.find('.progress-bar');
                            if (progressBar.length > 0) {
                                progressBar.css('width', upload.progress + '%');
                                progressBar.text(upload.progress + '%');
                            }

                            // 하이라이트 효과
                            row.addClass('table-row-highlight');
                            setTimeout(() => {
                                row.removeClass('table-row-highlight');
                            }, 2000);

                            // NEW 배지 추가
                            const firstTd = row.find('td:first');
                            if (!firstTd.find('.new-upload-indicator').length) {
                                firstTd.append('<span class="new-upload-indicator">NEW</span>');

                                // 10초 후 제거
                                setTimeout(() => {
                                    firstTd.find('.new-upload-indicator').fadeOut(500, function() {
                                        $(this).remove();
                                    });
                                }, 10000);
                            }
                        }
                    });
                }

                // 마지막 체크 시간 업데이트
                NotificationSystem.lastCheck = new Date().toISOString();
            },
            error: function(xhr, status, error) {
                console.error('테이블 업데이트 실패:', error);
            }
        });
    },

    /**
     * 알림음 로드
     */
    loadNotificationSound: function() {
        try {
            this.notificationSound = new Audio('dist/sounds/notification.mp3');
            this.notificationSound.volume = 0.5;

            // 사운드 파일 존재 확인
            this.notificationSound.addEventListener('error', () => {
                console.warn('알림음 파일을 찾을 수 없습니다');
                this.notificationSound = null;
            });
        } catch (e) {
            console.warn('알림음 로드 실패:', e);
            this.notificationSound = null;
        }
    },

    /**
     * 알림음 재생
     */
    playNotificationSound: function() {
        if (this.soundEnabled && this.notificationSound) {
            try {
                // 사운드 재생 (프로미스 처리)
                const playPromise = this.notificationSound.play();
                if (playPromise !== undefined) {
                    playPromise.catch(error => {
                        console.warn('알림음 재생 실패:', error);
                    });
                }
            } catch (e) {
                console.warn('알림음 재생 오류:', e);
            }
        }
    },

    /**
     * 사운드 토글
     */
    toggleSound: function() {
        this.soundEnabled = !this.soundEnabled;
        this.saveSettings();

        if (typeof toastr !== 'undefined') {
            toastr.info(this.soundEnabled ? '알림음이 켜졌습니다.' : '알림음이 꺼졌습니다.');
        }

        return this.soundEnabled;
    },

    /**
     * 전체 알림 페이지로 이동
     */
    showAllNotifications: function() {
        // 전체 알림 페이지가 있다면
        if ($('#notifications-page').length > 0) {
            window.location.href = './?page=notifications';
        } else {
            // 없으면 요청 목록으로
            window.location.href = './?page=document_requests';
        }
    },

    /**
     * 문서 타입별 아이콘 반환
     */
    getIconForDocument: function(documentName) {
        const lowerName = documentName ? documentName.toLowerCase() : '';

        if (lowerName.includes('사업자')) return 'fas fa-building';
        if (lowerName.includes('재무')) return 'fas fa-chart-line';
        if (lowerName.includes('인증')) return 'fas fa-certificate';
        if (lowerName.includes('계약')) return 'fas fa-file-contract';
        if (lowerName.includes('보험')) return 'fas fa-shield-alt';
        if (lowerName.includes('세금')) return 'fas fa-receipt';

        return 'fas fa-file-upload';
    },

    /**
     * 시간 포맷팅
     */
    formatTime: function(datetime) {
        const date = new Date(datetime);
        const now = new Date();
        const diff = (now - date) / 1000; // 초 단위

        if (diff < 60) return '방금 전';
        if (diff < 3600) return Math.floor(diff / 60) + '분 전';
        if (diff < 86400) return Math.floor(diff / 3600) + '시간 전';
        if (diff < 604800) return Math.floor(diff / 86400) + '일 전';

        return date.toLocaleDateString('ko-KR');
    },

    /**
     * 시스템 정리
     */
    destroy: function() {
        this.stopChecking();
        $('.notification-bell-wrapper').remove();
        $('.toast-container').remove();
        $(document).off('click.notification');
        this.isInitialized = false;
    }
};

// jQuery 준비 완료시 자동 초기화
$(document).ready(function() {
    // AdminLTE 관리자 페이지에서만 실행
    if ($('.main-header').length > 0) {
        // base_url이 정의되어 있는지 확인 (AdminLTE에서 기본 제공)
        if (typeof _base_url_ !== 'undefined' || typeof base_url !== 'undefined') {
            // 자동 초기화 (로그인 체크는 서버에서 처리됨)
            NotificationSystem.init();
        }
    }
});

// 전역 객체로 노출 (디버깅 및 외부 접근용)
window.NotificationSystem = NotificationSystem;
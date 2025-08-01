<?php
// admin/email_settings/index.php

// 세션 시작 전에 출력 버퍼링 시작
ob_start();

// initialize.php와 관련 파일들을 직접 포함
$base_dir = dirname(dirname(dirname(__FILE__)));

// initialize.php 포함
if(file_exists($base_dir . '/initialize.php')) {
    require_once($base_dir . '/initialize.php');
}

// 필요한 클래스들 포함
require_once($base_dir . '/classes/DBConnection.php');
require_once($base_dir . '/classes/SystemSettings.php');

// DB 연결
$db = new DBConnection;
$conn = $db->conn;

// SystemSettings 인스턴스가 없으면 생성
if(!isset($_settings)) {
    $_settings = new SystemSettings();
}

// 현재 이메일 템플릿 가져오기
$template_qry = $conn->query("
    SELECT * FROM email_templates 
    WHERE template_type = 'request_notification' 
    AND is_default = 1 
    LIMIT 1
");

$template = $template_qry->fetch_assoc();

// SMTP 설정 가져오기
$smtp_host = $_settings->info('smtp_host') ?? '';
$smtp_port = $_settings->info('smtp_port') ?? '';
$smtp_username = $_settings->info('smtp_username') ?? '';
$smtp_from_name = $_settings->info('smtp_from_name') ?? '';
$smtp_from_email = $_settings->info('smtp_from_email') ?? '';
?>

<style>
    .email-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .email-editor {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 30px;
        margin-bottom: 20px;
    }

    .editor-section {
        margin-bottom: 25px;
    }

    .editor-label {
        font-weight: 600;
        color: #333;
        margin-bottom: 10px;
        display: block;
        font-size: 16px;
    }

    .form-control {
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        padding: 12px 15px;
        font-size: 15px;
        transition: all 0.3s;
    }

    .form-control:focus {
        border-color: #4CAF50;
        box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
    }

    textarea.form-control {
        min-height: 300px;
        font-family: 'Malgun Gothic', sans-serif;
        line-height: 1.6;
    }

    .variable-box {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 25px;
    }

    .variable-title {
        font-weight: 600;
        color: #495057;
        margin-bottom: 15px;
        font-size: 15px;
    }

    .variable-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 10px;
    }

    .variable-item {
        display: flex;
        align-items: center;
        padding: 8px 12px;
        background: #fff;
        border-radius: 6px;
        border: 1px solid #e9ecef;
        cursor: pointer;
        transition: all 0.2s;
    }

    .variable-item:hover {
        background: #e3f2fd;
        border-color: #2196F3;
    }

    .variable-code {
        font-family: 'Courier New', monospace;
        color: #0066cc;
        font-weight: 600;
        margin-right: 10px;
    }

    .variable-desc {
        color: #666;
        font-size: 14px;
    }

    .preview-section {
        background: #f5f5f5;
        border-radius: 10px;
        padding: 30px;
        margin-top: 30px;
    }

    .preview-title {
        font-size: 18px;
        font-weight: 600;
        color: #333;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .preview-content {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 30px;
        min-height: 400px;
    }

    .preview-email {
        font-family: 'Malgun Gothic', sans-serif;
        line-height: 1.8;
        color: #333;
    }

    .button-group {
        display: flex;
        gap: 10px;
        margin-top: 30px;
    }

    .btn-save {
        background: #4CAF50;
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-save:hover {
        background: #45a049;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
    }

    .btn-preview {
        background: #2196F3;
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-preview:hover {
        background: #1976D2;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
    }

    .btn-test {
        background: #FF9800;
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-test:hover {
        background: #F57C00;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
    }

    .smtp-settings {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
    }

    .smtp-title {
        font-weight: 600;
        color: #856404;
        margin-bottom: 15px;
        font-size: 16px;
    }

    .smtp-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }

    .smtp-item {
        display: flex;
        flex-direction: column;
    }

    .smtp-label {
        font-size: 13px;
        color: #666;
        margin-bottom: 5px;
    }

    .smtp-value {
        font-size: 14px;
        color: #333;
        font-weight: 500;
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
        display: none;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    @media (max-width: 768px) {
        .variable-list {
            grid-template-columns: 1fr;
        }

        .button-group {
            flex-direction: column;
        }

        .btn-save, .btn-preview, .btn-test {
            width: 100%;
        }
    }
</style>

<div class="email-container">
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-envelope"></i> 이메일 양식 설정
            </h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="maximize">
                    <i class="fas fa-expand"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-success" id="success-alert">
                <i class="fas fa-check-circle"></i> 이메일 템플릿이 성공적으로 저장되었습니다.
            </div>

            <div class="alert alert-error" id="error-alert">
                <i class="fas fa-exclamation-circle"></i> <span id="error-message">오류가 발생했습니다.</span>
            </div>

            <!-- SMTP 설정 정보 표시 -->
            <div class="smtp-settings">
                <div class="smtp-title">
                    <i class="fas fa-server"></i> 현재 이메일 발송 설정
                </div>
                <div class="smtp-grid">
                    <div class="smtp-item">
                        <span class="smtp-label">SMTP 서버</span>
                        <span class="smtp-value"><?php echo $smtp_host ?: '설정되지 않음' ?></span>
                    </div>
                    <div class="smtp-item">
                        <span class="smtp-label">포트</span>
                        <span class="smtp-value"><?php echo $smtp_port ?: '설정되지 않음' ?></span>
                    </div>
                    <div class="smtp-item">
                        <span class="smtp-label">발신자 이름</span>
                        <span class="smtp-value"><?php echo $smtp_from_name ?: '설정되지 않음' ?></span>
                    </div>
                    <div class="smtp-item">
                        <span class="smtp-label">발신 이메일</span>
                        <span class="smtp-value"><?php echo $smtp_from_email ?: $smtp_username ?></span>
                    </div>
                </div>
            </div>

            <form id="email-template-form">
                <input type="hidden" name="template_id" value="<?php echo $template['id'] ?? '' ?>">

                <div class="email-editor">
                    <!-- 이메일 제목 -->
                    <div class="editor-section">
                        <label class="editor-label">
                            <i class="fas fa-heading"></i> 이메일 제목
                        </label>
                        <input type="text"
                               name="subject"
                               id="email-subject"
                               class="form-control"
                               value="<?php echo htmlspecialchars($template['subject'] ?? '[{{company_name}}] 서류 제출 요청 - {{project_name}}') ?>"
                               placeholder="이메일 제목을 입력하세요">
                    </div>

                    <!-- 사용 가능한 변수 -->
                    <div class="variable-box">
                        <div class="variable-title">
                            <i class="fas fa-code"></i> 사용 가능한 변수 (클릭하여 삽입)
                        </div>
                        <div class="variable-list">
                            <div class="variable-item" onclick="insertVariable('{{company_name}}')">
                                <span class="variable-code">{{company_name}}</span>
                                <span class="variable-desc">회사명</span>
                            </div>
                            <div class="variable-item" onclick="insertVariable('{{supplier_name}}')">
                                <span class="variable-code">{{supplier_name}}</span>
                                <span class="variable-desc">의뢰처명</span>
                            </div>
                            <div class="variable-item" onclick="insertVariable('{{project_name}}')">
                                <span class="variable-code">{{project_name}}</span>
                                <span class="variable-desc">프로젝트명</span>
                            </div>
                            <div class="variable-item" onclick="insertVariable('{{due_date}}')">
                                <span class="variable-code">{{due_date}}</span>
                                <span class="variable-desc">제출 기한</span>
                            </div>
                            <div class="variable-item" onclick="insertVariable('{{upload_link}}')">
                                <span class="variable-code">{{upload_link}}</span>
                                <span class="variable-desc">업로드 링크</span>
                            </div>
                            <div class="variable-item" onclick="insertVariable('{{document_list}}')">
                                <span class="variable-code">{{document_list}}</span>
                                <span class="variable-desc">요청 서류 목록</span>
                            </div>
                        </div>
                    </div>

                    <!-- 이메일 내용 -->
                    <div class="editor-section">
                        <label class="editor-label">
                            <i class="fas fa-align-left"></i> 이메일 내용
                        </label>
                        <textarea name="content"
                                  id="email-content"
                                  class="form-control"
                                  placeholder="이메일 내용을 입력하세요"><?php
                            echo htmlspecialchars($template['content'] ??
                                '안녕하세요 {{supplier_name}} 담당자님,

{{project_name}} 프로젝트와 관련하여 서류 제출을 요청드립니다.

제출 기한: {{due_date}}
업로드 링크: {{upload_link}}

요청 서류:
{{document_list}}

문의사항이 있으시면 언제든 연락 주시기 바랍니다.

감사합니다.
{{company_name}} 드림')
                            ?></textarea>
                    </div>
                </div>

                <!-- 버튼 그룹 -->
                <div class="button-group">
                    <button type="button" class="btn-preview" onclick="previewEmail()">
                        <i class="fas fa-eye"></i> 미리보기
                    </button>
                    <button type="button" class="btn-test" onclick="sendTestEmail()">
                        <i class="fas fa-paper-plane"></i> 테스트 발송
                    </button>
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> 저장하기
                    </button>
                </div>

                <!-- 미리보기 섹션 -->
                <div class="preview-section" id="preview-section" style="display: none;">
                    <div class="preview-title">
                        <span><i class="fas fa-desktop"></i> 이메일 미리보기</span>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="closePreview()">
                            <i class="fas fa-times"></i> 닫기
                        </button>
                    </div>
                    <div class="preview-content">
                        <div id="preview-subject" style="font-size: 20px; font-weight: bold; margin-bottom: 20px; color: #333;"></div>
                        <div id="preview-body" class="preview-email"></div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 테스트 이메일 모달 -->
<div class="modal fade" id="testEmailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-paper-plane"></i> 테스트 이메일 발송
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>받는 사람 이메일 주소</label>
                    <input type="email"
                           id="test-email-address"
                           class="form-control"
                           placeholder="test@example.com"
                           value="<?php echo $_settings->userdata('username') ?>">
                    <small class="text-muted">테스트 이메일을 받을 주소를 입력하세요.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" onclick="doSendTestEmail()">
                    <i class="fas fa-paper-plane"></i> 발송
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // 현재 커서 위치 저장
    let lastCursorPosition = 0;

    // 변수 삽입 함수
    function insertVariable(variable) {
        const textarea = document.getElementById('email-content');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;

        // 선택된 텍스트가 있으면 교체, 없으면 커서 위치에 삽입
        textarea.value = text.substring(0, start) + variable + text.substring(end);

        // 커서를 삽입된 변수 뒤로 이동
        textarea.selectionStart = textarea.selectionEnd = start + variable.length;
        textarea.focus();
    }

    // 미리보기 함수
    function previewEmail() {
        const subject = document.getElementById('email-subject').value;
        const content = document.getElementById('email-content').value;

        // 샘플 데이터로 변수 치환
        const sampleData = {
            '{{company_name}}': '중부제해예방관리원',
            '{{supplier_name}}': '(주)건설안전',
            '{{project_name}}': '서울시 도시재생 프로젝트',
            '{{due_date}}': '2025-08-15',
            '{{upload_link}}': '<a href="#" style="color: #007bff;">https://example.com/upload/abc123</a>',
            '{{document_list}}': `• 안전관리계획서 (필수)<br>
• 유해위험방지계획서 (필수)<br>
• 사업자등록증 (필수)<br>
• 건설업면허증 (선택)`
        };

        let previewSubject = subject;
        let previewContent = content;

        // 변수 치환
        for (const [key, value] of Object.entries(sampleData)) {
            previewSubject = previewSubject.replace(new RegExp(key, 'g'), value);
            previewContent = previewContent.replace(new RegExp(key, 'g'), value);
        }

        // 줄바꿈 처리
        previewContent = previewContent.replace(/\n/g, '<br>');

        // 미리보기 표시
        document.getElementById('preview-subject').innerHTML = previewSubject;
        document.getElementById('preview-body').innerHTML = previewContent;
        document.getElementById('preview-section').style.display = 'block';

        // 미리보기로 스크롤
        document.getElementById('preview-section').scrollIntoView({ behavior: 'smooth' });
    }

    // 미리보기 닫기
    function closePreview() {
        document.getElementById('preview-section').style.display = 'none';
    }

    // 테스트 이메일 모달 열기
    function sendTestEmail() {
        $('#testEmailModal').modal('show');
    }

    // 테스트 이메일 발송
    function doSendTestEmail() {
        const email = document.getElementById('test-email-address').value;
        const subject = document.getElementById('email-subject').value;
        const content = document.getElementById('email-content').value;

        if (!email) {
            alert('이메일 주소를 입력해주세요.');
            return;
        }

        start_loader();

        $.ajax({
            url: _base_url_ + 'classes/Master.php?f=send_test_email',
            method: 'POST',
            data: {
                email: email,
                subject: subject,
                content: content
            },
            dataType: 'json',
            success: function(resp) {
                end_loader();
                if (resp.status == 'success') {
                    alert_toast('테스트 이메일이 발송되었습니다.', 'success');
                    $('#testEmailModal').modal('hide');
                } else {
                    alert_toast(resp.msg || '발송 중 오류가 발생했습니다.', 'error');
                }
            },
            error: function(err) {
                end_loader();
                console.error(err);
                alert_toast('발송 중 오류가 발생했습니다.', 'error');
            }
        });
    }

    // 폼 제출 처리
    $(document).ready(function() {
        $('#email-template-form').submit(function(e) {
            e.preventDefault();

            start_loader();

            $.ajax({
                url: _base_url_ + 'classes/Master.php?f=save_email_template',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(resp) {
                    end_loader();
                    if (resp.status == 'success') {
                        $('#success-alert').fadeIn().delay(3000).fadeOut();
                    } else {
                        $('#error-message').text(resp.msg || '저장 중 오류가 발생했습니다.');
                        $('#error-alert').fadeIn().delay(3000).fadeOut();
                    }
                },
                error: function(err) {
                    end_loader();
                    console.error(err);
                    $('#error-message').text('저장 중 오류가 발생했습니다.');
                    $('#error-alert').fadeIn().delay(3000).fadeOut();
                }
            });
        });

        // 텍스트 영역 자동 높이 조절
        $('#email-content').on('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        }).trigger('input');
    });
</script>
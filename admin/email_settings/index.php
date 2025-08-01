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
        background: #f8f9fa;
        border-radius: 10px;
        padding: 30px;
        margin-top: 30px;
        display: none;
    }

    .preview-content {
        background: #fff;
        border-radius: 8px;
        padding: 30px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .smtp-info {
        background: #fff3cd;
        border: 1px solid #ffeeba;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
    }

    .smtp-info h6 {
        color: #856404;
        margin-bottom: 15px;
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
        font-size: 12px;
        color: #6c757d;
        margin-bottom: 3px;
    }

    .smtp-value {
        font-weight: 600;
        color: #495057;
    }

    .button-group {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 30px;
    }

    .btn {
        padding: 10px 25px;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    /* 알림 스타일 */
    .alert {
        border-radius: 8px;
        display: none;
    }

    /* Summernote 커스텀 스타일 */
    .note-editor.note-frame {
        border: 2px solid #e0e0e0;
        border-radius: 8px;
    }

    .note-editor.note-frame:focus-within {
        border-color: #4CAF50;
        box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
    }
</style>

<div class="email-container">
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">이메일 템플릿 설정</h3>
        </div>
        <div class="card-body">
            <!-- 알림 메시지 -->
            <div id="success-alert" class="alert alert-success">
                <i class="fas fa-check-circle"></i> 이메일 템플릿이 성공적으로 저장되었습니다.
            </div>
            <div id="error-alert" class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <span id="error-message">저장 중 오류가 발생했습니다.</span>
            </div>

            <!-- SMTP 설정 정보 표시 -->
            <div class="smtp-info">
                <h6><i class="fas fa-server"></i> 현재 이메일 발송 설정</h6>
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
                            <div class="variable-item" onclick="insertVariable('{{contact_person}}')">
                                <span class="variable-code">{{contact_person}}</span>
                                <span class="variable-desc">담당자명</span>
                            </div>
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
                            <div class="variable-item" onclick="insertVariable('{{required_documents}}')">
                                <span class="variable-code">{{required_documents}}</span>
                                <span class="variable-desc">필수 서류</span>
                            </div>
                            <div class="variable-item" onclick="insertVariable('{{optional_documents}}')">
                                <span class="variable-code">{{optional_documents}}</span>
                                <span class="variable-desc">선택 서류</span>
                            </div>
                            <div class="variable-item" onclick="insertVariable('{{additional_notes}}')">
                                <span class="variable-code">{{additional_notes}}</span>
                                <span class="variable-desc">추가 요청사항</span>
                            </div>
                        </div>
                    </div>

                    <!-- 이메일 내용 (HTML 에디터) -->
                    <div class="editor-section">
                        <label class="editor-label">
                            <i class="fas fa-align-left"></i> 이메일 내용
                        </label>
                        <textarea name="content" id="email-content" class="form-control summernote">
<?php
$default_content = '<div style="font-family: \'Noto Sans KR\', sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2>서류 제출 요청</h2>
    <p>안녕하세요, {{contact_person}}님</p>
    <p>{{company_name}}에서 {{project_name}} 프로젝트와 관련하여 서류 제출을 요청드립니다.</p>
    
    <div style="background-color: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 5px;">
        <h3>프로젝트 정보</h3>
        <p><strong>프로젝트명:</strong> {{project_name}}</p>
        <p><strong>제출 기한:</strong> <span style="color: #dc3545;">{{due_date}}</span></p>
    </div>
    
    <div style="margin: 20px 0;">
        <h3>필수 제출 서류</h3>
        {{required_documents}}
        
        <h3>선택 제출 서류</h3>
        {{optional_documents}}
        
        <h3>추가 요청사항</h3>
        <p>{{additional_notes}}</p>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{upload_link}}" style="background-color: #007bff; color: white; padding: 10px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">서류 업로드하기</a>
    </div>
    
    <p>문의사항이 있으시면 회신 부탁드립니다.</p>
    <p>감사합니다.</p>
</div>';

echo $template['content'] ?? $default_content;
?>
                        </textarea>
                    </div>
                </div>

                <!-- 버튼 그룹 -->
                <div class="button-group">
                    <button type="button" class="btn btn-info" onclick="previewEmail()">
                        <i class="fas fa-eye"></i> 미리보기
                    </button>
                    <button type="button" class="btn btn-warning" onclick="sendTestEmail()">
                        <i class="fas fa-paper-plane"></i> 테스트 발송
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> 저장하기
                    </button>
                </div>
            </form>

            <!-- 미리보기 섹션 -->
            <div id="preview-section" class="preview-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5><i class="fas fa-eye"></i> 이메일 미리보기</h5>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="closePreview()">
                        <i class="fas fa-times"></i> 닫기
                    </button>
                </div>
                <div class="preview-content">
                    <h4 id="preview-subject"></h4>
                    <hr>
                    <div id="preview-body"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 테스트 이메일 모달 -->
<div class="modal fade" id="testEmailModal" tabindex="-1">
    <div class="modal-dialog">
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
    $(document).ready(function() {
        // Summernote 초기화
        $('.summernote').summernote({
            height: 400,
            placeholder: '이메일 내용을 입력하세요...',
            tabsize: 2,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'strikethrough']],
                ['fontname', ['fontname']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'hr']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ],
            fontNames: ['Arial', 'Arial Black', 'Noto Sans KR', 'Malgun Gothic', '맑은 고딕', '돋움', '굴림'],
            fontNamesIgnoreCheck: ['Noto Sans KR']
        });

        // 폼 제출 처리
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
    });

    // 변수 삽입 함수
    function insertVariable(variable) {
        $('.summernote').summernote('editor.insertText', variable);
    }

    // 미리보기 함수
    function previewEmail() {
        const subject = document.getElementById('email-subject').value;
        const content = $('.summernote').summernote('code');

        // 샘플 데이터로 변수 치환
        const sampleData = {
            '{{contact_person}}': '홍길동',
            '{{company_name}}': '중부제해예방관리원',
            '{{supplier_name}}': '(주)건설안전',
            '{{project_name}}': '서울시 도시재생 프로젝트',
            '{{due_date}}': '2025년 8월 15일',
            '{{upload_link}}': '<a href="#" style="color: #007bff;">https://example.com/upload/abc123</a>',
            '{{document_list}}': `<ul>
                <li>안전관리계획서 (필수)</li>
                <li>유해위험방지계획서 (필수)</li>
                <li>사업자등록증 (필수)</li>
                <li>건설업면허증 (선택)</li>
            </ul>`,
            '{{required_documents}}': `<ul>
                <li>안전관리계획서</li>
                <li>유해위험방지계획서</li>
                <li>사업자등록증</li>
            </ul>`,
            '{{optional_documents}}': `<ul>
                <li>건설업면허증</li>
            </ul>`,
            '{{additional_notes}}': '서류는 PDF 형식으로 제출해주시기 바랍니다.'
        };

        let previewSubject = subject;
        let previewContent = content;

        // 변수 치환
        for (const [key, value] of Object.entries(sampleData)) {
            previewSubject = previewSubject.replace(new RegExp(key, 'g'), value);
            previewContent = previewContent.replace(new RegExp(key, 'g'), value);
        }

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
        const content = $('.summernote').summernote('code');

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
</script>
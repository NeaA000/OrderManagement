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

    .variable-item.clicked {
        animation: clickEffect 0.3s ease-out;
    }

    @keyframes clickEffect {
        0% { transform: scale(1); }
        50% { transform: scale(0.95); background: #1976D2; color: white; }
        100% { transform: scale(1); }
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

    /* 이메일 버튼 스타일 */
    .email-button {
        display: inline-block;
        padding: 12px 30px;
        background-color: #007bff;
        color: white !important;
        text-decoration: none !important;
        border-radius: 5px;
        font-weight: 500;
        text-align: center;
        transition: background-color 0.3s ease;
    }

    .email-button:hover {
        background-color: #0056b3;
        color: white !important;
        text-decoration: none !important;
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
                            <div class="variable-item" data-variable="{{contact_person}}">
                                <span class="variable-code">{{contact_person}}</span>
                                <span class="variable-desc">담당자명</span>
                            </div>
                            <div class="variable-item" data-variable="{{company_name}}">
                                <span class="variable-code">{{company_name}}</span>
                                <span class="variable-desc">회사명</span>
                            </div>
                            <div class="variable-item" data-variable="{{supplier_name}}">
                                <span class="variable-code">{{supplier_name}}</span>
                                <span class="variable-desc">의뢰처명</span>
                            </div>
                            <div class="variable-item" data-variable="{{project_name}}">
                                <span class="variable-code">{{project_name}}</span>
                                <span class="variable-desc">프로젝트명</span>
                            </div>
                            <div class="variable-item" data-variable="{{due_date}}">
                                <span class="variable-code">{{due_date}}</span>
                                <span class="variable-desc">제출 기한</span>
                            </div>
                            <div class="variable-item" data-variable="{{upload_link}}">
                                <span class="variable-code">{{upload_link}}</span>
                                <span class="variable-desc">업로드 링크</span>
                            </div>
                            <div class="variable-item" data-variable="{{upload_button}}">
                                <span class="variable-code">{{upload_button}}</span>
                                <span class="variable-desc">업로드 버튼 (upload_link와 동일)</span>
                            </div>
                            <div class="variable-item" data-variable="{{document_list}}">
                                <span class="variable-code">{{document_list}}</span>
                                <span class="variable-desc">요청 서류 목록</span>
                            </div>
                            <div class="variable-item" data-variable="{{required_documents}}">
                                <span class="variable-code">{{required_documents}}</span>
                                <span class="variable-desc">필수 서류</span>
                            </div>
                            <div class="variable-item" data-variable="{{optional_documents}}">
                                <span class="variable-code">{{optional_documents}}</span>
                                <span class="variable-desc">선택 서류</span>
                            </div>
                            <div class="variable-item" data-variable="{{additional_notes}}">
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
// 이메일 호환성이 높은 기본 템플릿
$default_content = '<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>서류 제출 요청</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin: 0; padding: 0; font-family: Arial, \'Malgun Gothic\', \'맑은 고딕\', sans-serif; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f4f4f4">
        <tr>
            <td align="center">
                <!--[if mso]><table width="600" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
                <table cellpadding="0" cellspacing="0" border="0" style="width: 100%; max-width: 600px;" bgcolor="#ffffff">
                    <!-- 헤더 -->
                    <tr>
                        <td bgcolor="#f8f9fa">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="padding: 30px 40px; border-bottom: 1px solid #e0e0e0;">
                                        <h2 style="margin: 0; color: #333333; font-size: 24px; font-weight: bold;">서류 제출 요청</h2>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- 인사말 -->
                    <tr>
                        <td>
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="padding: 30px 40px;">
                                        <p style="margin: 0 0 15px 0; color: #333333; font-size: 16px; line-height: 24px;">
                                            안녕하세요, {{contact_person}}님
                                        </p>
                                        <p style="margin: 0 0 25px 0; color: #333333; font-size: 16px; line-height: 24px;">
                                            {{company_name}}에서 {{project_name}} 프로젝트와 관련하여 서류 제출을 요청드립니다.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- 프로젝트 정보 -->
                    <tr>
                        <td>
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="padding: 0 40px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f8f9fa" style="border: 1px solid #e0e0e0;">
                                            <tr>
                                                <td style="padding: 20px;">
                                                    <h3 style="margin: 0 0 15px 0; color: #333333; font-size: 18px; font-weight: bold;">프로젝트 정보</h3>
                                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                        <tr>
                                                            <td width="30%" style="padding: 5px 0; color: #666666; font-size: 14px; font-weight: bold;">프로젝트명:</td>
                                                            <td style="padding: 5px 0; color: #333333; font-size: 14px;">{{project_name}}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding: 5px 0; color: #666666; font-size: 14px; font-weight: bold;">제출 기한:</td>
                                                            <td style="padding: 5px 0; color: #dc3545; font-size: 14px; font-weight: bold;">{{due_date}}</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- 서류 목록 -->
                    <tr>
                        <td>
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="padding: 30px 40px;">
                                        <h3 style="margin: 0 0 15px 0; color: #333333; font-size: 18px; font-weight: bold;">필수 제출 서류</h3>
                                        {{required_documents}}
                                        
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr><td style="padding: 25px 0 0 0;"></td></tr>
                                        </table>
                                        
                                        <h3 style="margin: 0 0 15px 0; color: #333333; font-size: 18px; font-weight: bold;">선택 제출 서류</h3>
                                        {{optional_documents}}
                                        
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr><td style="padding: 25px 0 0 0;"></td></tr>
                                        </table>
                                        
                                        <h3 style="margin: 0 0 15px 0; color: #333333; font-size: 18px; font-weight: bold;">추가 요청사항</h3>
                                        <p style="margin: 0; color: #333333; font-size: 14px; line-height: 22px;">{{additional_notes}}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- 업로드 버튼 -->
                    <tr>
                        <td>
                            {{upload_link}}
                        </td>
                    </tr>
                    
                    <!-- 푸터 -->
                    <tr>
                        <td bgcolor="#f8f9fa">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="padding: 30px 40px; border-top: 1px solid #e0e0e0;">
                                        <p style="margin: 0 0 10px 0; color: #666666; font-size: 14px; line-height: 20px;">
                                            이 링크는 보안을 위해 제출 기한까지만 유효합니다.
                                        </p>
                                        <p style="margin: 0; color: #666666; font-size: 14px; line-height: 20px;">
                                            문의사항이 있으시면 회신 부탁드립니다.
                                        </p>
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr><td style="padding: 15px 0 0 0;"></td></tr>
                                        </table>
                                        <p style="margin: 0; color: #333333; font-size: 14px;">
                                            감사합니다.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <!--[if mso]></td></tr></table><![endif]-->
            </td>
        </tr>
    </table>
</body>
</html>';

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
    // 전역 변수로 Summernote 인스턴스와 Range 저장
    var savedRange = null;
    var $summerNote = null;

    $(document).ready(function() {
        // Summernote 초기화
        $summerNote = $('.summernote');
        $summerNote.summernote({
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
            fontNamesIgnoreCheck: ['Noto Sans KR'],
            callbacks: {
                onBlur: function() {
                    // 포커스를 잃을 때 현재 Range 저장
                    savedRange = $summerNote.summernote('createRange');
                }
            }
        });

        // 변수 아이템에 마우스 오버 시 Range 저장 (클릭 전에 저장)
        $(document).on('mouseenter', '.variable-item', function() {
            // 에디터가 포커스되어 있을 때만 Range 저장
            if ($('.note-editable').is(':focus')) {
                savedRange = $summerNote.summernote('createRange');
            }
        });

        // 변수 클릭 이벤트
        $(document).on('click', '.variable-item', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const variable = $(this).data('variable');

            // 포커스된 요소 확인
            const $focused = $(':focus');

            // 제목 필드가 포커스된 경우
            if ($focused.attr('id') === 'email-subject') {
                const input = document.getElementById('email-subject');
                const start = input.selectionStart;
                const end = input.selectionEnd;
                const text = input.value;

                input.value = text.substring(0, start) + variable + text.substring(end);
                input.selectionStart = input.selectionEnd = start + variable.length;
                input.focus();
            }
            // Summernote에 삽입
            else {
                // 에디터에 포커스 주기
                $summerNote.summernote('focus');

                // 저장된 Range가 있으면 복원
                if (savedRange) {
                    savedRange.select();
                    savedRange.insertNode(document.createTextNode(variable));

                    // 커서를 삽입한 텍스트 뒤로 이동
                    const newRange = $summerNote.summernote('createRange');
                    newRange.collapse(false);
                    newRange.select();
                } else {
                    // Range가 없으면 현재 위치에 삽입
                    $summerNote.summernote('insertText', variable);
                }
            }

            // 클릭 효과
            $(this).addClass('clicked');
            setTimeout(() => {
                $(this).removeClass('clicked');
            }, 300);
        });

        // 폼 제출 처리
        $('#email-template-form').submit(function(e) {
            e.preventDefault();

            // Summernote 내용을 명시적으로 textarea에 업데이트
            var content = $('.summernote').summernote('code');
            $('#email-content').val(content);  // textarea 값 업데이트

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
                        if(resp.template_id) {
                            $('input[name="template_id"]').val(resp.template_id);
                        }
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

    // 미리보기 함수
    function previewEmail() {
        const subject = document.getElementById('email-subject').value;
        const content = $('.summernote').summernote('code');
        
        console.log('Original content:', content); // 디버깅용
        console.log('Content length:', content.length);

        // 샘플 데이터로 변수 치환
        const sampleData = {
            '{{contact_person}}': '홍길동',
            '{{company_name}}': '중부제해예방관리원',
            '{{supplier_name}}': '(주)건설안전',
            '{{project_name}}': '서울시 도시재생 프로젝트',
            '{{due_date}}': '2025년 8월 15일',
            '{{upload_link}}': `<table cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                    <td align="center" style="padding: 30px 0;">
                        <!--[if mso]>
                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" 
                            href="#" 
                            style="height:40px;v-text-anchor:middle;width:200px;" 
                            arcsize="10%" stroke="f" fillcolor="#007bff">
                            <w:anchorlock/>
                            <center>
                        <![endif]-->
                        <a href="#" 
                           style="background-color:#007bff;border-radius:4px;color:#ffffff;display:inline-block;font-family:Arial,sans-serif;font-size:16px;font-weight:bold;line-height:40px;text-align:center;text-decoration:none;width:200px;-webkit-text-size-adjust:none;">
                            서류 업로드하기
                        </a>
                        <!--[if mso]>
                            </center>
                        </v:roundrect>
                        <![endif]-->
                    </td>
                </tr>
            </table>`,
            '{{upload_button}}': '', // upload_link와 동일하게 처리
            '{{document_list}}': `<table cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr><td style="padding: 5px 0; color: #333333; font-size: 14px;">• 안전관리계획서 <span style="color: #dc3545;">(필수)</span></td></tr>
                <tr><td style="padding: 5px 0; color: #333333; font-size: 14px;">• 유해위험방지계획서 <span style="color: #dc3545;">(필수)</span></td></tr>
                <tr><td style="padding: 5px 0; color: #333333; font-size: 14px;">• 사업자등록증 <span style="color: #dc3545;">(필수)</span></td></tr>
                <tr><td style="padding: 5px 0; color: #333333; font-size: 14px;">• 건설업면허증 <span style="color: #6c757d;">(선택)</span></td></tr>
            </table>`,
            '{{required_documents}}': `<table cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr><td style="padding: 5px 0; color: #333333; font-size: 14px;">• 안전관리계획서</td></tr>
                <tr><td style="padding: 5px 0; color: #333333; font-size: 14px;">• 유해위험방지계획서</td></tr>
                <tr><td style="padding: 5px 0; color: #333333; font-size: 14px;">• 사업자등록증</td></tr>
            </table>`,
            '{{optional_documents}}': `<table cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr><td style="padding: 5px 0; color: #333333; font-size: 14px;">• 건설업면허증</td></tr>
            </table>`,
            '{{additional_notes}}': '서류는 PDF 형식으로 제출해주시기 바랍니다.'
        };

        let previewSubject = subject;
        let previewContent = content;

        // 변수 치환
        // 긴 변수부터 치환하도록 정렬
        const sortedData = Object.entries(sampleData).sort((a, b) => b[0].length - a[0].length);
        
        for (const [key, value] of sortedData) {
            // 모든 정규식 특수문자를 이스케이프
            const escapedKey = key.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const regex = new RegExp(escapedKey, 'g');
            previewSubject = previewSubject.replace(regex, value);
            previewContent = previewContent.replace(regex, value);
        }
        
        console.log('After replacement:', previewContent); // 디버깅용

        // HTML 구조가 없으면 추가
        if (previewContent.indexOf('<!DOCTYPE') === -1 && previewContent.indexOf('<body') === -1) {
            previewContent = `<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin: 0; padding: 0; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">
    ${previewContent}
</body>
</html>`;
        }

        // 미리보기 표시
        $('#preview-subject').text(previewSubject);
        $('#preview-body').html(previewContent);
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
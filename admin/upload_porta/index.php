<?php
// admin/upload_porta/index.php
require_once('../initialize.php');

// 토큰 검증
if(!isset($_GET['token']) || empty($_GET['token'])) {
    die('잘못된 접근입니다.');
}

$token = $conn->real_escape_string($_GET['token']);

// 요청 정보 조회
$qry = $conn->query("
    SELECT dr.*, p.name as project_name, s.name as supplier_name, 
           s.email as supplier_email, dr.created_by
    FROM `document_requests` dr 
    LEFT JOIN `project_list` p ON dr.project_id = p.id 
    LEFT JOIN `supplier_list` s ON dr.supplier_id = s.id 
    WHERE dr.upload_token = '{$token}' AND dr.status = 1
");

if($qry->num_rows <= 0) {
    die('유효하지 않은 링크이거나 만료된 링크입니다.');
}

$request = $qry->fetch_assoc();

// 요청된 문서 목록 조회
$documents = $conn->query("
    SELECT rd.*, dc.name as doc_name, dc.description, dc.is_webform, dc.form_template 
    FROM `request_documents` rd 
    LEFT JOIN `document_categories` dc ON rd.document_id = dc.id 
    WHERE rd.request_id = '{$request['id']}' 
    ORDER BY dc.`order_by` ASC
");

// 제출 완료 여부 확인
$all_submitted = true;
while($doc = $documents->fetch_array()) {
    if($doc['status'] == 0) {
        $all_submitted = false;
        break;
    }
}
$documents->data_seek(0); // 결과셋 리셋
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $_settings->info('name') ?> - 서류 업로드</title>

    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/dropzone/min/dropzone.min.css">
    <link rel="stylesheet" href="<?php echo base_url ?>dist/css/adminlte.min.css">
    <link rel="stylesheet" href="<?php echo base_url ?>assets/css/custom.css">

    <style>
        body {
            background-color: #f4f6f9;
        }
        .upload-container {
            max-width: 1200px;
            margin: 50px auto;
        }
        .company-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        .document-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .document-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-completed {
            background-color: #d1fae5;
            color: #065f46;
        }
        .dropzone-container {
            border: 2px dashed #ddd;
            border-radius: 10px;
            background: #fafafa;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .dropzone-container:hover {
            border-color: #667eea;
            background: #f7f7ff;
        }
        .dz-message {
            font-size: 1.1rem;
            color: #666;
            margin: 0;
        }
        .dz-message i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 10px;
            display: block;
        }
    </style>
</head>
<body>
<div class="upload-container">
    <!-- 회사 헤더 -->
    <div class="company-header">
        <h2><?php echo $_settings->info('name') ?></h2>
        <p class="mb-0">서류 제출 포털</p>
    </div>

    <!-- 프로젝트 정보 -->
    <div class="card mb-4">
        <div class="card-body">
            <h4 class="card-title"><?php echo $request['project_name'] ?></h4>
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1"><strong>협력업체:</strong> <?php echo $request['supplier_name'] ?></p>
                    <p class="mb-1"><strong>이메일:</strong> <?php echo $request['supplier_email'] ?></p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>요청일:</strong> <?php echo date('Y-m-d', strtotime($request['date_created'])) ?></p>
                    <p class="mb-1"><strong>마감일:</strong>
                        <span class="text-danger font-weight-bold">
                                <?php echo date('Y-m-d', strtotime($request['due_date'])) ?>
                            </span>
                    </p>
                </div>
            </div>
            <?php if(!empty($request['remarks'])): ?>
                <div class="mt-3">
                    <strong>요청 사항:</strong>
                    <div class="alert alert-info mt-2 mb-0">
                        <?php echo nl2br($request['remarks']) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 서류 목록 -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-file-alt"></i> 제출 서류 목록
                <?php if($all_submitted): ?>
                    <span class="badge badge-success float-right">제출 완료</span>
                <?php else: ?>
                    <span class="badge badge-warning float-right">제출 진행중</span>
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body">
            <?php while($doc = $documents->fetch_assoc()): ?>
                <div class="document-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="mb-1">
                                <?php echo $doc['doc_name'] ?>
                                <?php if($doc['status'] == 1): ?>
                                    <span class="status-badge status-completed ml-2">
                                        <i class="fas fa-check-circle"></i> 제출완료
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-pending ml-2">
                                        <i class="fas fa-clock"></i> 미제출
                                    </span>
                                <?php endif; ?>
                            </h6>
                            <?php if(!empty($doc['description'])): ?>
                                <small class="text-muted"><?php echo $doc['description'] ?></small>
                            <?php endif; ?>
                            <?php if($doc['status'] == 1): ?>
                                <div class="mt-2">
                                    <small class="text-success">
                                        <i class="fas fa-file"></i> <?php echo $doc['file_name'] ?>
                                        (<?php echo date('Y-m-d H:i', strtotime($doc['uploaded_at'])) ?>)
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4 text-right">
                            <?php if($doc['status'] == 0): ?>
                                <?php if($doc['is_webform'] == 1): ?>
                                    <button type="button" class="btn btn-primary webform-btn"
                                            data-doc-id="<?php echo $doc['id'] ?>"
                                            data-template="<?php echo htmlspecialchars($doc['form_template']) ?>">
                                        <i class="fas fa-edit"></i> 웹 양식 작성
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-primary upload-btn"
                                            data-doc-id="<?php echo $doc['id'] ?>">
                                        <i class="fas fa-upload"></i> 파일 업로드
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-info view-file"
                                        data-doc-id="<?php echo $doc['id'] ?>">
                                    <i class="fas fa-eye"></i> 보기
                                </button>
                                <button type="button" class="btn btn-sm btn-danger delete-file"
                                        data-doc-id="<?php echo $doc['id'] ?>">
                                    <i class="fas fa-trash"></i> 삭제
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- 안내 메시지 -->
    <div class="alert alert-info mt-4">
        <h5 class="alert-heading">
            <i class="fas fa-info-circle"></i> 안내사항
        </h5>
        <?php if($all_submitted): ?>
            <h4>✅ 모든 서류가 제출되었습니다.</h4>
            <p>제출해 주셔서 감사합니다. 검토 후 연락드리겠습니다.</p>
        <?php else: ?>
            <h5 class="mb-3">서류 제출을 완료하시려면 모든 필수 서류를 업로드해 주세요.</h5>
            <div class="text-muted">
                <i class="fas fa-info-circle"></i>
                파일 형식: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, ZIP, HWP, HWPX (최대 10MB)
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 파일 업로드 모달 -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">파일 업로드</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="documentDropzone" class="dropzone-container">
                    <div class="dz-message">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>파일을 여기에 드래그하거나 클릭하여 업로드하세요</p>
                        <small class="text-muted">최대 10MB, 허용 형식: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, ZIP, HWP, HWPX</small>
                    </div>
                </div>
                <input type="hidden" id="upload_request_id" value="<?php echo $request['id'] ?>">
                <input type="hidden" id="upload_document_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="<?php echo base_url ?>plugins/jquery/jquery.min.js"></script>
<script src="<?php echo base_url ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo base_url ?>plugins/dropzone/min/dropzone.min.js"></script>

<script>
    $(document).ready(function() {
        // Dropzone 자동 초기화 방지
        Dropzone.autoDiscover = false;

        let myDropzone = null;

        // 업로드 버튼 클릭
        $('.upload-btn').click(function() {
            const docId = $(this).data('doc-id');
            $('#upload_document_id').val(docId);

            // 기존 Dropzone 인스턴스 제거
            if(myDropzone) {
                myDropzone.destroy();
            }

            // 새 Dropzone 인스턴스 생성
            myDropzone = new Dropzone("#documentDropzone", {
                url: "upload_handler.php",
                maxFilesize: 10, // MB
                acceptedFiles: '.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip,.hwp,.hwpx',
                maxFiles: 1,
                addRemoveLinks: true,
                dictDefaultMessage: '파일을 여기에 드래그하거나 클릭하여 업로드하세요',
                dictRemoveFile: '삭제',
                dictCancelUpload: '취소',
                dictFileTooBig: '파일이 너무 큽니다. ({{filesize}}MB). 최대 크기: {{maxFilesize}}MB.',
                dictInvalidFileType: '허용되지 않은 파일 형식입니다.',

                sending: function(file, xhr, formData) {
                    formData.append("request_id", $('#upload_request_id').val());
                    formData.append("document_id", $('#upload_document_id').val());
                },

                success: function(file, response) {
                    try {
                        const res = typeof response === 'string' ? JSON.parse(response) : response;
                        if(res.status == 'success') {
                            alert('파일이 성공적으로 업로드되었습니다.');
                            $('#uploadModal').modal('hide');
                            location.reload();
                        } else {
                            alert(res.msg || '업로드 중 오류가 발생했습니다.');
                            this.removeFile(file);
                        }
                    } catch(e) {
                        console.error('Response parsing error:', e);
                        alert('서버 응답 처리 중 오류가 발생했습니다.');
                        this.removeFile(file);
                    }
                },

                error: function(file, errorMessage) {
                    console.error('Upload error:', errorMessage);
                    alert('업로드 중 오류가 발생했습니다: ' + errorMessage);
                    this.removeFile(file);
                }
            });

            $('#uploadModal').modal('show');
        });

        // 모달 닫힐 때 Dropzone 초기화
        $('#uploadModal').on('hidden.bs.modal', function() {
            if(myDropzone) {
                myDropzone.destroy();
                myDropzone = null;
            }
        });

        // 파일 보기
        $('.view-file').click(function() {
            const docId = $(this).data('doc-id');
            window.open('view_file.php?id=' + docId, '_blank');
        });

        // 파일 삭제
        $('.delete-file').click(function() {
            if(!confirm('정말로 이 파일을 삭제하시겠습니까?')) return;

            const docId = $(this).data('doc-id');
            const $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: 'delete_file.php',
                type: 'POST',
                data: { document_id: docId },
                dataType: 'json',
                success: function(res) {
                    if(res.status == 'success') {
                        alert('파일이 삭제되었습니다.');
                        location.reload();
                    } else {
                        alert(res.msg || '삭제 중 오류가 발생했습니다.');
                        $btn.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Delete error:', error);
                    alert('삭제 중 오류가 발생했습니다.');
                    $btn.prop('disabled', false);
                }
            });
        });

        // 자동 새로고침 (30초마다)
        setInterval(function() {
            // 업로드 중이 아닐 때만 새로고침
            if(!$('#uploadModal').hasClass('show')) {
                location.reload();
            }
        }, 30000);
    });
</script>
</body>
</html>
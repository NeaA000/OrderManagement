<?php
// upload_portal/index.php
require_once('../initialize.php');

// 토큰 확인
if(!isset($_GET['token']) || empty($_GET['token'])) {
    echo "<script>alert('잘못된 접근입니다.'); window.close();</script>";
    exit;
}

$token = $conn->real_escape_string($_GET['token']);

// 요청 정보 조회
$request_qry = $conn->query("
    SELECT dr.*, sl.name as supplier_name, sl.contact_person 
    FROM `document_requests` dr 
    LEFT JOIN `supplier_list` sl ON dr.supplier_id = sl.id 
    WHERE dr.upload_token = '{$token}'
");

if($request_qry->num_rows <= 0) {
    echo "<script>alert('유효하지 않은 링크입니다.'); window.close();</script>";
    exit;
}

$request = $request_qry->fetch_assoc();

// 마감일 확인
if(strtotime($request['due_date']) < strtotime(date('Y-m-d'))) {
    echo "<script>alert('제출 기한이 지났습니다.'); window.close();</script>";
    exit;
}

// 요청된 서류 목록 조회
$documents = $conn->query("
    SELECT rd.*, dc.name as category_name 
    FROM `request_documents` rd 
    LEFT JOIN `document_categories` dc ON rd.category_id = dc.id 
    WHERE rd.request_id = '{$request['id']}' 
    ORDER BY rd.is_required DESC, rd.document_name ASC
");

$_settings->info('name');
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $_settings->info('name') ?> - 서류 업로드</title>

    <!-- Google Font -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/fontawesome-free/css/all.min.css">

    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/bootstrap/css/bootstrap.min.css">

    <!-- Dropzone -->
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/dropzone/min/dropzone.min.css">

    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Noto Sans KR', sans-serif;
            background-color: #f4f6f9;
        }

        .upload-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }

        .document-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .document-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .document-card.completed {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
        }

        .document-card.required {
            border-left: 4px solid #dc3545;
        }

        .document-card.optional {
            border-left: 4px solid #ffc107;
        }

        .dropzone {
            border: 2px dashed #007bff;
            border-radius: 10px;
            background: #f8f9fa;
            min-height: 150px;
        }

        .dropzone .dz-message {
            font-weight: 400;
            font-size: 16px;
            margin: 2em 0;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25em 0.6em;
            font-size: 0.875em;
            font-weight: 500;
            border-radius: 0.25rem;
        }

        .status-completed {
            background-color: #28a745;
            color: white;
        }

        .status-pending {
            background-color: #6c757d;
            color: white;
        }

        .deadline-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .progress-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .submit-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>

<body>
<div class="upload-header">
    <div class="container">
        <h1 class="mb-3"><?php echo $_settings->info('name') ?></h1>
        <h3>서류 제출 포털</h3>
    </div>
</div>

<div class="container">
    <!-- 프로젝트 정보 -->
    <div class="card mb-4">
        <div class="card-body">
            <h4 class="card-title">프로젝트 정보</h4>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>프로젝트명:</strong> <?php echo $request['project_name'] ?></p>
                    <p><strong>업체명:</strong> <?php echo $request['supplier_name'] ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>담당자:</strong> <?php echo $request['contact_person'] ?></p>
                    <p><strong>제출기한:</strong>
                        <span class="text-danger font-weight-bold">
                                <?php echo date('Y년 m월 d일', strtotime($request['due_date'])) ?>
                            </span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- 마감일 경고 -->
    <?php
    $days_left = floor((strtotime($request['due_date']) - strtotime(date('Y-m-d'))) / 86400);
    if($days_left <= 3):
        ?>
        <div class="deadline-warning">
            <i class="fas fa-exclamation-triangle"></i>
            제출 마감까지 <strong><?php echo $days_left ?>일</strong> 남았습니다. 서둘러 제출해 주세요!
        </div>
    <?php endif; ?>

    <!-- 진행률 표시 -->
    <?php
    $total_docs = $documents->num_rows;
    $completed_docs = 0;
    $documents->data_seek(0);
    while($doc = $documents->fetch_assoc()) {
        if($doc['status'] == 1) $completed_docs++;
    }
    $progress = $total_docs > 0 ? round(($completed_docs / $total_docs) * 100) : 0;
    ?>
    <div class="progress-section">
        <h5>전체 진행률: <?php echo $completed_docs ?>/<?php echo $total_docs ?>개 완료</h5>
        <div class="progress" style="height: 25px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated"
                 role="progressbar"
                 style="width: <?php echo $progress ?>%;"
                 aria-valuenow="<?php echo $progress ?>"
                 aria-valuemin="0"
                 aria-valuemax="100">
                <?php echo $progress ?>%
            </div>
        </div>
    </div>

    <!-- 서류 목록 -->
    <h4 class="mb-3">제출할 서류 목록</h4>

    <?php
    $documents->data_seek(0);
    while($doc = $documents->fetch_assoc()):
        $card_class = $doc['status'] == 1 ? 'completed' : ($doc['is_required'] ? 'required' : 'optional');
        ?>
        <div class="document-card <?php echo $card_class ?>" data-doc-id="<?php echo $doc['id'] ?>">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-1">
                        <?php echo $doc['document_name'] ?>
                        <?php if($doc['is_required']): ?>
                            <span class="badge badge-danger">필수</span>
                        <?php else: ?>
                            <span class="badge badge-warning">선택</span>
                        <?php endif; ?>
                    </h5>
                    <small class="text-muted"><?php echo $doc['category_name'] ?></small>
                </div>
                <div class="col-md-4">
                    <?php if($doc['status'] == 1): ?>
                        <span class="status-badge status-completed">
                            <i class="fas fa-check"></i> 제출완료
                        </span>
                        <br>
                        <small class="text-muted">
                            파일명: <?php echo $doc['file_name'] ?><br>
                            제출일: <?php echo date('Y-m-d H:i', strtotime($doc['uploaded_at'])) ?>
                        </small>
                    <?php else: ?>
                        <span class="status-badge status-pending">
                            <i class="fas fa-clock"></i> 미제출
                        </span>
                    <?php endif; ?>
                </div>
                <div class="col-md-2 text-right">
                    <?php if($doc['status'] == 1): ?>
                        <button class="btn btn-sm btn-info view-file" data-doc-id="<?php echo $doc['id'] ?>">
                            <i class="fas fa-eye"></i> 보기
                        </button>
                        <button class="btn btn-sm btn-danger delete-file" data-doc-id="<?php echo $doc['id'] ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                    <?php else: ?>
                        <button class="btn btn-primary upload-btn" data-doc-id="<?php echo $doc['id'] ?>">
                            <i class="fas fa-upload"></i> 업로드
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endwhile; ?>

    <!-- 추가 요청사항 -->
    <?php if(!empty($request['additional_notes'])): ?>
        <div class="card mt-4">
            <div class="card-body">
                <h5 class="card-title">추가 요청사항</h5>
                <p class="card-text"><?php echo nl2br($request['additional_notes']) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- 제출 완료 -->
    <div class="submit-section mt-4">
        <?php if($progress == 100): ?>
            <h4 class="text-success mb-3">
                <i class="fas fa-check-circle"></i> 모든 서류가 제출되었습니다!
            </h4>
            <p>제출해 주셔서 감사합니다. 검토 후 연락드리겠습니다.</p>
        <?php else: ?>
            <h5 class="mb-3">서류 제출을 완료하시려면 모든 필수 서류를 업로드해 주세요.</h5>
            <div class="text-muted">
                <i class="fas fa-info-circle"></i>
                파일 형식: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, ZIP (최대 10MB)
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
                <form action="upload_handler.php" class="dropzone" id="documentDropzone">
                    <input type="hidden" name="request_id" value="<?php echo $request['id'] ?>">
                    <input type="hidden" name="document_id" id="upload_document_id">
                </form>
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
        // Dropzone 설정
        Dropzone.options.documentDropzone = {
            maxFilesize: 10, // MB
            acceptedFiles: '.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip',
            maxFiles: 1,
            addRemoveLinks: true,
            dictDefaultMessage: '파일을 여기에 드래그하거나 클릭하여 업로드하세요',
            dictRemoveFile: '삭제',
            dictCancelUpload: '취소',
            dictFileTooBig: '파일이 너무 큽니다. ({{filesize}}MB). 최대 크기: {{maxFilesize}}MB.',
            dictInvalidFileType: '허용되지 않은 파일 형식입니다.',

            success: function(file, response) {
                const res = JSON.parse(response);
                if(res.status == 'success') {
                    alert('파일이 성공적으로 업로드되었습니다.');
                    $('#uploadModal').modal('hide');
                    location.reload();
                } else {
                    alert(res.msg);
                    this.removeFile(file);
                }
            },
            error: function(file, errorMessage) {
                alert('업로드 중 오류가 발생했습니다: ' + errorMessage);
                this.removeFile(file);
            }
        };

        // 업로드 버튼 클릭
        $('.upload-btn').click(function() {
            const docId = $(this).data('doc-id');
            $('#upload_document_id').val(docId);
            $('#uploadModal').modal('show');
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
                        alert(res.msg);
                    }
                },
                error: function() {
                    alert('삭제 중 오류가 발생했습니다.');
                }
            });
        });

        // 자동 새로고침 (30초마다)
        setInterval(function() {
            location.reload();
        }, 30000);
    });
</script>
</body>
</html>
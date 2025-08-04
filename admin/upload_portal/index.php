<?php
// admin/upload_portal/index.php
require_once('upload_init.php');  // 전용 초기화 파일 사용

// 토큰 검증
if(!isset($_GET['token']) || empty($_GET['token'])) {
    die('<html>
    <head>
        <meta charset="utf-8">
        <title>오류</title>
        <style>
            body { font-family: "Noto Sans KR", sans-serif; background: #f5f5f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
            .error-box { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; max-width: 400px; }
            .error-icon { font-size: 48px; color: #dc3545; margin-bottom: 20px; }
            h2 { color: #333; margin-bottom: 10px; }
            p { color: #666; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <div class="error-icon">⚠️</div>
            <h2>잘못된 접근입니다</h2>
            <p>유효한 업로드 링크를 통해 접근해주세요.</p>
        </div>
    </body>
    </html>');
}

$token = $conn->real_escape_string($_GET['token']);

// 요청 정보 조회
$qry = $conn->query("
    SELECT dr.*, dr.project_name, s.name as supplier_name, 
           s.email as supplier_email, dr.created_by
    FROM `document_requests` dr 
    LEFT JOIN `supplier_list` s ON dr.supplier_id = s.id 
    WHERE dr.upload_token = '{$token}'
");

if($qry->num_rows <= 0) {
    die('<html>
    <head>
        <meta charset="utf-8">
        <title>오류</title>
        <style>
            body { font-family: "Noto Sans KR", sans-serif; background: #f5f5f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
            .error-box { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; max-width: 400px; }
            .error-icon { font-size: 48px; color: #dc3545; margin-bottom: 20px; }
            h2 { color: #333; margin-bottom: 10px; }
            p { color: #666; margin-bottom: 20px; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <div class="error-icon">❌</div>
            <h2>유효하지 않은 링크입니다</h2>
            <p>링크가 만료되었거나 잘못된 링크입니다.</p>
            <p style="font-size: 14px;">담당자에게 문의해주세요.</p>
        </div>
    </body>
    </html>');
}

$request = $qry->fetch_assoc();

// 이미 완료된 요청인지 확인
$is_completed = ($request['status'] == 2);

// 요청된 서류 목록 조회
$docs_qry = $conn->query("
    SELECT rd.*, dc.name as category_name 
    FROM `request_documents` rd 
    LEFT JOIN `document_categories` dc ON rd.category_id = dc.id 
    WHERE rd.request_id = '{$request['id']}' 
    ORDER BY rd.is_required DESC, rd.id ASC
");

// 전체 서류 수와 제출 완료 수 계산
$total_docs = $docs_qry->num_rows;
$submitted_docs = 0;
$all_submitted = true;

// 서류 목록을 배열에 저장
$documents = [];
while($row = $docs_qry->fetch_assoc()) {
    if($row['status'] == 1) {
        $submitted_docs++;
    } else {
        $all_submitted = false;
    }
    $documents[] = $row;
}

// 진행률 계산
$progress = $total_docs > 0 ? round(($submitted_docs / $total_docs) * 100) : 0;

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>서류 관리 시스템</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/fontawesome-free/css/all.min.css">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/bootstrap/css/bootstrap.min.css">

    <!-- Dropzone CSS -->
    <link rel="stylesheet" href="<?php echo base_url ?>plugins/dropzone/min/dropzone.min.css">

    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #5e72e4;
            --secondary-color: #8392ab;
            --success-color: #2dce89;
            --info-color: #11cdef;
            --warning-color: #fb6340;
            --danger-color: #f5365c;
            --light-bg: #f8f9fe;
            --card-shadow: 0 0 2rem 0 rgba(136, 152, 170, 0.15);
        }

        body {
            font-family: 'Noto Sans KR', sans-serif;
            background-color: var(--light-bg);
            color: #32325d;
            line-height: 1.6;
        }

        /* Header Section */
        .header-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, #667eea 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 15px 35px rgba(94, 114, 228, 0.3);
        }

        .header-content h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .header-content p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        /* Main Container */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        /* Info Cards */
        .info-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
            border: none;
        }

        .info-card h5 {
            color: #32325d;
            font-weight: 600;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .info-item i {
            width: 20px;
            color: var(--primary-color);
            margin-right: 0.75rem;
        }

        .info-item span {
            color: #525f7f;
        }

        /* Progress Section */
        .progress-card {
            background: white;
            border-radius: 0.5rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }

        .progress-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .progress-info h4 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #32325d;
            margin: 0;
        }

        .progress-stats {
            font-size: 0.9rem;
            color: #8898aa;
        }

        .progress {
            height: 10px;
            border-radius: 5px;
            background-color: #e9ecef;
            overflow: hidden;
        }

        .progress-bar {
            background: linear-gradient(90deg, var(--primary-color) 0%, #667eea 100%);
            transition: width 0.6s ease;
        }

        /* Documents Table */
        .documents-card {
            background: white;
            border-radius: 0.5rem;
            padding: 2rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
        }

        .documents-card h4 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #32325d;
            margin-bottom: 1.5rem;
        }

        .documents-table {
            width: 100%;
            border-collapse: collapse;
        }

        .documents-table thead th {
            background-color: #f6f9fc;
            color: #8898aa;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem;
            border: none;
        }

        .documents-table tbody td {
            padding: 1rem;
            border-top: 1px solid #e9ecef;
            color: #525f7f;
        }

        .documents-table tbody tr {
            transition: all 0.2s ease;
        }

        .documents-table tbody tr:hover {
            background-color: #f6f9fc;
            transform: translateX(5px);
        }

        .badge-required {
            background-color: #f5365c;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-optional {
            background-color: #8898aa;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-submitted {
            color: var(--success-color);
            font-weight: 500;
        }

        .status-pending {
            color: var(--warning-color);
            font-weight: 500;
        }

        .upload-btn {
            background: linear-gradient(135deg, var(--primary-color) 0%, #667eea 100%);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(94, 114, 228, 0.25);
            position: relative;
            overflow: hidden;
        }

        .upload-btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: left 0.3s ease;
        }

        .upload-btn:hover:before {
            left: 100%;
        }

        .upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(94, 114, 228, 0.35);
        }

        .upload-btn i {
            margin-right: 0.5rem;
        }

        .upload-btn.btn-danger {
            background: linear-gradient(135deg, var(--danger-color) 0%, #f5365c 100%);
            box-shadow: 0 4px 15px rgba(245, 54, 92, 0.25);
        }

        .upload-btn.btn-danger:hover {
            box-shadow: 0 6px 20px rgba(245, 54, 92, 0.35);
        }

        .upload-btn:disabled {
            background: #dee2e6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* 완료 버튼 개선 */
        .complete-section {
            text-align: center;
            padding: 2rem 0;
        }

        .complete-btn {
            background: linear-gradient(135deg, var(--success-color) 0%, #26af74 100%);
            color: white;
            border: none;
            padding: 1rem 3rem;
            border-radius: 50px;
            font-size: 1.125rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-block;
            margin: 2rem auto;
            box-shadow: 0 8px 25px rgba(45, 206, 137, 0.3);
            position: relative;
            overflow: hidden;
        }

        .complete-btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.3);
            transition: left 0.5s ease;
        }

        .complete-btn:hover:before {
            left: 100%;
        }

        .complete-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(45, 206, 137, 0.4);
        }

        /* Notice Card */
        .notice-card {
            background: linear-gradient(135deg, #f6f9fc 0%, #e9ecef 100%);
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            margin-bottom: 2rem;
        }

        .notice-card h5 {
            color: #32325d;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .notice-card ul {
            list-style: none;
            padding: 0;
            margin: 0;
            color: #8898aa;
            font-size: 0.9rem;
        }

        .notice-card ul li {
            margin-bottom: 0.5rem;
        }

        /* Success Message */
        .success-message {
            text-align: center;
            padding: 3rem;
        }

        .success-message i {
            font-size: 4rem;
            color: var(--success-color);
            margin-bottom: 1.5rem;
            display: block;
        }

        .success-message h4 {
            color: #32325d;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .success-message p {
            color: #8898aa;
            font-size: 1.1rem;
        }

        /* Modal Styling */
        .modal-dialog {
            max-width: 500px;
        }

        .modal-content {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            border-bottom: none;
            padding: 1.5rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, #667eea 100%);
            color: white;
            border-radius: 1rem 1rem 0 0;
        }

        .modal-header .modal-title {
            font-weight: 600;
            font-size: 1.125rem;
        }

        .modal-header .close {
            color: white;
            opacity: 0.8;
            text-shadow: none;
            font-size: 1.5rem;
        }

        .modal-header .close:hover {
            opacity: 1;
        }

        .modal-body {
            padding: 2rem;
        }

        /* Upload Card */
        .upload-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
        }

        .upload-card h6 {
            color: #32325d;
            font-weight: 600;
            font-size: 1rem;
        }

        /* Dropzone Area */
        .dropzone-area {
            background: linear-gradient(135deg, #f6f9fc 0%, #e9ecef 100%);
            border: 2px dashed #dee2e6;
            border-radius: 0.75rem;
            padding: 3rem 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .dropzone-area:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(94, 114, 228, 0.1) 0%, rgba(102, 126, 234, 0.1) 100%);
            transition: left 0.5s ease;
        }

        .dropzone-area:hover:before {
            left: 0;
        }

        .dropzone-area:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(94, 114, 228, 0.15);
        }

        .dz-message {
            margin: 0;
        }

        .dz-message i {
            font-size: 3rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, #667eea 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            display: block;
        }

        .dz-message p {
            color: #525f7f;
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .dz-message small {
            color: #8898aa;
            font-size: 0.8125rem;
            display: block;
        }

        /* Upload Info */
        .upload-info {
            background-color: #e7f1ff;
            border-left: 4px solid var(--info-color);
            padding: 0.75rem 1rem;
            border-radius: 0.25rem;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: #525f7f;
        }

        .upload-info i {
            color: var(--info-color);
            margin-right: 0.5rem;
        }

        /* Dropzone Progress */
        .dz-preview {
            display: none;
        }

        .dz-complete .dz-message {
            display: none;
        }

        .dropzone-area.dz-started .dz-message {
            display: none;
        }

        .dz-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
        }

        .dz-upload {
            background: linear-gradient(90deg, var(--primary-color) 0%, #667eea 100%);
            height: 100%;
            transition: width 0.3s ease;
        }

        /* Success State */
        .dz-success-mark {
            display: inline-block;
            color: var(--success-color);
            font-size: 3rem;
            margin: 2rem 0;
        }

        .upload-success-msg {
            color: var(--success-color);
            font-weight: 600;
            font-size: 1.125rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content h1 {
                font-size: 1.5rem;
            }

            .documents-table {
                font-size: 0.875rem;
            }

            .documents-table thead th,
            .documents-table tbody td {
                padding: 0.75rem 0.5rem;
            }

            .upload-btn {
                padding: 0.375rem 1rem;
                font-size: 0.8125rem;
            }
        }
    </style>
</head>
<body>
<!-- Header Section -->
<div class="header-section">
    <div class="main-container">
        <div class="header-content">
            <h1>서류 관리 시스템</h1>
            <p>서류 제출 포털</p>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="main-container">
    <div class="row">
        <!-- Project Info -->
        <div class="col-lg-4 mb-4">
            <div class="info-card">
                <h5>프로젝트 정보</h5>
                <div class="info-item">
                    <i class="fas fa-building"></i>
                    <span><?php echo htmlspecialchars($request['supplier_name']) ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-project-diagram"></i>
                    <span><?php echo htmlspecialchars($request['project_name']) ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <span><?php echo htmlspecialchars($request['supplier_email']) ?></span>
                </div>
            </div>
        </div>

        <!-- Progress -->
        <div class="col-lg-8 mb-4">
            <div class="progress-card">
                <div class="progress-info">
                    <h4>제출 진행률</h4>
                    <div class="progress-stats">
                        <span><?php echo $submitted_docs ?></span> / <span><?php echo $total_docs ?></span> 완료
                    </div>
                </div>
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: <?php echo $progress ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Documents Table -->
    <div class="documents-card">
        <h4>제출 서류 목록</h4>

        <?php if($is_completed): ?>
            <!-- 이미 제출 완료된 경우 -->
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <h4>모든 서류가 제출되었습니다</h4>
                <p>제출해 주셔서 감사합니다. 검토 후 연락드리겠습니다.</p>
            </div>
        <?php else: ?>
            <!-- 아직 제출 진행 중인 경우 -->
            <div class="table-responsive">
                <table class="documents-table">
                    <thead>
                    <tr>
                        <th>서류명</th>
                        <th style="width: 100px;">구분</th>
                        <th style="width: 120px;">상태</th>
                        <th style="width: 140px;">작업</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($documents as $doc): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($doc['document_name']) ?></strong>
                                <?php if($doc['category_name']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($doc['category_name']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($doc['is_required'] == 1): ?>
                                    <span class="badge-required">필수</span>
                                <?php else: ?>
                                    <span class="badge-optional">선택</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($doc['status'] == 1): ?>
                                    <span class="status-submitted">
                                        <i class="fas fa-check-circle"></i> 제출완료
                                    </span>
                                <?php else: ?>
                                    <span class="status-pending">
                                        <i class="fas fa-clock"></i> 미제출
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($doc['status'] == 0): ?>
                                    <button class="upload-btn" onclick="openUploadModal(<?php echo $doc['id'] ?>)">
                                        <i class="fas fa-upload"></i> 업로드
                                    </button>
                                <?php else: ?>
                                    <button class="upload-btn btn-danger" onclick="deleteFile(<?php echo $doc['id'] ?>)">
                                        <i class="fas fa-trash"></i> 삭제
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- 모든 서류가 업로드되었을 때만 제출 완료 버튼 표시 -->
            <?php if($all_submitted && !$is_completed): ?>
                <div class="complete-section">
                    <h5 class="mb-3">모든 서류가 업로드되었습니다!</h5>
                    <p class="text-muted mb-4">제출을 완료하시려면 아래 버튼을 클릭해주세요.</p>
                    <button class="complete-btn" onclick="confirmSubmission()">
                        <i class="fas fa-check-circle"></i> 제출 완료
                    </button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Notice -->
    <?php if(!$is_completed && !$all_submitted): ?>
        <div class="notice-card">
            <h5>업로드 안내사항</h5>
            <ul>
                <li>파일 형식: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, ZIP, HWP, HWPX</li>
                <li>최대 파일 크기: 10MB</li>
                <li>파일명은 한글/영문 모두 가능합니다</li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">파일 업로드</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="upload-card">
                    <h6 class="text-center mb-3" id="upload-doc-name">파일을 선택해주세요</h6>
                    <div id="documentDropzone" class="dropzone-area">
                        <div class="dz-message">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>파일을 드래그하거나 클릭하여 업로드</p>
                            <small>최대 10MB · PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, ZIP, HWP, HWPX</small>
                        </div>
                    </div>
                    <div class="upload-info">
                        <i class="fas fa-info-circle"></i>
                        <span id="upload-hint">선택한 서류에 해당하는 파일을 업로드해주세요</span>
                    </div>
                </div>
                <input type="hidden" id="upload_request_id" value="<?php echo $request['id'] ?>">
                <input type="hidden" id="upload_document_id">
            </div>
        </div>
    </div>
</div>

<!-- Confirm Submission Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">제출 확인</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>모든 서류를 제출하시겠습니까?</p>
                <p class="text-muted">제출 완료 후에는 수정이 불가능합니다.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                <button type="button" class="btn btn-success" onclick="submitCompletion()">
                    <i class="fas fa-check"></i> 제출 완료
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="<?php echo base_url ?>plugins/jquery/jquery.min.js"></script>
<script src="<?php echo base_url ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo base_url ?>plugins/dropzone/min/dropzone.min.js"></script>

<script>
    // Dropzone 자동 초기화 방지
    Dropzone.autoDiscover = false;

    let myDropzone = null;

    function openUploadModal(docId) {
        $('#upload_document_id').val(docId);

        // 선택한 문서명 가져오기
        const docRow = $('button[onclick="openUploadModal(' + docId + ')"]').closest('tr');
        const docName = docRow.find('td:first strong').text();
        const categoryName = docRow.find('td:first small').text();

        // 모달에 문서명 표시
        $('#upload-doc-name').text(docName);
        if(categoryName) {
            $('#upload-hint').text(categoryName + ' 카테고리의 ' + docName + '에 해당하는 파일을 업로드해주세요');
        } else {
            $('#upload-hint').text(docName + '에 해당하는 파일을 업로드해주세요');
        }

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
            autoProcessQueue: true,
            uploadMultiple: false,
            dictDefaultMessage: '', // 기본 메시지 제거
            dictRemoveFile: '삭제',
            dictCancelUpload: '취소',
            dictFileTooBig: '파일이 너무 큽니다. (최대 {{maxFilesize}}MB)',
            dictInvalidFileType: '이 파일 형식은 업로드할 수 없습니다.',
            init: function() {
                this.on("addedfile", function(file) {
                    // 파일 추가 시 아이콘 변경
                    $('.dropzone-area .dz-message').html('<div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div>');
                });

                this.on("uploadprogress", function(file, progress) {
                    // 업로드 진행률 표시
                    $('.dz-upload').css('width', progress + '%');
                });

                this.on("success", function(file, response) {
                    // 성공 시 표시
                    $('.dropzone-area').html('<div class="dz-success-mark"><i class="fas fa-check-circle"></i></div><p class="upload-success-msg">업로드 완료!</p>');
                });
            },
            sending: function(file, xhr, formData) {
                formData.append("request_id", $('#upload_request_id').val());
                formData.append("document_id", $('#upload_document_id').val());
            },
            success: function(file, response) {
                if(typeof response === 'string') {
                    try {
                        response = JSON.parse(response);
                    } catch(e) {
                        console.error('응답 파싱 오류:', e);
                        alert('파일 업로드 중 오류가 발생했습니다.');
                        return;
                    }
                }

                if(response.status === 'success') {
                    setTimeout(function() {
                        $('#uploadModal').modal('hide');
                        location.reload();
                    }, 1500);
                } else {
                    alert(response.msg || '파일 업로드에 실패했습니다.');
                }
            },
            error: function(file, errorMessage) {
                console.error('업로드 오류:', errorMessage);
                alert('파일 업로드 중 오류가 발생했습니다: ' + errorMessage);
            }
        });

        $('#uploadModal').modal('show');
    }

    // 모달 닫힐 때 Dropzone 초기화
    $('#uploadModal').on('hidden.bs.modal', function() {
        if(myDropzone) {
            myDropzone.destroy();
            myDropzone = null;
        }
    });

    // 제출 확인
    function confirmSubmission() {
        $('#confirmModal').modal('show');
    }

    // 최종 제출
    function submitCompletion() {
        $.ajax({
            url: 'complete_submission.php',
            method: 'POST',
            data: {
                request_id: <?php echo $request['id'] ?>,
                token: '<?php echo $token ?>'
            },
            dataType: 'json',
            beforeSend: function() {
                // 버튼 비활성화
                $('#confirmModal .btn-success').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 처리중...');
            },
            success: function(response) {
                if(response.status === 'success') {
                    $('#confirmModal').modal('hide');

                    // 성공 메시지 표시 후 페이지 새로고침
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert(response.msg || '제출 처리 중 오류가 발생했습니다.');
                    $('#confirmModal .btn-success').prop('disabled', false).html('<i class="fas fa-check"></i> 제출 완료');
                }
            },
            error: function() {
                alert('서버 통신 중 오류가 발생했습니다.');
                $('#confirmModal .btn-success').prop('disabled', false).html('<i class="fas fa-check"></i> 제출 완료');
            }
        });
    }

    // 파일 삭제 - 기존 시스템의 delete_file.php 사용
    function deleteFile(docId) {
        if(!confirm('정말로 이 파일을 삭제하시겠습니까?')) {
            return;
        }

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
                }
            },
            error: function(xhr, status, error) {
                console.error('Delete error:', error);
                alert('삭제 중 오류가 발생했습니다.');
            }
        });
    }
</script>
</body>
</html>
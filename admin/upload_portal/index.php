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

// 요청된 서류 목록 조회 - 업로드된 파일 정보도 함께 조회
$docs_qry = $conn->query("
    SELECT rd.*, dc.name as category_name,
           uf.original_name as uploaded_file_name,
           uf.file_size,
           uf.uploaded_at
    FROM `request_documents` rd 
    LEFT JOIN `document_categories` dc ON rd.category_id = dc.id 
    LEFT JOIN `uploaded_files` uf ON uf.document_id = rd.id
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
            height: 20px;
            border-radius: 5px;
            background-color: #e9ecef;
            overflow: hidden;
            position: relative;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color) 0%, #667eea 100%);
            transition: width 1s ease-out;
            position: relative;
        }

        .progress-bar::after {
            content: attr(data-progress) '%';
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .progress-bar.show-percentage::after {
            opacity: 1;
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

        /* 파일명 표시 스타일 */
        .status-submitted + small {
            display: block;
            margin-top: 0.25rem;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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

        /* Inline Upload Card */
        .inline-upload-section {
            display: none;
            background: white;
            border-radius: 0.5rem;
            margin: 1rem 0;
            padding: 0;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                max-height: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                max-height: 500px;
                transform: translateY(0);
            }
        }

        .inline-upload-section.show {
            display: block;
        }

        .upload-card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #667eea 100%);
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .upload-card-header h6 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
        }

        .upload-card-header .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.25rem;
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        .upload-card-header .close-btn:hover {
            opacity: 1;
        }

        .upload-card-body {
            padding: 2rem;
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

        /* Complete Section */
        .complete-section {
            text-align: center;
            padding: 2rem 0;
        }

        .complete-section.confirm-mode {
            background: white;
            border-radius: 0.5rem;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: var(--card-shadow);
            display: none;
            animation: slideDown 0.3s ease-out;
        }

        .complete-section.confirm-mode.show {
            display: block;
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

        .confirm-buttons {
            margin-top: 2rem;
        }

        .confirm-buttons .btn {
            margin: 0 0.5rem;
        }

        .btn-secondary {
            background: #8898aa;
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #525f7f;
            transform: translateY(-2px);
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

            /* 모바일에서 파일명 표시 조정 */
            .status-submitted + small {
                max-width: 150px;
                font-size: 0.7rem !important;
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
                <!-- 진행률 표시 확인을 위한 디버그 -->
                <!-- <?php echo "Progress: " . $progress . "%"; ?> -->
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: 0%" data-progress="<?php echo $progress ?>"></div>
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
                        <th style="width: 200px;">상태</th>
                        <th style="width: 140px;">작업</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($documents as $doc): ?>
                        <tr id="doc-row-<?php echo $doc['id'] ?>">
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
                                    <?php if($doc['uploaded_file_name']): ?>
                                        <br><small class="text-muted" style="font-size: 0.75rem;">
                                            <i class="fas fa-file"></i> <?php echo htmlspecialchars($doc['uploaded_file_name']) ?>
                                        </small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="status-pending">
                                        <i class="fas fa-clock"></i> 미제출
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($doc['status'] == 0): ?>
                                    <button class="upload-btn" onclick="toggleUploadCard(<?php echo $doc['id'] ?>)">
                                        <i class="fas fa-upload"></i> 업로드
                                    </button>
                                <?php else: ?>
                                    <button class="upload-btn btn-danger" onclick="deleteFile(<?php echo $doc['id'] ?>)">
                                        <i class="fas fa-trash"></i> 삭제
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr id="upload-row-<?php echo $doc['id'] ?>" style="display: none;">
                            <td colspan="4" style="padding: 0; border: none;">
                                <div class="inline-upload-section" id="upload-section-<?php echo $doc['id'] ?>">
                                    <div class="upload-card-header">
                                        <h6><?php echo htmlspecialchars($doc['document_name']) ?> 업로드</h6>
                                        <button class="close-btn" onclick="toggleUploadCard(<?php echo $doc['id'] ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="upload-card-body">
                                        <div id="dropzone-<?php echo $doc['id'] ?>" class="dropzone-area">
                                            <div class="dz-message">
                                                <i class="fas fa-cloud-upload-alt"></i>
                                                <p>파일을 드래그하거나 클릭하여 업로드</p>
                                                <small>최대 10MB · PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, ZIP, HWP, HWPX</small>
                                            </div>
                                        </div>
                                        <div class="upload-info">
                                            <i class="fas fa-info-circle"></i>
                                            <span>
                                                <?php if($doc['category_name']): ?>
                                                    <?php echo htmlspecialchars($doc['category_name']) ?> 카테고리의
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($doc['document_name']) ?>에 해당하는 파일을 업로드해주세요
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- 모든 서류가 업로드되었을 때만 제출 완료 버튼 표시 -->
            <?php if($all_submitted && !$is_completed): ?>
                <div class="complete-section" id="complete-section">
                    <h5 class="mb-3">모든 서류가 업로드되었습니다!</h5>
                    <p class="text-muted mb-4">제출을 완료하시려면 아래 버튼을 클릭해주세요.</p>
                    <button class="complete-btn" onclick="showConfirmSection()">
                        <i class="fas fa-check-circle"></i> 제출 완료
                    </button>
                </div>

                <!-- 제출 확인 섹션 (숨겨진 상태) -->
                <div class="complete-section confirm-mode" id="confirm-section">
                    <h5>제출 확인</h5>
                    <p>모든 서류를 제출하시겠습니까?</p>
                    <p class="text-muted">제출 완료 후에는 수정이 불가능합니다.</p>
                    <div class="confirm-buttons">
                        <button type="button" class="btn btn-secondary" onclick="hideConfirmSection()">취소</button>
                        <button type="button" class="btn complete-btn" onclick="submitCompletion()">
                            <i class="fas fa-check"></i> 제출 완료
                        </button>
                    </div>
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

<!-- Scripts -->
<script src="<?php echo base_url ?>plugins/jquery/jquery.min.js"></script>
<script src="<?php echo base_url ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo base_url ?>plugins/dropzone/min/dropzone.min.js"></script>

<script>
    // Dropzone 자동 초기화 방지
    Dropzone.autoDiscover = false;

    // 페이지 로드 시 진행률 바 애니메이션
    $(document).ready(function() {
        // 진행률 바 애니메이션
        setTimeout(function() {
            $('.progress-bar').each(function() {
                var progress = $(this).data('progress');
                $(this).css('width', progress + '%');
                
                // 진행률이 20% 이상일 때만 숫자 표시
                if(progress >= 20) {
                    $(this).addClass('show-percentage');
                }
            });
        }, 100);
    });

    // 활성화된 Dropzone 인스턴스들을 저장
    const dropzoneInstances = {};

    // 업로드 카드 토글
    function toggleUploadCard(docId) {
        const uploadRow = document.getElementById(`upload-row-${docId}`);
        const uploadSection = document.getElementById(`upload-section-${docId}`);

        // 다른 열려있는 업로드 카드들을 모두 닫기
        $('.inline-upload-section.show').each(function() {
            if(this.id !== `upload-section-${docId}`) {
                const otherId = this.id.replace('upload-section-', '');
                toggleUploadCard(otherId);
            }
        });

        if(uploadSection.classList.contains('show')) {
            // 닫기
            uploadSection.classList.remove('show');
            setTimeout(() => {
                uploadRow.style.display = 'none';
            }, 300);

            // Dropzone 인스턴스 제거
            if(dropzoneInstances[docId]) {
                dropzoneInstances[docId].destroy();
                delete dropzoneInstances[docId];
            }
        } else {
            // 열기
            uploadRow.style.display = 'table-row';
            setTimeout(() => {
                uploadSection.classList.add('show');
            }, 10);

            // Dropzone 초기화
            initializeDropzone(docId);
        }
    }

    // Dropzone 초기화
    function initializeDropzone(docId) {
        const dropzoneElement = document.getElementById(`dropzone-${docId}`);

        // 이미 초기화된 경우 제거
        if(dropzoneInstances[docId]) {
            dropzoneInstances[docId].destroy();
        }

        dropzoneInstances[docId] = new Dropzone(dropzoneElement, {
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
                    $(dropzoneElement).find('.dz-message').html('<div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div>');
                });

                this.on("uploadprogress", function(file, progress) {
                    // 업로드 진행률 표시
                    $(dropzoneElement).find('.dz-upload').css('width', progress + '%');
                });

                this.on("success", function(file, response) {
                    // 성공 시 표시
                    const fileName = file.name;
                    $(dropzoneElement).html('<div class="dz-success-mark"><i class="fas fa-check-circle"></i></div><p class="upload-success-msg">업로드 완료!</p><p class="text-muted" style="font-size: 0.875rem;"><i class="fas fa-file"></i> ' + fileName + '</p>');
                });
            },
            sending: function(file, xhr, formData) {
                formData.append("request_id", <?php echo $request['id'] ?>);
                formData.append("document_id", docId);
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
    }

    // 제출 확인 섹션 표시
    function showConfirmSection() {
        document.getElementById('complete-section').style.display = 'none';
        const confirmSection = document.getElementById('confirm-section');
        confirmSection.style.display = 'block';
        setTimeout(() => {
            confirmSection.classList.add('show');
        }, 10);
    }

    // 제출 확인 섹션 숨기기
    function hideConfirmSection() {
        const confirmSection = document.getElementById('confirm-section');
        confirmSection.classList.remove('show');
        setTimeout(() => {
            confirmSection.style.display = 'none';
            document.getElementById('complete-section').style.display = 'block';
        }, 300);
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
                $('#confirm-section .complete-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 처리중...');
            },
            success: function(response) {
                if(response.status === 'success') {
                    // 성공 메시지 표시 후 페이지 새로고침
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert(response.msg || '제출 처리 중 오류가 발생했습니다.');
                    $('#confirm-section .complete-btn').prop('disabled', false).html('<i class="fas fa-check"></i> 제출 완료');
                }
            },
            error: function() {
                alert('서버 통신 중 오류가 발생했습니다.');
                $('#confirm-section .complete-btn').prop('disabled', false).html('<i class="fas fa-check"></i> 제출 완료');
            }
        });
    }

    // 파일 삭제
    function deleteFile(docId) {
        // 파일명 가져오기
        const docRow = $('#doc-row-' + docId);
        const fileName = docRow.find('.text-muted i.fa-file').parent().text().trim();
        const confirmMsg = fileName ?
            '파일 "' + fileName + '"을(를) 삭제하시겠습니까?' :
            '정말로 이 파일을 삭제하시겠습니까?';

        if(!confirm(confirmMsg)) {
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
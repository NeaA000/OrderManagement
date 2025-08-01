<?php
// get_uploads.php - document_requests 폴더에 생성
// AdminLTE에서는 이미 config.php가 로드되어 있으므로 상위 폴더의 init.php 포함
require_once('../../config.php');

if (!isset($_GET['document_id'])) {
    echo "잘못된 요청입니다.";
    exit;
}

$document_id = $_GET['document_id'];

// 업로드된 파일 조회
$stmt = $conn->prepare("
    SELECT * FROM document_uploads 
    WHERE document_id = ? 
    ORDER BY uploaded_at DESC
");
$stmt->bind_param("i", $document_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<p class='text-center text-muted'>업로드된 파일이 없습니다.</p>";
    exit;
}

echo '<div class="list-group">';
while ($file = $result->fetch_assoc()) {
    $file_size = filesize('../uploads/' . $file['file_path']);
    $file_size_mb = round($file_size / 1024 / 1024, 2);
    ?>
    <div class="list-group-item">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h6 class="mb-1"><?php echo htmlspecialchars($file['original_name']) ?></h6>
                <small class="text-muted">
                    업로드: <?php echo date("Y-m-d H:i", strtotime($file['uploaded_at'])) ?> |
                    크기: <?php echo $file_size_mb ?> MB
                </small>
            </div>
            <div class="col-md-4 text-right">
                <a href="../uploads/<?php echo $file['file_path'] ?>"
                   class="btn btn-sm btn-primary" target="_blank">
                    <i class="fas fa-download"></i> 다운로드
                </a>
                <?php if (in_array(strtolower(pathinfo($file['file_path'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'pdf'])): ?>
                    <button type="button" class="btn btn-sm btn-info"
                            onclick="previewFile('../uploads/<?php echo $file['file_path'] ?>')">
                        <i class="fas fa-eye"></i> 미리보기
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}
echo '</div>';
?>

<script>
    function previewFile(filePath) {
        // 파일 확장자 확인
        const ext = filePath.split('.').pop().toLowerCase();

        if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
            // 이미지 파일인 경우
            window.open(filePath, '_blank', 'width=800,height=600');
        } else if (ext === 'pdf') {
            // PDF 파일인 경우
            window.open(filePath, '_blank');
        } else {
            // 기타 파일은 다운로드
            window.location.href = filePath;
        }
    }
</script>
<?php
// 데이터베이스 연결 및 설정 포함
if(!isset($conn)){
    require_once("../../initialize.php");
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

// 파라미터 확인
$id = isset($_GET['id']) ? $_GET['id'] : '';
$parent_id = isset($_GET['parent_id']) ? $_GET['parent_id'] : '';
$level = isset($_GET['level']) ? $_GET['level'] : 1;

// 수정 모드인 경우 기존 데이터 로드
if($id > 0){
    $qry = $conn->query("SELECT * FROM `document_categories` WHERE id = '{$id}'");
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k = $v;
        }
    }
    $level = $level ?? 1;
    $parent_id = $parent_id ?? '';
}

// 부모 분류 정보 (하위 분류 추가 시)
$parent_info = null;
if($parent_id > 0){
    $parent_qry = $conn->query("SELECT * FROM `document_categories` WHERE id = '{$parent_id}'");
    if($parent_qry->num_rows > 0){
        $parent_info = $parent_qry->fetch_assoc();
        $level = $parent_info['level'] + 1;
    }
}

// 레벨별 제목 설정
$level_names = [
    1 => '대분류 (체크박스)',
    2 => '중분류 (서류명)',
    3 => '소분류 (세부항목)'
];

$form_title = isset($id) && $id > 0 ?
    $level_names[$level] . ' 수정' :
    $level_names[$level] . ' 추가';
?>

<div class="container-fluid">
    <form action="" id="category-form">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
        <input type="hidden" name="parent_id" value="<?php echo $parent_id ?>">
        <input type="hidden" name="level" value="<?php echo $level ?>">

        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas <?php echo $level == 1 ? 'fa-check-square' : ($level == 2 ? 'fa-file-alt' : 'fa-file') ?>"></i>
                            <?php echo $form_title ?>
                        </h4>
                    </div>
                    <div class="card-body">

                        <!-- 부모 분류 정보 표시 -->
                        <?php if($parent_info): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>상위 분류:</strong> <?php echo htmlspecialchars($parent_info['name']) ?>
                                <span class="badge badge-primary ml-2"><?php echo $level_names[$parent_info['level']] ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <!-- 분류명 -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name" class="control-label">
                                        <?php echo $level == 1 ? '체크박스명' : ($level == 2 ? '서류명' : '세부항목명') ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="name" name="name"
                                           value="<?php echo isset($name) ? htmlspecialchars($name) : '' ?>"
                                           placeholder="<?php echo $level == 1 ? '예: 안전관리계획서' : ($level == 2 ? '예: 기본 안전관리계획서' : '예: 위험성평가 세부항목') ?>"
                                           required>
                                </div>
                            </div>

                            <!-- 표시 순서 -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="display_order" class="control-label">표시 순서</label>
                                    <input type="number" class="form-control" id="display_order" name="display_order"
                                           value="<?php echo isset($display_order) ? $display_order : 1 ?>"
                                           min="1" max="99">
                                    <small class="text-muted">작은 숫자가 먼저 표시됩니다</small>
                                </div>
                            </div>

                            <!-- 상태 -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="status" class="control-label">상태</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="1" <?php echo (!isset($status) || $status == 1) ? 'selected' : '' ?>>활성</option>
                                        <option value="0" <?php echo (isset($status) && $status == 0) ? 'selected' : '' ?>>비활성</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- 필수 여부 -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">필수 여부</label>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="is_required" name="is_required" value="1"
                                            <?php echo (isset($is_required) && $is_required == 1) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="is_required">
                                            <span class="text-danger font-weight-bold">필수</span> /
                                            <span class="text-muted">선택</span>
                                        </label>
                                    </div>
                                    <small class="text-muted">
                                        <?php if($level == 1): ?>
                                            필수 체크박스는 기본적으로 선택된 상태로 표시됩니다
                                        <?php elseif($level == 2): ?>
                                            필수 서류는 반드시 제출해야 하는 서류입니다
                                        <?php else: ?>
                                            필수 항목은 반드시 작성해야 하는 항목입니다
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>

                            <!-- 대분류인 경우 추가 설정 -->
                            <?php if($level == 1): ?>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">체크박스 스타일</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="checkbox_style" id="style_primary" value="primary"
                                                <?php echo (!isset($checkbox_style) || $checkbox_style == 'primary') ? 'checked' : '' ?>>
                                            <label class="form-check-label text-primary" for="style_primary">
                                                <i class="fas fa-check-square"></i> 기본 (파란색)
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="checkbox_style" id="style_danger" value="danger"
                                                <?php echo (isset($checkbox_style) && $checkbox_style == 'danger') ? 'checked' : '' ?>>
                                            <label class="form-check-label text-danger" for="style_danger">
                                                <i class="fas fa-check-square"></i> 중요 (빨간색)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="row">
                            <!-- 설명 -->
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="description" class="control-label">설명 (선택사항)</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"
                                              placeholder="<?php echo $level == 1 ? '이 체크박스에 대한 설명을 입력하세요' : '이 서류/항목에 대한 설명을 입력하세요' ?>"><?php echo isset($description) ? htmlspecialchars($description) : '' ?></textarea>
                                    <small class="text-muted">업로드 시 사용자에게 표시될 도움말입니다</small>
                                </div>
                            </div>
                        </div>

                        <!-- 레벨별 추가 정보 -->
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-light">
                                    <h6><i class="fas fa-info-circle"></i> 참고 정보</h6>
                                    <?php if($level == 1): ?>
                                        <p class="mb-1"><strong>대분류 (체크박스):</strong> 서류 요청 시 사용자가 선택할 수 있는 메인 카테고리입니다.</p>
                                        <p class="mb-0"><small>• 체크박스가 선택되면 하위의 모든 중분류(서류)가 요청 대상이 됩니다.</small></p>
                                    <?php elseif($level == 2): ?>
                                        <p class="mb-1"><strong>중분류 (서류명):</strong> 실제로 제출받을 서류의 이름입니다.</p>
                                        <p class="mb-0"><small>• 사용자가 파일을 업로드하거나 웹에서 직접 작성할 서류입니다.</small></p>
                                    <?php else: ?>
                                        <p class="mb-1"><strong>소분류 (세부항목):</strong> 서류 내부의 세부 항목이나 단계입니다.</p>
                                        <p class="mb-0"><small>• 웹 작성 시 개별 입력 필드나 체크 항목으로 사용됩니다.</small></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="row">
                            <div class="col-12 text-right">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    <?php echo isset($id) && $id > 0 ? '수정' : '추가' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    $(document).ready(function(){
        // 폼 제출 처리
        $('#category-form').submit(function(e){
            e.preventDefault()
            var _this = $(this)

            // 폼 유효성 검사
            var name = $('#name').val().trim()
            if(name == ''){
                alert_toast('분류명을 입력해주세요.', 'error')
                $('#name').focus()
                return false
            }

            $('.err-msg').remove();
            start_loader();

            $.ajax({
                url: _base_url_ + "classes/Master.php?f=save_category",
                data: new FormData($(this)[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
                error: err => {
                    console.log(err)
                    alert_toast("오류가 발생했습니다", 'error');
                    end_loader();
                },
                success: function(resp) {
                    if (typeof resp == 'object' && resp.status == 'success') {
                        location.reload();
                    } else if (resp.status == 'failed' && !!resp.msg) {
                        var el = $('<div>')
                        el.addClass("alert alert-danger err-msg").text(resp.msg)
                        _this.prepend(el)
                        el.show('slow')
                        $("html, body").animate({
                            scrollTop: _this.closest('.card').offset().top
                        }, 500);
                        end_loader()
                    } else {
                        alert_toast("오류가 발생했습니다", 'error');
                        end_loader();
                        console.log(resp)
                    }
                }
            })
        })

        // 필수 여부 변경 시 스타일 미리보기
        $('#is_required').change(function(){
            var label = $('label[for="is_required"]')
            if($(this).is(':checked')){
                label.html('<span class="text-danger font-weight-bold">필수</span> / <span class="text-muted">선택</span>')
            } else {
                label.html('<span class="text-muted">필수</span> / <span class="text-primary font-weight-bold">선택</span>')
            }
        })
    })
</script>
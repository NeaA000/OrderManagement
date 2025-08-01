<?php
// 데이터베이스 연결 및 설정 포함
require_once('../../config.php');

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
    1 => '메인 카테고리',
    2 => '서류 그룹',
    3 => '실제 서류'
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

        <!-- 부모 분류 정보 표시 -->
        <?php if($parent_info): ?>
            <div class="alert alert-info mb-3">
                <i class="fas fa-info-circle"></i>
                <strong>상위 분류:</strong> <?php echo htmlspecialchars($parent_info['name']) ?>
                <span class="badge badge-primary ml-2"><?php echo $level_names[$parent_info['level']] ?></span>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- 분류명 -->
            <div class="col-md-12">
                <div class="form-group">
                    <label for="name" class="control-label">
                        <?php echo $level == 1 ? '카테고리명' : ($level == 2 ? '그룹명' : '서류명') ?>
                        <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="name" name="name"
                           value="<?php echo isset($name) ? htmlspecialchars($name) : '' ?>"
                           placeholder="<?php echo $level == 1 ? '예: 안전관리계획서' : ($level == 2 ? '예: 기본 안전관리' : '예: 기본안전관리계획서.docx') ?>"
                           required>
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
                            필수 카테고리는 기본적으로 선택된 상태로 표시됩니다
                        <?php elseif($level == 2): ?>
                            필수 그룹은 반드시 포함되어야 하는 서류 그룹입니다
                        <?php else: ?>
                            필수 서류는 반드시 제출해야 하는 서류입니다
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- 설명 -->
            <div class="col-12">
                <div class="form-group">
                    <label for="description" class="control-label">설명 (선택사항)</label>
                    <textarea class="form-control" id="description" name="description" rows="3"
                              placeholder="<?php echo $level == 1 ? '이 카테고리에 대한 설명을 입력하세요' : ($level == 2 ? '이 서류 그룹에 대한 설명을 입력하세요' : '이 서류에 대한 설명을 입력하세요') ?>"><?php echo isset($description) ? htmlspecialchars($description) : '' ?></textarea>
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
                        <p class="mb-1"><strong>메인 카테고리:</strong> 서류 요청 시 사용자가 선택할 수 있는 최상위 분류입니다.</p>
                        <p class="mb-0"><small>• 카테고리가 선택되면 하위의 모든 서류 그룹이 요청 대상이 됩니다.</small></p>
                    <?php elseif($level == 2): ?>
                        <p class="mb-1"><strong>서류 그룹:</strong> 비슷한 성격의 서류들을 묶는 중간 분류입니다.</p>
                        <p class="mb-0"><small>• 관련된 실제 서류들을 체계적으로 관리하기 위한 그룹입니다.</small></p>
                    <?php else: ?>
                        <p class="mb-1"><strong>실제 서류:</strong> 사용자가 실제로 업로드하거나 작성할 구체적인 서류입니다.</p>
                        <p class="mb-0"><small>• 파일 업로드나 웹 양식으로 제출받을 최종 서류입니다.</small></p>
                    <?php endif; ?>
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

            // FormData 준비
            var formData = new FormData($(this)[0]);

            // parent_id가 빈 문자열인 경우 제거 (NULL로 처리되도록)
            if(formData.get('parent_id') === '' || formData.get('parent_id') === '0') {
                formData.delete('parent_id');
            }

            // is_required 체크박스 처리 (체크되지 않은 경우 0으로 설정)
            if(!$('#is_required').is(':checked')) {
                formData.set('is_required', '0');
            }

            // start_loader 함수가 없는 경우를 대비한 체크
            if(typeof start_loader === 'function') {
                start_loader();
            }

            $.ajax({
                url: "<?php echo base_url ?>classes/Master.php?f=save_category",
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
                error: err => {
                    console.log(err)
                    console.log("Response:", err.responseText); // 디버깅용
                    alert_toast("오류가 발생했습니다", 'error');
                    if(typeof end_loader === 'function') {
                        end_loader();
                    }
                },
                success: function(resp) {
                    if (typeof resp == 'object' && resp.status == 'success') {
                        alert_toast("저장되었습니다.", 'success');
                        setTimeout(function(){
                            $('#uni_modal').modal('hide');
                            location.reload();
                        }, 1500);
                    } else if (resp.status == 'failed' && !!resp.msg) {
                        var el = $('<div>')
                        el.addClass("alert alert-danger err-msg").text(resp.msg)
                        _this.prepend(el)
                        el.show('slow')
                        $("html, body").animate({
                            scrollTop: 0
                        }, 500);
                        if(typeof end_loader === 'function') {
                            end_loader();
                        }
                    } else {
                        alert_toast("오류가 발생했습니다", 'error');
                        if(typeof end_loader === 'function') {
                            end_loader();
                        }
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
<?php if($_settings->chk_flashdata('success')): ?>
    <script>
        alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
    </script>
<?php endif;?>

<style>
    img#cimg{
        height: 15vh;
        width: 15vh;
        object-fit: cover;
        border-radius: 100% 100%;
    }
    img#cimg2{
        height: 50vh;
        width: 100%;
        object-fit: contain;
        /* border-radius: 100% 100%; */
    }
</style>
<div class="col-lg-12">
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h5 class="card-title">시스템 정보</h5>
        </div>
        <div class="card-body">
            <form action="" id="system-frm">
                <div id="msg" class="form-group"></div>

                <!-- 기본 시스템 정보 -->
                <h5 class="text-primary">기본 정보</h5>
                <hr>
                <div class="form-group">
                    <label for="name" class="control-label">시스템명</label>
                    <input type="text" class="form-control form-control-sm" name="name" id="name" value="<?php echo $_settings->info('name') ?>">
                </div>
                <div class="form-group">
                    <label for="short_name" class="control-label">시스템 약칭</label>
                    <input type="text" class="form-control form-control-sm" name="short_name" id="short_name" value="<?php echo $_settings->info('short_name') ?>">
                </div>
                <div class="form-group">
                    <label for="company_name" class="control-label">회사명</label>
                    <input type="text" class="form-control form-control-sm" name="company_name" id="company_name" value="<?php echo $_settings->info('company_name') ?>">
                </div>
                <div class="form-group">
                    <label for="company_email" class="control-label">회사 이메일</label>
                    <input type="text" class="form-control form-control-sm" name="company_email" id="company_email" value="<?php echo $_settings->info('company_email') ?>">
                </div>
                <div class="form-group">
                    <label for="company_address" class="control-label">회사 주소</label>
                    <textarea rows="3" class="form-control form-control-sm" name="company_address" id="company_address"><?php echo $_settings->info('company_address') ?></textarea>
                </div>

                <!-- 이메일 SMTP 설정 추가 -->
                <br>
                <h5 class="text-primary">이메일 설정 (SMTP)</h5>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="smtp_host" class="control-label">SMTP 호스트</label>
                            <input type="text" class="form-control form-control-sm" name="smtp_host" id="smtp_host"
                                   value="<?php echo $_settings->info('smtp_host') ?>"
                                   placeholder="예: smtp.gmail.com">
                            <small class="text-muted">Gmail: smtp.gmail.com, Naver: smtp.naver.com</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="smtp_port" class="control-label">SMTP 포트</label>
                            <input type="number" class="form-control form-control-sm" name="smtp_port" id="smtp_port"
                                   value="<?php echo $_settings->info('smtp_port') ?: '587' ?>">
                            <small class="text-muted">일반적으로 587 (TLS) 또는 465 (SSL)</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="smtp_username" class="control-label">SMTP 사용자명</label>
                            <input type="text" class="form-control form-control-sm" name="smtp_username" id="smtp_username"
                                   value="<?php echo $_settings->info('smtp_username') ?>"
                                   placeholder="your-email@gmail.com">
                            <small class="text-muted">보통 이메일 주소와 동일</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="smtp_password" class="control-label">SMTP 비밀번호</label>
                            <input type="password" class="form-control form-control-sm" name="smtp_password" id="smtp_password"
                                   value="<?php echo $_settings->info('smtp_password') ?>">
                            <small class="text-muted">Gmail의 경우 앱 비밀번호 사용</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="smtp_secure" class="control-label">보안 방식</label>
                            <select class="form-control form-control-sm" name="smtp_secure" id="smtp_secure">
                                <option value="tls" <?php echo $_settings->info('smtp_secure') == 'tls' ? 'selected' : '' ?>>TLS</option>
                                <option value="ssl" <?php echo $_settings->info('smtp_secure') == 'ssl' ? 'selected' : '' ?>>SSL</option>
                            </select>
                            <small class="text-muted">포트 587 = TLS, 포트 465 = SSL</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="smtp_from_name" class="control-label">발신자명</label>
                            <input type="text" class="form-control form-control-sm" name="smtp_from_name" id="smtp_from_name"
                                   value="<?php echo $_settings->info('smtp_from_name') ?: $_settings->info('name') ?>"
                                   placeholder="중부재해예방관리원">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="smtp_from_email" class="control-label">발신 이메일</label>
                    <input type="email" class="form-control form-control-sm" name="smtp_from_email" id="smtp_from_email"
                           value="<?php echo $_settings->info('smtp_from_email') ?: $_settings->info('smtp_username') ?>"
                           placeholder="noreply@company.com">
                    <small class="text-muted">비워두면 SMTP 사용자명과 동일하게 설정됩니다</small>
                </div>

                <!-- 이메일 테스트 버튼 -->
                <div class="form-group">
                    <button type="button" class="btn btn-sm btn-info" id="test-email">
                        <i class="fas fa-paper-plane"></i> 테스트 이메일 발송
                    </button>
                    <small class="text-muted ml-2">설정을 저장한 후 테스트하세요</small>
                </div>

                <!-- 로고 설정 -->
                <br>
                <h5 class="text-primary">시스템 로고</h5>
                <hr>
                <div class="form-group">
                    <label for="" class="control-label">시스템 로고</label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input rounded-circle" id="customFile" name="img" onchange="displayImg(this,$(this))">
                        <label class="custom-file-label" for="customFile">파일 선택</label>
                    </div>
                </div>
                <div class="form-group d-flex justify-content-center">
                    <img src="<?php echo validate_image($_settings->info('logo')) ?>" alt="" id="cimg" class="img-fluid img-thumbnail">
                </div>
            </form>
        </div>
        <div class="card-footer">
            <div class="col-md-12">
                <div class="row">
                    <button class="btn btn-sm btn-primary" form="system-frm">저장</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function displayImg(input,_this) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#cimg').attr('src', e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function displayImg2(input,_this) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                _this.siblings('label').html(input.files[0].name)
                $('#cimg2').attr('src', e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    $(document).ready(function(){
        $('.select2').select2({
            width:'100%'
        })

        $('.summernote').summernote({
            height: 200,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'clear']],
                ['fontname', ['fontname']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ol', 'ul', 'paragraph', 'height']],
                ['table', ['table']],
                ['view', ['undo', 'redo', 'fullscreen', 'codeview', 'help']]
            ]
        })

        // 테스트 이메일 발송
        $('#test-email').click(function(){
            var test_email = prompt("테스트 이메일을 받을 주소를 입력하세요:", "");
            if(test_email) {
                $.ajax({
                    url: 'test_email.php',
                    method: 'POST',
                    data: {email: test_email},
                    dataType: 'json',
                    error: err => {
                        console.log(err)
                        alert_toast("테스트 이메일 발송 중 오류가 발생했습니다", 'error');
                    },
                    success: function(resp){
                        if(resp.status == 'success'){
                            alert_toast("테스트 이메일이 발송되었습니다. 메일함을 확인하세요.", 'success');
                        } else {
                            alert_toast(resp.msg, 'error');
                        }
                    }
                })
            }
        });
    })

    $('#system-frm').submit(function(e){
        e.preventDefault()
        start_loader()
        if($('.err-msg').length > 0)
            $('.err-msg').remove()
        $.ajax({
            url:_base_url_+'classes/SystemSettings.php?f=update_settings',
            data: new FormData($(this)[0]),
            cache: false,
            contentType: false,
            processData: false,
            method: 'POST',
            type: 'POST',
            success:function(resp){
                if(resp == 1){
                    alert_toast("시스템 정보가 성공적으로 업데이트되었습니다",'success')
                    setTimeout(function(){
                        location.reload()
                    }, 1500)
                }else{
                    $('#msg').html('<div class="alert alert-danger err-msg">오류가 발생했습니다</div>')
                }
                end_loader()
            }
        })
    })
</script>
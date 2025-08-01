<?php require_once('../config.php') ?>
<!DOCTYPE html>
<html lang="ko" class="" style="height: auto;">
<?php require_once('inc/header.php') ?>
<body class="hold-transition login-page">
<script>
    start_loader()
</script>
<style>
    body{
        background-image: url("<?php echo validate_image($_settings->info('cover')) ?>");
        background-size:cover;
        background-repeat:no-repeat;
    }
    .login-title{
        text-shadow: 1px 1px #4c1d1d
    }
</style>
<h1 class="text-center text-dark py-5 login-title"><b><?php echo $_settings->info('name') ?></b></h1>
<div class="login-box">
    <!-- /.login-logo -->
    <div class="card card-outline">
        <div class="card-header text-center">
            <a href="./" class="h1"><b>로그인</b></a>
        </div>
        <div class="card-body">
            <p class="login-box-msg">세션을 시작하려면 로그인하세요</p>

            <form id="login-frm" action="" method="post">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" name="username" placeholder="사용자명">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-user"></span>
                        </div>
                    </div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" class="form-control" name="password" placeholder="비밀번호">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-lock"></span>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-8">
                    </div>
                    <!-- /.col -->
                    <div class="col-4">
                        <button type="submit" class="btn btn-primary btn-block">로그인</button>
                    </div>
                    <!-- /.col -->
                </div>
            </form>
            <!-- /.social-auth-links -->

            <!-- <p class="mb-1">
              <a href="forgot-password.html">비밀번호를 잊으셨나요?</a>
            </p> -->

        </div>
        <!-- /.card-body -->
    </div>
    <!-- /.card -->
</div>
<!-- /.login-box -->

<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.min.js"></script>

<script>
    $(document).ready(function(){
        end_loader();
    })
</script>
</body>
</html>
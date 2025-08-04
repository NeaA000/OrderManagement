<?php
if(!class_exists('DBConnection')){
    require_once('../config.php');
    require_once('DBConnection.php');
}
class SystemSettings extends DBConnection{
    public function __construct(){
        parent::__construct();
    }
    function check_connection(){
        return($this->conn);
    }
    function load_system_info(){
        // if(!isset($_SESSION['system_info'])){
        $sql = "SELECT * FROM system_info";
        $qry = $this->conn->query($sql);
        while($row = $qry->fetch_assoc()){
            $_SESSION['system_info'][$row['meta_field']] = $row['meta_value'];
        }
        // }
    }
    function update_system_info(){
        $sql = "SELECT * FROM system_info";
        $qry = $this->conn->query($sql);
        while($row = $qry->fetch_assoc()){
            if(isset($_SESSION['system_info'][$row['meta_field']]))unset($_SESSION['system_info'][$row['meta_field']]);
            $_SESSION['system_info'][$row['meta_field']] = $row['meta_value'];
        }
        return true;
    }
    function update_settings_info(){
        $data = "";
        foreach ($_POST as $key => $value) {
            // about_us와 privacy_policy를 제외한 모든 필드 처리
            if(!in_array($key,array("about_us","privacy_policy"))){
                // Wasabi Secret Key는 암호화하여 저장
                if($key == 'wasabi_secret' && !empty($value)){
                    $value = $this->encrypt_data($value);
                }

                if(isset($_SESSION['system_info'][$key])){
                    $value = str_replace("'", "&apos;", $value);
                    $qry = $this->conn->query("UPDATE system_info set meta_value = '{$value}' where meta_field = '{$key}' ");
                }else{
                    // 새로운 필드는 INSERT (SMTP 설정 등)
                    $value = str_replace("'", "&apos;", $value);
                    $qry = $this->conn->query("INSERT into system_info set meta_value = '{$value}', meta_field = '{$key}' ");
                }
            }
        }

        if(isset($_POST['about_us'])){
            file_put_contents('../about.html',$_POST['about_us']);
        }
        if(isset($_POST['privacy_policy'])){
            file_put_contents('../privacy_policy.html',$_POST['privacy_policy']);
        }
        if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
            $fname = 'uploads/'.strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
            $move = move_uploaded_file($_FILES['img']['tmp_name'],'../'. $fname);
            if(isset($_SESSION['system_info']['logo'])){
                $qry = $this->conn->query("UPDATE system_info set meta_value = '{$fname}' where meta_field = 'logo' ");
                if(is_file('../'.$_SESSION['system_info']['logo'])) unlink('../'.$_SESSION['system_info']['logo']);
            }else{
                $qry = $this->conn->query("INSERT into system_info set meta_value = '{$fname}',meta_field = 'logo' ");
            }
        }
        if(isset($_FILES['cover']) && $_FILES['cover']['tmp_name'] != ''){
            $fname = 'uploads/'.strtotime(date('y-m-d H:i')).'_'.$_FILES['cover']['name'];
            $move = move_uploaded_file($_FILES['cover']['tmp_name'],'../'. $fname);
            if(isset($_SESSION['system_info']['cover'])){
                $qry = $this->conn->query("UPDATE system_info set meta_value = '{$fname}' where meta_field = 'cover' ");
                if(is_file('../'.$_SESSION['system_info']['cover'])) unlink('../'.$_SESSION['system_info']['cover']);
            }else{
                $qry = $this->conn->query("INSERT into system_info set meta_value = '{$fname}',meta_field = 'cover' ");
            }
        }

        $update = $this->update_system_info();
        $flash = $this->set_flashdata('success','시스템 정보가 성공적으로 업데이트되었습니다.');
        if($update && $flash){
            // var_dump($_SESSION);
            return 1; // AJAX 응답을 위해 1 반환
        }
        return 0; // 실패 시 0 반환
    }

    // 데이터 암호화
    function encrypt_data($data) {
        $key = $this->get_encryption_key();
        $cipher = "AES-256-CBC";
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext = openssl_encrypt($data, $cipher, $key, 0, $iv);
        return base64_encode($iv . $ciphertext);
    }

    // 데이터 복호화
    function decrypt_data($data) {
        if(empty($data)) return '';

        $key = $this->get_encryption_key();
        $cipher = "AES-256-CBC";
        $data = base64_decode($data);
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = substr($data, 0, $ivlen);
        $ciphertext = substr($data, $ivlen);
        return openssl_decrypt($ciphertext, $cipher, $key, 0, $iv);
    }

    // 암호화 키 가져오기
    private function get_encryption_key() {
        // DB에서 암호화 키 가져오기
        $key = $this->info('encryption_key');

        // 키가 없으면 기본 키 사용 (보안상 권장하지 않음)
        if(empty($key)) {
            $key = 'cdms-default-encryption-key-2025';
        }

        // 32자로 맞추기
        return substr(hash('sha256', $key), 0, 32);
    }

    // Wasabi 설정 가져오기 (복호화 포함)
    function get_wasabi_config() {
        $config = [
            'use_wasabi' => $this->info('use_wasabi') === 'true',
            'key' => $this->info('wasabi_key'),
            'secret' => $this->decrypt_data($this->info('wasabi_secret')),
            'bucket' => $this->info('wasabi_bucket'),
            'region' => $this->info('wasabi_region') ?: 'ap-northeast-1',
            'endpoint' => $this->info('wasabi_endpoint') ?: 'https://s3.ap-northeast-1.wasabisys.com'
        ];

        return $config;
    }

    function set_userdata($field='',$value=''){
        if(!empty($field) && !empty($value)){
            $_SESSION['userdata'][$field]= $value;
        }
    }
    function userdata($field = ''){
        if(!empty($field)){
            if(isset($_SESSION['userdata'][$field]))
                return $_SESSION['userdata'][$field];
            else
                return null;
        }else{
            return false;
        }
    }
    function set_flashdata($flash='',$value=''){
        if(!empty($flash) && !empty($value)){
            $_SESSION['flashdata'][$flash]= $value;
            return true;
        }
    }
    function chk_flashdata($flash = ''){
        if(isset($_SESSION['flashdata'][$flash])){
            return true;
        }else{
            return false;
        }
    }
    function flashdata($flash = ''){
        if(!empty($flash)){
            $_tmp = $_SESSION['flashdata'][$flash];
            unset($_SESSION['flashdata']);
            return $_tmp;
        }else{
            return false;
        }
    }
    function sess_des(){
        if(isset($_SESSION['userdata'])){
            unset($_SESSION['userdata']);
            return true;
        }
        return true;
    }
    function info($field=''){
        if(!empty($field)){
            if(isset($_SESSION['system_info'][$field]))
                return $_SESSION['system_info'][$field];
            else
                return '';  // false 대신 빈 문자열 반환 (SMTP 설정 등에 유용)
        }else{
            return false;
        }
    }
    function set_info($field='',$value=''){
        if(!empty($field) && !empty($value)){
            $_SESSION['system_info'][$field] = $value;
        }
    }
}
$_settings = new SystemSettings();
$_settings->load_system_info();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();
switch ($action) {
    case 'update_settings':
        echo $sysset->update_settings_info();
        break;
    default:
        // echo $sysset->index();
        break;
}
?>
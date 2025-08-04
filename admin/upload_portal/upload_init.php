<?php
// admin/upload_portal/upload_init.php
// 업로드 포털 전용 초기화 파일 - 로그인 체크 없음

// 오류 표시 설정
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 세션 시작 (필요시)
if(!isset($_SESSION)) {
    session_start();
}

// 기본 경로 설정
if(!defined('base_url')) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $script_name = str_replace('\\', '/', dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))));
    $script_name = ($script_name == '/') ? '' : $script_name;
    define('base_url', $protocol . '://' . $host . $script_name . '/');
}

if(!defined('base_app')) {
    define('base_app', str_replace('\\', '/', dirname(dirname(__DIR__))) . '/');
}

if(!defined('DB_SERVER')) {
    define('DB_SERVER', "localhost");
    define('DB_USERNAME', "root");
    define('DB_PASSWORD', "");
    define('DB_NAME', "purchase_order_db");
}

// DBConnection 클래스 로드
require_once(base_app . 'classes/DBConnection.php');

// DB 연결
$db = new DBConnection();
$conn = $db->conn;

// Settings 클래스 로드
require_once(base_app . 'classes/SystemSettings.php');
$_settings = new SystemSettings();
$_settings->load_system_info();

// 헬퍼 함수들
function redirect($url = '') {
    if(!empty($url)) {
        echo '<script>location.href="'.base_url.$url.'"</script>';
    }
}

function alert($msg = '') {
    if(!empty($msg)) {
        echo '<script>alert("'.$msg.'")</script>';
    }
}

function format_num($number = 0, $decimal = 2) {
    return number_format($number, $decimal);
}
?>

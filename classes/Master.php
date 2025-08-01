<?php
require_once('../config.php');
Class Master extends DBConnection {
    private $settings;
    public function __construct(){
        global $_settings;
        $this->settings = $_settings;
        parent::__construct();
    }
    public function __destruct(){
        parent::__destruct();
    }
    function capture_err(){
        if(!$this->conn->error)
            return false;
        else{
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
            return json_encode($resp);
            exit;
        }
    }
    function save_supplier(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k =>$v){
            if(!in_array($k,array('id'))){
                $v = addslashes(trim($v));
                if(!empty($data)) $data .=",";
                $data .= " `{$k}`='{$v}' ";
            }
        }
        $check = $this->conn->query("SELECT * FROM `supplier_list` where `name` = '{$name}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
        if($this->capture_err())
            return $this->capture_err();
        if($check > 0){
            $resp['status'] = 'failed';
            $resp['msg'] = "의뢰처가 이미 존재합니다.";
            return json_encode($resp);
            exit;
        }
        if(empty($id)){
            $sql = "INSERT INTO `supplier_list` set {$data} ";
            $save = $this->conn->query($sql);
        }else{
            $sql = "UPDATE `supplier_list` set {$data} where id = '{$id}' ";
            $save = $this->conn->query($sql);
        }
        if($save){
            $resp['status'] = 'success';
            if(empty($id))
                $this->settings->set_flashdata('success',"새 의뢰처가 성공적으로 저장되었습니다.");
            else
                $this->settings->set_flashdata('success',"의뢰처 정보가 성공적으로 업데이트되었습니다.");
        }else{
            $resp['status'] = 'failed';
            $resp['err'] = $this->conn->error."[{$sql}]";
        }
        return json_encode($resp);
    }
    function delete_supplier(){
        extract($_POST);
        $del = $this->conn->query("DELETE FROM `supplier_list` where id = '{$id}'");
        if($del){
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success',"의뢰처가 성공적으로 삭제되었습니다.");
        }else{
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        return json_encode($resp);

    }
    function save_item(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k =>$v){
            if(!in_array($k,array('id','description'))){
                if(!empty($data)) $data .=",";
                $data .= " `{$k}`='{$v}' ";
            }
        }
        if(isset($_POST['description'])){
            if(!empty($data)) $data .=",";
            $data .= " `description`='".addslashes(htmlentities($description))."' ";
        }
        $check = $this->conn->query("SELECT * FROM `item_list` where `name` = '{$name}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
        if($this->capture_err())
            return $this->capture_err();
        if($check > 0){
            $resp['status'] = 'failed';
            $resp['msg'] = "아이템이 이미 존재합니다.";
            return json_encode($resp);
            exit;
        }
        if(empty($id)){
            $sql = "INSERT INTO `item_list` set {$data} ";
            $save = $this->conn->query($sql);
        }else{
            $sql = "UPDATE `item_list` set {$data} where id = '{$id}' ";
            $save = $this->conn->query($sql);
        }
        if($save){
            $resp['status'] = 'success';
            if(empty($id))
                $this->settings->set_flashdata('success',"새 아이템이 성공적으로 저장되었습니다.");
            else
                $this->settings->set_flashdata('success',"아이템이 성공적으로 업데이트되었습니다.");
        }else{
            $resp['status'] = 'failed';
            $resp['err'] = $this->conn->error."[{$sql}]";
        }
        return json_encode($resp);
    }
    function delete_item(){
        extract($_POST);
        $del = $this->conn->query("DELETE FROM `item_list` where id = '{$id}'");
        if($del){
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success',"아이템이 성공적으로 삭제되었습니다.");
        }else{
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        return json_encode($resp);

    }
    function search_items(){
        extract($_POST);
        $data = array();
        $items = $this->conn->query("SELECT * FROM `item_list` where `name` LIKE '%{$q}%' and status = 1 order by `name` asc limit 10 ");
        while($row = $items->fetch_assoc()){
            $data[] = $row;
        }
        return json_encode($data);

    }
    function get_price(){
        extract($_POST);
        $get = $this->conn->query("SELECT * FROM `item_list` where id = '{$id}'");
        if($get->num_rows > 0){
            $res = $get->fetch_array();
            return $res['price'];
        }else{
            return 0;
        }

    }
    function save_po(){
        if(empty($_POST['id'])){
            $prefix = "PO";
            $code = sprintf("%'.04d",1);
            while(true){
                $check = $this->conn->query("SELECT * FROM `po_list` where po_no = '".$prefix.date('Ymd')."-".$code."' ")->num_rows;
                if($check > 0){
                    $code = sprintf("%'.04d",abs($code) + 1);
                }else{
                    break;
                }
            }
            $_POST['po_no'] = $prefix.date('Ymd')."-".$code;
        }
        extract($_POST);
        $data = "";
        foreach($_POST as $k =>$v){
            if(!in_array($k,array('id')) && !is_array($_POST[$k])){
                $v = addslashes(trim($v));
                if(!empty($data)) $data .=",";
                $data .= " `{$k}`='{$v}' ";
            }
        }
        if(empty($id)){
            $sql = "INSERT INTO `po_list` set {$data} ";
        }else{
            $sql = "UPDATE `po_list` set {$data} where id = '{$id}' ";
        }
        $save = $this->conn->query($sql);
        if($save){
            $po_id = empty($id) ? $this->conn->insert_id : $id;
            $resp['status'] = 'success';
            if(empty($id)){
                $resp['id'] = $po_id;
                $this->settings->set_flashdata('success',"새 구매주문이 성공적으로 저장되었습니다.");
            }else{
                $this->settings->set_flashdata('success',"구매주문이 성공적으로 업데이트되었습니다.");
            }
            if(isset($item_id)){
                $this->conn->query("DELETE FROM `order_items` where po_id = '{$po_id}'");
                $data = "";
                foreach($item_id as $k =>$v){
                    if(!empty($data)) $data .=", ";
                    $data .= "('{$po_id}','{$item_id[$k]}','{$qty[$k]}','{$price[$k]}','{$unit[$k]}')";
                }
                if(!empty($data)){
                    $sql2 = "INSERT INTO `order_items` (`po_id`,`item_id`,`quantity`,`price`,`unit`) VALUES {$data}";
                    $this->conn->query($sql2);
                }
            }

        }else{
            $resp['status'] = 'failed';
            $resp['err'] = $this->conn->error."[{$sql}]";
        }
        return json_encode($resp);
    }
    function delete_po(){
        extract($_POST);
        $del = $this->conn->query("DELETE FROM `po_list` where id = '{$id}'");
        $del2 = $this->conn->query("DELETE FROM `order_items` where po_id = '{$id}'");
        if($del){
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success',"구매주문이 성공적으로 삭제되었습니다.");
        }else{
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        return json_encode($resp);

    }
    function save_rent(){
        if(empty($_POST['id'])){
            $prefix = "RENT";
            $code = sprintf("%'.04d",1);
            while(true){
                $check = $this->conn->query("SELECT * FROM `rent_list` where rent_no = '".$prefix.date('Ymd')."-".$code."' ")->num_rows;
                if($check > 0){
                    $code = sprintf("%'.04d",abs($code) + 1);
                }else{
                    break;
                }
            }
            $_POST['rent_no'] = $prefix.date('Ymd')."-".$code;
        }
        extract($_POST);
        $data = "";
        foreach($_POST as $k =>$v){
            if(!in_array($k,array('id'))){
                $v = addslashes(trim($v));
                if(!empty($data)) $data .=",";
                $data .= " `{$k}`='{$v}' ";
            }
        }
        if(empty($id)){
            $sql = "INSERT INTO `rent_list` set {$data} ";
            $save = $this->conn->query($sql);
        }else{
            $sql = "UPDATE `rent_list` set {$data} where id = '{$id}' ";
            $save = $this->conn->query($sql);
        }
        if($save){
            $resp['status'] = 'success';
            if(empty($id))
                $this->settings->set_flashdata('success',"새 임대가 성공적으로 저장되었습니다.");
            else
                $this->settings->set_flashdata('success',"임대가 성공적으로 업데이트되었습니다.");
            $this->settings->conn->query("UPDATE `unit_list` set `status` = '{$status}' where id = '{$unit_id}'");
        }else{
            $resp['status'] = 'failed';
            $resp['err'] = $this->conn->error."[{$sql}]";
        }
        return json_encode($resp);
    }
    function delete_rent(){
        extract($_POST);
        $del = $this->conn->query("DELETE FROM `rent_list` where id = '{$id}'");
        if($del){
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success',"임대가 성공적으로 삭제되었습니다.");
        }else{
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        return json_encode($resp);

    }
    function delete_img(){
        extract($_POST);
        if(is_file($path)){
            if(unlink($path)){
                $resp['status'] = 'success';
            }else{
                $resp['status'] = 'failed';
                $resp['error'] = 'failed to delete '.$path;
            }
        }else{
            $resp['status'] = 'failed';
            $resp['error'] = 'Unkown '.$path.' path';
        }
        return json_encode($resp);
    }
    function renew_rent(){
        extract($_POST);
        $qry = $this->conn->query("SELECT * FROM `rent_list` where id ='{$id}'");
        $res = $qry->fetch_array();
        switch ($res['rent_type']) {
            case 1:
                $date_end = " `date_end`='".date("Y-m-d",strtotime($res['date_end'].' +1 month'))."' ";
                break;
            case 2:
                $date_end = " `date_end`='".date("Y-m-d",strtotime($res['date_end'].' +3 month'))."' ";
                break;
            case 3:
                $date_end = " `date_end`='".date("Y-m-d",strtotime($res['date_end'].' +1 year'))."' ";
                break;
            default:
                # code...
                break;
        }
        $update = $this->conn->query("UPDATE `rent_list` set {$date_end}, date_rented = date_end where id = '{$id}' ");
        if($update){
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success',"임대가 성공적으로 갱신되었습니다.");
        }else{
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        return json_encode($resp);
    }

    // 서류 분류 관리 함수들 추가
    function save_category(){
        extract($_POST);
        $data = "";
        foreach($_POST as $k =>$v){
            if(!in_array($k,array('id'))){
                $v = addslashes(trim($v));
                if(!empty($data)) $data .=",";
                $data .= " `{$k}`='{$v}' ";
            }
        }

        // 중복 체크 (같은 레벨, 같은 부모 하위에서)
        $parent_condition = !empty($parent_id) ? "parent_id = '{$parent_id}'" : "parent_id IS NULL";
        $check = $this->conn->query("SELECT * FROM `document_categories` WHERE `name` = '{$name}' AND level = '{$level}' AND {$parent_condition} ".(!empty($id) ? " AND id != {$id} " : "")." ")->num_rows;

        if($this->capture_err())
            return $this->capture_err();

        if($check > 0){
            $resp['status'] = 'failed';
            $resp['msg'] = "같은 이름의 분류가 이미 존재합니다.";
            return json_encode($resp);
            exit;
        }

        if(empty($id)){
            $sql = "INSERT INTO `document_categories` SET {$data} ";
            $save = $this->conn->query($sql);
        }else{
            $sql = "UPDATE `document_categories` SET {$data} WHERE id = '{$id}' ";
            $save = $this->conn->query($sql);
        }

        if($save){
            $resp['status'] = 'success';
            if(empty($id))
                $this->settings->set_flashdata('success',"새 분류가 성공적으로 저장되었습니다.");
            else
                $this->settings->set_flashdata('success',"분류가 성공적으로 업데이트되었습니다.");
        }else{
            $resp['status'] = 'failed';
            $resp['err'] = $this->conn->error."[{$sql}]";
        }
        return json_encode($resp);
    }

    function delete_category(){
        extract($_POST);

        // 하위 분류가 있는지 확인
        $sub_check = $this->conn->query("SELECT COUNT(*) as cnt FROM `document_categories` WHERE parent_id = '{$id}'")->fetch_array()['cnt'];

        $del = $this->conn->query("DELETE FROM `document_categories` WHERE id = '{$id}'");
        if($del){
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success',"분류가 성공적으로 삭제되었습니다." . ($sub_check > 0 ? " (하위 분류 {$sub_check}개도 함께 삭제됨)" : ""));
        }else{
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        return json_encode($resp);
    }

    function update_category_order(){
        extract($_POST);
        
        // order가 JSON 문자열로 전달되므로 디코드
        $order_array = json_decode($order, true);
        
        if(!is_array($order_array)){
            return json_encode(array('status'=>'failed','msg'=>'Invalid order data'));
        }
        
        $success = true;
        
        foreach($order_array as $item){
            $id = $this->conn->real_escape_string($item['id']);
            $display_order = $this->conn->real_escape_string($item['order']);
            
            $sql = "UPDATE `document_categories` SET `display_order` = '{$display_order}' WHERE `id` = '{$id}'";
            
            if(!$this->conn->query($sql)){
                $success = false;
                break;
            }
        }
        
        if($success){
            return json_encode(array('status'=>'success'));
        } else {
            return json_encode(array('status'=>'failed','msg'=>'Database update failed'));
        }
    }
}

$Master = new Master();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();
switch ($action) {
    case 'save_supplier':
        echo $Master->save_supplier();
        break;
    case 'delete_supplier':
        echo $Master->delete_supplier();
        break;
    case 'save_item':
        echo $Master->save_item();
        break;
    case 'delete_item':
        echo $Master->delete_item();
        break;
    case 'search_items':
        echo $Master->search_items();
        break;
    case 'save_po':
        echo $Master->save_po();
        break;
    case 'delete_po':
        echo $Master->delete_po();
        break;
    case 'get_price':
        echo $Master->get_price();
        break;
    case 'save_rent':
        echo $Master->save_rent();
        break;
    case 'delete_rent':
        echo $Master->delete_rent();
        break;
    case 'renew_rent':
        echo $Master->renew_rent();
        break;
    case 'save_category':
        echo $Master->save_category();
        break;
    case 'delete_category':
        echo $Master->delete_category();
        break;
    case 'update_category_order':
        echo $Master->update_category_order();
        break;


    default:
        // echo $sysset->index();
        break;
}
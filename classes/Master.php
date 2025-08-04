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

        // parent_id가 빈 문자열이거나 0인 경우 제거
        if(isset($parent_id) && ($parent_id === '' || $parent_id == 0)) {
            unset($_POST['parent_id']);
            unset($parent_id);
        }

        // is_required가 설정되지 않은 경우 0으로 설정
        if(!isset($_POST['is_required'])) {
            $_POST['is_required'] = '0';
        }

        $data = "";
        foreach($_POST as $k =>$v){
            if(!in_array($k,array('id'))){
                // 빈 값이 아닌 경우만 업데이트 (단, is_required는 0일 수 있으므로 예외)
                if($k == 'is_required' || !empty($v) || $v === '0') {
                    $v = addslashes(trim($v));
                    if(!empty($data)) $data .=",";

                    if($k == 'parent_id' && empty($v)) {
                        $data .= " `{$k}`=NULL ";
                    } else {
                        $data .= " `{$k}`='{$v}' ";
                    }
                }
            }
        }

        // 중복 체크는 새로 추가할 때만
        if(empty($id)){
            $parent_condition = !empty($parent_id) ? "parent_id = '{$parent_id}'" : "parent_id IS NULL";
            $check = $this->conn->query("SELECT * FROM `document_categories` WHERE `name` = '{$name}' AND level = '{$level}' AND {$parent_condition}")->num_rows;

            if($this->capture_err())
                return $this->capture_err();

            if($check > 0){
                $resp['status'] = 'failed';
                $resp['msg'] = "같은 이름의 분류가 이미 존재합니다.";
                return json_encode($resp);
                exit;
            }
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

    function delete_request(){
        extract($_POST);

        // ID 확인
        if(!isset($id) || empty($id)){
            $resp['status'] = 'failed';
            $resp['msg'] = "요청 ID가 없습니다.";
            return json_encode($resp);
        }

        // 트랜잭션 시작
        $this->conn->begin_transaction();

        try {
            // 1. 업로드된 파일들 먼저 삭제
            $file_qry = $this->conn->query("
                SELECT du.file_path 
                FROM document_uploads du 
                INNER JOIN request_documents rd ON du.document_id = rd.id 
                WHERE rd.request_id = '{$id}'
            ");

            while($file = $file_qry->fetch_assoc()){
                $file_path = '../uploads/' . $file['file_path'];
                if(file_exists($file_path)){
                    unlink($file_path);
                }
            }

            // 2. 관련 테이블 데이터 삭제 (외래키 제약으로 자동 삭제되지만 명시적으로 처리)
            // document_uploads는 request_documents가 삭제되면 자동 삭제됨

            // 3. request_documents 삭제
            $this->conn->query("DELETE FROM request_documents WHERE request_id = '{$id}'");

            // 4. document_request_details 삭제
            $this->conn->query("DELETE FROM document_request_details WHERE request_id = '{$id}'");

            // 5. document_targets 삭제
            $this->conn->query("DELETE FROM document_targets WHERE request_id = '{$id}'");

            // 6. document_cost_details 삭제
            $this->conn->query("DELETE FROM document_cost_details WHERE request_id = '{$id}'");

            // 7. document_writers 삭제
            $this->conn->query("DELETE FROM document_writers WHERE request_id = '{$id}'");

            // 8. review_credentials 삭제
            $this->conn->query("DELETE FROM review_credentials WHERE request_id = '{$id}'");

            // 9. workflow_status 삭제
            $this->conn->query("DELETE FROM workflow_status WHERE request_id = '{$id}'");

            // 10. 메인 테이블 삭제
            $delete = $this->conn->query("DELETE FROM document_requests WHERE id = '{$id}'");

            if($delete){
                $this->conn->commit();
                $resp['status'] = 'success';
                $this->settings->set_flashdata('success', "서류 요청이 성공적으로 삭제되었습니다.");
            } else {
                throw new Exception("삭제 실패");
            }

        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = "삭제 중 오류가 발생했습니다: " . $e->getMessage();
            $resp['error'] = $this->conn->error;
        }

        return json_encode($resp);
    }

    function send_request_email(){
        extract($_POST);

        if(!isset($id) || empty($id)){
            $resp['status'] = 'failed';
            $resp['msg'] = "요청 ID가 없습니다.";
            return json_encode($resp);
        }

        // 요청 정보 조회
        $qry = $this->conn->query("
            SELECT r.*, s.email, s.name as supplier_name, s.contact_person,
                   d.manager_email, d.manager_name
            FROM document_requests r
            LEFT JOIN supplier_list s ON r.supplier_id = s.id
            LEFT JOIN document_request_details d ON d.request_id = r.id
            WHERE r.id = '{$id}'
        ");

        if($qry->num_rows == 0){
            $resp['status'] = 'failed';
            $resp['msg'] = "요청을 찾을 수 없습니다.";
            return json_encode($resp);
        }

        $request = $qry->fetch_assoc();

        // 이메일 템플릿 가져오기
        $template_qry = $this->conn->query("
            SELECT * FROM email_templates 
            WHERE template_type = 'request_notification' 
            AND is_default = 1 
            LIMIT 1
        ");

        if($template_qry->num_rows == 0){
            $resp['status'] = 'failed';
            $resp['msg'] = "이메일 템플릿이 설정되지 않았습니다.";
            return json_encode($resp);
        }

        $template = $template_qry->fetch_assoc();

        // 이메일 수신자 결정
        $to_email = !empty($request['manager_email']) ? $request['manager_email'] : $request['email'];
        $to_name = !empty($request['manager_name']) ? $request['manager_name'] : $request['contact_person'];

        if(empty($to_email)){
            $resp['status'] = 'failed';
            $resp['msg'] = "수신자 이메일이 없습니다.";
            return json_encode($resp);
        }

        // 업로드 링크 생성
        $upload_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") .
            "://$_SERVER[HTTP_HOST]" . dirname(dirname($_SERVER['REQUEST_URI'])) .
            "/upload_portal/?token=" . $request['upload_token'];

        // 업로드 링크를 버튼 HTML로 생성 (인라인 스타일 완전 적용)
        $upload_button = '<div style="text-align: center; margin: 30px 0;">' .
            '<a href="'.$upload_link.'" style="display: inline-block; padding: 12px 30px; ' .
            'background-color: #007bff; color: white !important; ' .
            'text-decoration: none !important; border-radius: 5px; font-weight: 500; font-size: 16px; ' .
            'box-shadow: 0 2px 4px rgba(0,0,0,0.1);">' .
            '서류 업로드하기' .
            '</a></div>';

        // 요청된 서류 목록 조회
        $docs_qry = $this->conn->query("
            SELECT document_name, is_required 
            FROM request_documents 
            WHERE request_id = '{$id}'
            ORDER BY is_required DESC, document_name ASC");

        $required_docs = array();
        $optional_docs = array();
        $all_docs = array();

        while($doc = $docs_qry->fetch_assoc()){
            if($doc['is_required'] == 1){
                $required_docs[] = $doc['document_name'];
                $all_docs[] = $doc['document_name'] . ' (필수)';
            } else {
                $optional_docs[] = $doc['document_name'];
                $all_docs[] = $doc['document_name'] . ' (선택)';
            }
        }

        // 서류 목록을 HTML 리스트로 변환
        $required_docs_html = "";
        $optional_docs_html = "";
        $all_docs_html = "";

        if(!empty($required_docs)){
            $required_docs_html = '<ul style="margin: 10px 0; padding-left: 20px; list-style-type: disc;">';
            foreach($required_docs as $doc) {
                $required_docs_html .= '<li style="margin: 5px 0;">' . htmlspecialchars($doc) . '</li>';
            }
            $required_docs_html .= '</ul>';
        } else {
            $required_docs_html = '<span style="color: #6c757d;">없음</span>';
        }

        if(!empty($optional_docs)){
            $optional_docs_html = '<ul style="margin: 10px 0; padding-left: 20px; list-style-type: disc;">';
            foreach($optional_docs as $doc) {
                $optional_docs_html .= '<li style="margin: 5px 0;">' . htmlspecialchars($doc) . '</li>';
            }
            $optional_docs_html .= '</ul>';
        } else {
            $optional_docs_html = '<span style="color: #6c757d;">없음</span>';
        }

        if(!empty($all_docs)){
            $all_docs_html = '<ul style="margin: 10px 0; padding-left: 20px; list-style-type: disc;">';
            foreach($all_docs as $doc) {
                $all_docs_html .= '<li style="margin: 5px 0;">' . htmlspecialchars($doc) . '</li>';
            }
            $all_docs_html .= '</ul>';
        } else {
            $all_docs_html = '<span style="color: #6c757d;">서류 목록이 없습니다.</span>';
        }

        // 변수 치환 데이터
        $replacements = array(
            '{{contact_person}}' => $to_name,
            '{{company_name}}' => $this->settings->info('name'),
            '{{supplier_name}}' => $request['supplier_name'],
            '{{project_name}}' => $request['project_name'],
            '{{due_date}}' => date('Y년 m월 d일', strtotime($request['due_date'])),
            '{{upload_link}}' => $upload_button,
            '{{document_list}}' => $all_docs_html,
            '{{required_documents}}' => $required_docs_html,
            '{{optional_documents}}' => $optional_docs_html,
            '{{additional_notes}}' => !empty($request['additional_notes']) ?
                nl2br(htmlspecialchars($request['additional_notes'])) :
                '<span style="color: #6c757d;">없음</span>'
        );

        // 템플릿 내용에서 변수 치환
        $subject = $template['subject'];
        $content = $template['content'];

        foreach($replacements as $key => $value){
            $subject = str_replace($key, $value, $subject);
            $content = str_replace($key, $value, $content);
        }

        // HTML 이메일 완전한 구조로 래핑
        $full_html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style type="text/css">
        body {
            margin: 0;
            padding: 0;
            font-family: "Noto Sans KR", "Malgun Gothic", "맑은 고딕", sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        a.email-button {
            display: inline-block !important;
            padding: 12px 30px !important;
            background-color: #007bff !important;
            color: white !important;
            text-decoration: none !important;
            border-radius: 5px !important;
            font-weight: 500 !important;
        }
    </style>
</head>
<body>
    <div class="email-container">
        ' . $content . '
    </div>
</body>
</html>';

        // EmailSender 클래스 사용
        require_once('EmailSender.php');
        $emailSender = new EmailSender();

        // 이메일 발송
        $result = $emailSender->sendEmail($to_email, $to_name, $subject, $full_html);

        if($result['status'] == 'success'){
            // 발송 시간 기록
            $this->conn->query("UPDATE document_requests SET email_sent_at = NOW() WHERE id = '{$id}'");
            $resp = $result;
        } else {
            $resp = $result;
        }

        return json_encode($resp);
    }

    function save_document_request() {
        extract($_POST);
        $data = "";

        // 요청번호 생성
        $request_no = "REQ-" . date('Ymd') . "-";
        $count_qry = $this->conn->query("SELECT COUNT(*) as count FROM document_requests WHERE DATE(date_created) = CURDATE()");
        $count = $count_qry->fetch_assoc()['count'] + 1;
        $request_no .= str_pad($count, 3, '0', STR_PAD_LEFT);

        // 업로드 토큰 생성
        $upload_token = bin2hex(random_bytes(32));

        // 기본 데이터 준비
        $data .= " request_no = '{$request_no}' ";
        $data .= ", supplier_id = '{$supplier_id}' ";
        $data .= ", project_name = '{$project_name}' ";
        $data .= ", due_date = '{$due_date}' ";
        $data .= ", additional_notes = '{$additional_notes}' ";
        $data .= ", upload_token = '{$upload_token}' ";
        $data .= ", status = 0 ";
        $data .= ", created_by = '{$this->settings->userdata('id')}' ";

        // document_requests 테이블에 저장
        $save = $this->conn->query("INSERT INTO document_requests SET {$data}");

        if($save) {
            $request_id = $this->conn->insert_id;

            // 선택된 서류들을 request_documents 테이블에 저장
            if(isset($documents) && is_array($documents)) {
                foreach($documents as $doc_id) {
                    // 서류 정보 조회
                    $doc_qry = $this->conn->query("SELECT * FROM document_categories WHERE id = '{$doc_id}'");
                    $doc = $doc_qry->fetch_assoc();

                    $doc_data = "";
                    $doc_data .= " request_id = '{$request_id}' ";
                    $doc_data .= ", category_id = '{$doc_id}' ";
                    $doc_data .= ", document_name = '{$doc['name']}' ";
                    $doc_data .= ", is_required = '{$doc['is_required']}' ";
                    $doc_data .= ", status = 'pending' ";

                    $this->conn->query("INSERT INTO request_documents SET {$doc_data}");
                }
            }

            $resp['status'] = 'success';
            $resp['msg'] = '서류 요청이 성공적으로 생성되었습니다.';
            $resp['request_id'] = $request_id;
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = '서류 요청 생성에 실패했습니다.';
            $resp['error'] = $this->conn->error;
        }

        return json_encode($resp);
    }

    // 이메일 템플릿 저장
    public function save_email_template() {
        extract($_POST);
        $data = "";

        // 입력값 검증
        if(empty($subject) || empty($content)) {
            $resp['status'] = 'failed';
            $resp['msg'] = "제목과 내용을 모두 입력해주세요.";
            return json_encode($resp);
        }

        // 데이터 준비
        $subject = $this->conn->real_escape_string($subject);
        $content = $this->conn->real_escape_string($content);

        // 트랜잭션 시작
        $this->conn->begin_transaction();

        try {
            // 1. 기존의 모든 request_notification 타입 템플릿의 is_default를 0으로 변경
            $this->conn->query("UPDATE email_templates SET is_default = 0 WHERE template_type = 'request_notification'");

            if(!empty($template_id)) {
                // 기존 템플릿 업데이트
                $sql = "UPDATE email_templates SET 
                    subject = '{$subject}',
                    content = '{$content}',
                    is_default = 1,
                    date_updated = NOW()
                    WHERE id = '{$template_id}'";
            } else {
                // 새 템플릿 생성
                $sql = "INSERT INTO email_templates SET
                    template_name = '서류 요청 알림',
                    template_type = 'request_notification',
                    subject = '{$subject}',
                    content = '{$content}',
                    is_html = 1,
                    is_default = 1,
                    status = 1";
            }

            $save = $this->conn->query($sql);

            if($save) {
                // 선택적: 오래된 템플릿 삭제 (최근 5개만 유지)
                $this->conn->query("
                    DELETE FROM email_templates 
                    WHERE template_type = 'request_notification' 
                    AND is_default = 0 
                    AND id NOT IN (
                        SELECT id FROM (
                            SELECT id FROM email_templates 
                            WHERE template_type = 'request_notification' 
                            ORDER BY date_created DESC 
                            LIMIT 5
                        ) AS keep_templates
                    )
                ");

                $this->conn->commit();
                $resp['status'] = 'success';
                $resp['msg'] = "이메일 템플릿이 성공적으로 저장되었습니다.";

                // 새로 생성된 경우 ID 반환
                if(empty($template_id)) {
                    $resp['template_id'] = $this->conn->insert_id;
                }
            } else {
                throw new Exception($this->conn->error);
            }

        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = "저장 중 오류가 발생했습니다: " . $e->getMessage();
        }

        return json_encode($resp);
    }

    // 테스트 이메일 발송 - 수정된 버전
    public function send_test_email() {
        extract($_POST);

        // 입력값 검증
        if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $resp['status'] = 'failed';
            $resp['msg'] = "유효한 이메일 주소를 입력해주세요.";
            return json_encode($resp);
        }

        // EmailSender 클래스 사용
        require_once('EmailSender.php');
        $emailSender = new EmailSender();

        // 업로드 버튼 HTML 생성 (인라인 스타일 완전 적용)
        $upload_button = '<div style="text-align: center; margin: 30px 0;">' .
            '<a href="#" style="display: inline-block; padding: 12px 30px; ' .
            'background-color: #007bff; color: white !important; ' .
            'text-decoration: none !important; border-radius: 5px; font-weight: 500; font-size: 16px; ' .
            'box-shadow: 0 2px 4px rgba(0,0,0,0.1);">' .
            '서류 업로드하기</a></div>';

        // 필수 서류 HTML
        $required_docs_html = '<ul style="margin: 10px 0; padding-left: 20px; list-style-type: disc;">' .
            '<li style="margin: 5px 0;">안전관리계획서</li>' .
            '<li style="margin: 5px 0;">유해위험방지계획서</li>' .
            '<li style="margin: 5px 0;">사업자등록증</li>' .
            '</ul>';

        // 선택 서류 HTML
        $optional_docs_html = '<ul style="margin: 10px 0; padding-left: 20px; list-style-type: disc;">' .
            '<li style="margin: 5px 0;">건설업면허증</li>' .
            '</ul>';

        // 전체 서류 목록 HTML
        $all_docs_html = '<ul style="margin: 10px 0; padding-left: 20px; list-style-type: disc;">' .
            '<li style="margin: 5px 0;">안전관리계획서 (필수)</li>' .
            '<li style="margin: 5px 0;">유해위험방지계획서 (필수)</li>' .
            '<li style="margin: 5px 0;">사업자등록증 (필수)</li>' .
            '<li style="margin: 5px 0;">건설업면허증 (선택)</li>' .
            '</ul>';

        // 샘플 데이터로 변수 치환 - 모든 변수 포함
        $sampleData = [
            '{{contact_person}}' => '홍길동',
            '{{company_name}}' => $this->settings->info('name'),
            '{{supplier_name}}' => '테스트 의뢰처',
            '{{project_name}}' => '테스트 프로젝트',
            '{{due_date}}' => date('Y년 m월 d일', strtotime('+7 days')),
            '{{upload_link}}' => $upload_button,
            '{{required_documents}}' => $required_docs_html,
            '{{optional_documents}}' => $optional_docs_html,
            '{{additional_notes}}' => '서류는 PDF 형식으로 제출해주시기 바랍니다.',
            '{{document_list}}' => $all_docs_html
        ];

        // 변수 치환
        $test_subject = $subject;
        $test_content = $content;

        // 모든 변수를 치환
        foreach($sampleData as $key => $value) {
            $test_subject = str_replace($key, $value, $test_subject);
            $test_content = str_replace($key, $value, $test_content);
        }

        // 테스트 이메일임을 표시
        $test_subject = "[테스트] " . $test_subject;

        // HTML 이메일 완전한 구조로 래핑
        $full_html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style type="text/css">
        body {
            margin: 0;
            padding: 0;
            font-family: "Noto Sans KR", "Malgun Gothic", "맑은 고딕", sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        a.email-button {
            display: inline-block !important;
            padding: 12px 30px !important;
            background-color: #007bff !important;
            color: white !important;
            text-decoration: none !important;
            border-radius: 5px !important;
            font-weight: 500 !important;
        }
    </style>
</head>
<body>
    <div class="email-container">
        ' . $test_content . '
    </div>
</body>
</html>';

        // 이메일 발송
        $result = $emailSender->sendEmail($email, '담당자', $test_subject, $full_html);

        return json_encode($result);
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
    case 'delete_request':
        echo $Master->delete_request();
        break;
    case 'send_request_email':
        echo $Master->send_request_email();
        break;
    case 'save_document_request':
        echo $Master->save_document_request();
        break;
    case 'save_email_template':
        echo $Master->save_email_template();
        break;
    case 'send_test_email':
        echo $Master->send_test_email();
        break;
    default:
        // echo $sysset->index();
        break;
}
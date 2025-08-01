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

        // 이메일 수신자 결정 (담당자 이메일이 있으면 우선, 없으면 의뢰처 이메일)
        $to_email = !empty($request['manager_email']) ? $request['manager_email'] : $request['email'];
        $to_name = !empty($request['manager_name']) ? $request['manager_name'] : $request['contact_person'];

        if(empty($to_email)){
            $resp['status'] = 'failed';
            $resp['msg'] = "수신자 이메일이 없습니다.";
            return json_encode($resp);
        }

        // 업로드 링크 생성
        $upload_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") .
            "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['REQUEST_URI']) .
            "/document_requests/upload.php?token=" . $request['upload_token'];

        // 이메일 제목
        $subject = "[서류요청] " . $request['project_name'] . " - 서류 제출 요청";

        // 이메일 내용
        $message = "
        <html>
        <head>
            <style>
                body { font-family: 'Malgun Gothic', sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
                .content { background-color: #ffffff; padding: 20px; border: 1px solid #dee2e6; border-radius: 5px; }
                .button { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #dee2e6; font-size: 0.9em; color: #6c757d; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>서류 제출 요청</h2>
                </div>
                <div class='content'>
                    <p>안녕하세요, {$to_name}님</p>
                    
                    <p><strong>{$request['project_name']}</strong> 프로젝트와 관련하여 필요한 서류 제출을 요청드립니다.</p>
                    
                    <h3>요청 정보</h3>
                    <ul>
                        <li><strong>요청번호:</strong> {$request['request_no']}</li>
                        <li><strong>프로젝트명:</strong> {$request['project_name']}</li>
                        <li><strong>제출기한:</strong> " . date('Y년 m월 d일', strtotime($request['due_date'])) . "</li>
                    </ul>
                    
                    <p>아래 링크를 클릭하여 필요한 서류를 업로드해 주시기 바랍니다:</p>
                    
                    <p style='text-align: center;'>
                        <a href='{$upload_link}' class='button'>서류 업로드하기</a>
                    </p>
                    
                    <p><small>또는 다음 링크를 복사하여 브라우저에 붙여넣으세요:<br>
                    {$upload_link}</small></p>
                    
                    " . (!empty($request['additional_notes']) ? "
                    <h3>추가 요청사항</h3>
                    <p>" . nl2br(htmlspecialchars($request['additional_notes'])) . "</p>
                    " : "") . "
                    
                    <div class='footer'>
                        <p>문의사항이 있으시면 언제든 연락 주시기 바랍니다.</p>
                        <p>감사합니다.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";

        // 이메일 헤더
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: ' . $this->settings->info('company_email') . "\r\n";

        // 이메일 발송
        if(mail($to_email, $subject, $message, $headers)){
            // 발송 시간 기록
            $this->conn->query("UPDATE document_requests SET email_sent_at = NOW() WHERE id = '{$id}'");

            $resp['status'] = 'success';
            $resp['msg'] = "이메일이 성공적으로 발송되었습니다.";
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = "이메일 발송에 실패했습니다.";
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

        if(!empty($template_id)) {
            // 기존 템플릿 업데이트
            $sql = "UPDATE email_templates SET 
                    subject = '{$subject}',
                    content = '{$content}',
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
            $resp['status'] = 'success';
            $resp['msg'] = "이메일 템플릿이 성공적으로 저장되었습니다.";
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = "저장 중 오류가 발생했습니다: " . $this->conn->error;
        }

        return json_encode($resp);
    }

    // 테스트 이메일 발송
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

        // 샘플 데이터로 변수 치환
        $sampleData = [
            '{{company_name}}' => $this->settings->info('name'),
            '{{supplier_name}}' => '테스트 의뢰처',
            '{{project_name}}' => '테스트 프로젝트',
            '{{due_date}}' => date('Y-m-d', strtotime('+7 days')),
            '{{upload_link}}' => base_url . 'upload_portal/?token=test123',
            '{{document_list}}' => "• 안전관리계획서 (필수)\n• 유해위험방지계획서 (필수)\n• 사업자등록증 (필수)\n• 건설업면허증 (선택)"
        ];

        // 변수 치환
        $test_subject = $subject;
        $test_content = $content;

        foreach($sampleData as $key => $value) {
            $test_subject = str_replace($key, $value, $test_subject);
            $test_content = str_replace($key, $value, $test_content);
        }

        // HTML 형식으로 변환
        $html_content = "
        <div style='font-family: \"Noto Sans KR\", \"Malgun Gothic\", sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background-color: #f8f9fa; padding: 30px; border-radius: 10px;'>
                <h2 style='color: #333; margin-bottom: 20px;'>[테스트 이메일]</h2>
                <div style='background-color: #fff; padding: 20px; border-radius: 5px;'>
                    " . nl2br($test_content) . "
                </div>
                <div style='margin-top: 20px; padding: 15px; background-color: #e9ecef; border-radius: 5px;'>
                    <p style='margin: 0; color: #666; font-size: 14px;'>
                        <strong>※ 테스트 이메일입니다.</strong><br>
                        실제 발송 시에는 해당 프로젝트의 정보로 자동 치환됩니다.
                    </p>
                </div>
            </div>
        </div>";

        // 이메일 발송
        $result = $emailSender->sendEmail($email, '담당자', $test_subject, $html_content);

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
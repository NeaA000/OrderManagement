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
    function delete_multiple_requests(){
        extract($_POST);

        if(!isset($ids) || empty($ids) || !is_array($ids)){
            $resp['status'] = 'failed';
            $resp['msg'] = "삭제할 항목을 선택해주세요.";
            return json_encode($resp);
        }

        $deleted_count = 0;
        $failed_count = 0;
        $errors = [];

        // 트랜잭션 시작
        $this->conn->begin_transaction();

        try {
            foreach($ids as $id){
                $id = $this->conn->real_escape_string($id);

                // 1. uploaded_files 테이블에서 Wasabi 파일 정보 조회 및 삭제
                $wasabi_qry = $this->conn->query("
                    SELECT * FROM uploaded_files 
                    WHERE request_id = '{$id}'
                ");

                while($file = $wasabi_qry->fetch_assoc()){
                    // Wasabi에서 파일 삭제
                    if(!empty($file['wasabi_key']) && !empty($file['wasabi_bucket'])){
                        require_once('../classes/UploadHandler.php');
                        $uploadHandler = new UploadHandler();

                        $delete_result = $uploadHandler->deleteFromWasabi($file['wasabi_key'], $file['wasabi_bucket']);
                        if(!$delete_result){
                            error_log("Wasabi 파일 삭제 실패: " . $file['wasabi_key']);
                        }
                    }

                    // 로컬 파일 삭제
                    if(!empty($file['file_path'])){
                        $local_path = '../uploads/' . $file['file_path'];
                        if(file_exists($local_path)){
                            unlink($local_path);
                        }
                    }
                }

                // 2. document_uploads 테이블 파일 삭제
                $upload_qry = $this->conn->query("
                    SELECT du.file_path 
                    FROM document_uploads du 
                    INNER JOIN request_documents rd ON du.document_id = rd.id 
                    WHERE rd.request_id = '{$id}'
                ");

                while($upload = $upload_qry->fetch_assoc()){
                    $file_path = '../uploads/' . $upload['file_path'];
                    if(file_exists($file_path)){
                        unlink($file_path);
                    }
                }

                // 3. 메인 테이블 삭제 (CASCADE로 관련 테이블도 자동 삭제)
                $delete_main = $this->conn->query("DELETE FROM document_requests WHERE id = '{$id}'");

                if($delete_main){
                    $deleted_count++;

                    // 삭제 로그
                    $user_name = $_SESSION['userdata']['firstname'] . ' ' . $_SESSION['userdata']['lastname'];
                    error_log("Document request deleted - ID: {$id}, User: {$user_name}");
                } else {
                    $failed_count++;
                    $errors[] = "ID {$id}: " . $this->conn->error;
                }
            }

            // 트랜잭션 커밋
            $this->conn->commit();

            // 결과 반환
            if($deleted_count > 0 && $failed_count == 0){
                $resp['status'] = 'success';
                $resp['msg'] = "{$deleted_count}개의 서류 요청이 삭제되었습니다.";
                $this->settings->set_flashdata('success', "{$deleted_count}개의 서류 요청이 삭제되었습니다.");
            } else if($deleted_count > 0 && $failed_count > 0){
                $resp['status'] = 'partial';
                $resp['msg'] = "{$deleted_count}개 삭제 성공, {$failed_count}개 삭제 실패<br><small>" . implode("<br>", $errors) . "</small>";
                $this->settings->set_flashdata('warning', "{$deleted_count}개 삭제 성공, {$failed_count}개 삭제 실패");
            } else {
                throw new Exception("삭제 실패: " . implode(", ", $errors));
            }

        } catch (Exception $e) {
            // 트랜잭션 롤백
            $this->conn->rollback();

            $resp['status'] = 'failed';
            $resp['msg'] = "삭제 중 오류가 발생했습니다.";
            $resp['error'] = $e->getMessage();
            $this->settings->set_flashdata('error', "삭제 중 오류가 발생했습니다: " . $e->getMessage());
        }

        return json_encode($resp);
    }

    // send_request_email() - 단순화된 버전
    function send_request_email(){
        extract($_POST);

        if(!isset($id) || empty($id)){
            $resp['status'] = 'failed';
            $resp['msg'] = "요청 ID가 없습니다.";
            return json_encode($resp);
        }

        // EmailSender 클래스 사용
        require_once('EmailSender.php');
        $emailSender = new EmailSender();

        // 이메일 발송 - EmailSender가 모든 것을 처리
        $result = $emailSender->sendDocumentRequest($id);

        return json_encode($result);
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

        // MSO 조건부 주석 보존
        // Summernote가 인코딩한 MSO 주석을 원래대로 복원하되, SQL injection 방지를 위해 이스케이프는 유지
        $mso_patterns = [
            '/&lt;!--\[if mso\]&gt;/' => '<!--[if mso]>',
            '/&lt;!\[endif\]--&gt;/' => '<![endif]-->'
        ];
        
        foreach ($mso_patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }
        
        // 디버깅을 위한 로그
        error_log("Template content length: " . strlen($content));
        error_log("Template contains variables: " . (strpos($content, '{{') !== false ? 'Yes' : 'No'));

        // 데이터 준비
        $subject = $this->conn->real_escape_string($subject);
        $content = $this->conn->real_escape_string($content);

        // 트랜잭션 시작
        $this->conn->begin_transaction();

        try {
            // email_templates 테이블이 없으면 생성
            $table_check = $this->conn->query("SHOW TABLES LIKE 'email_templates'");
            if($table_check->num_rows == 0) {
                $create_table = "CREATE TABLE `email_templates` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `template_name` varchar(255) NOT NULL,
                    `template_type` varchar(50) NOT NULL,
                    `subject` varchar(500) NOT NULL,
                    `content` longtext NOT NULL,
                    `is_html` tinyint(1) DEFAULT 1,
                    `is_default` tinyint(1) DEFAULT 0,
                    `status` tinyint(1) DEFAULT 1,
                    `date_created` datetime DEFAULT current_timestamp(),
                    `date_updated` datetime DEFAULT NULL ON UPDATE current_timestamp(),
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                
                $this->conn->query($create_table);
            }

            if(!empty($template_id)) {
                // 기존 템플릿 업데이트
                $sql = "UPDATE email_templates SET 
                    subject = '{$subject}',
                    content = '{$content}',
                    date_updated = NOW()
                    WHERE id = '{$template_id}'";
            } else {
                // 기존 기본 템플릿 해제
                $this->conn->query("UPDATE email_templates SET is_default = 0 WHERE template_type = 'request_notification'");
                
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
                $this->conn->commit();
                $resp['status'] = 'success';
                $resp['msg'] = "이메일 템플릿이 성공적으로 저장되었습니다.";

                // 새로 생성된 경우 ID 반환
                if(empty($template_id)) {
                    $resp['template_id'] = $this->conn->insert_id;
                }
                
                // 저장된 내용 확인
                $check = $this->conn->query("SELECT LENGTH(content) as len, content FROM email_templates WHERE id = " . 
                    (empty($template_id) ? $this->conn->insert_id : $template_id))->fetch_assoc();
                
                error_log("Saved template length: " . $check['len']);
                error_log("Saved template has variables: " . (strpos($check['content'], '{{') !== false ? 'Yes' : 'No'));
                
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

    // 테스트 이메일 발송 - 단순화된 버전
    public function send_test_email() {
        extract($_POST);

        // 입력값 검증
        if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $resp['status'] = 'failed';
            $resp['msg'] = "유효한 이메일 주소를 입력해주세요.";
            return json_encode($resp);
        }

        // 디버깅용 로그
        error_log("Test email request - To: " . $email);
        error_log("Test email request - Subject provided: " . (!empty($subject) ? 'Yes' : 'No'));
        error_log("Test email request - Content provided: " . (!empty($content) ? 'Yes' : 'No'));

        // EmailSender 클래스 사용
        require_once('EmailSender.php');
        $emailSender = new EmailSender();

        // 테스트 이메일 발송
        // subject와 content가 전달되면 사용, 아니면 기본 템플릿 사용
        $result = $emailSender->sendTestEmail($email, $subject ?? null, $content ?? null);

        // 결과 로그
        error_log("Test email result: " . json_encode($result));

        return json_encode($result);
    }

    // 전체 파일 ZIP 다운로드
    public function download_all(){
        $request_id = $_GET['request_id'] ?? 0;

        if(!$request_id){
            http_response_code(400);
            die('요청 ID가 없습니다.');
        }

        // 요청 정보 조회
        $request = $this->conn->query("
            SELECT dr.*, sl.name as supplier_name 
            FROM document_requests dr 
            LEFT JOIN supplier_list sl ON dr.supplier_id = sl.id 
            WHERE dr.id = '{$request_id}'
        ")->fetch_assoc();

        if(!$request){
            http_response_code(404);
            die('요청을 찾을 수 없습니다.');
        }

        // 업로드된 파일들 조회 (해당 요청번호의 파일만)
        $files = $this->conn->query("
            SELECT rd.*, uf.wasabi_key, uf.wasabi_bucket 
            FROM request_documents rd
            LEFT JOIN uploaded_files uf ON uf.document_id = rd.id
            WHERE rd.request_id = '{$request_id}' 
            AND rd.status = 1 
            AND (rd.file_path IS NOT NULL OR uf.wasabi_key IS NOT NULL)
        ");

        if($files->num_rows == 0){
            http_response_code(404);
            die('다운로드할 파일이 없습니다.');
        }

        // ZIP 확장 모듈 확인
        if(!class_exists('ZipArchive')){
            // ZIP을 사용할 수 없는 경우 대체 방법 제공
            echo $this->generate_download_list_page($files, $request);
            exit;
        }

        // ZIP 파일 생성
        $zip = new ZipArchive();

        // ZIP 파일명: 요청번호_프로젝트명_날짜시간.zip
        $project_name_clean = preg_replace('/[^가-힣a-zA-Z0-9]/', '_', $request['project_name']);
        $zipFileName = $request['request_no'] . "_" . $project_name_clean . "_" . date('YmdHis') . ".zip";
        $zipFilePath = sys_get_temp_dir() . '/' . $zipFileName;

        if($zip->open($zipFilePath, ZipArchive::CREATE) !== TRUE){
            die('ZIP 파일 생성 실패');
        }

        // 폴더명: 요청번호_프로젝트명
        $folderName = $request['request_no'] . "_" . $project_name_clean;

        // 파일 추가
        $fileCount = 0;
        while($file = $files->fetch_assoc()){
            $fileAdded = false;

            // Wasabi 파일 처리
            if($this->settings->info('use_wasabi') === 'true' && !empty($file['wasabi_key'])){
                try {
                    // UploadHandler 클래스 사용
                    require_once('UploadHandler.php');
                    $uploadHandler = new UploadHandler();

                    // Wasabi에서 파일 가져오기
                    $tempFile = tempnam(sys_get_temp_dir(), 'wasabi_');
                    $result = $uploadHandler->downloadFromWasabi($file['wasabi_key'], $tempFile);

                    if($result && file_exists($tempFile)){
                        // ZIP에 추가 (폴더 구조 포함)
                        $fileName = $file['document_name'] . '_' . basename($file['file_name'] ?: 'file_' . $fileCount);
                        $zip->addFile($tempFile, $folderName . '/' . $fileName);
                        $fileAdded = true;

                        // 임시 파일 삭제
                        unlink($tempFile);
                    }
                } catch(Exception $e) {
                    error_log("Wasabi download error: " . $e->getMessage());
                }
            }

            // 로컬 파일 처리
            if(!$fileAdded && !empty($file['file_path'])){
                $filePath = base_app . $file['file_path'];
                if(file_exists($filePath)){
                    // 파일명 생성: 서류명_원본파일명
                    $originalFileName = basename($file['file_path']);
                    $fileName = $file['document_name'] . '_' . $originalFileName;

                    // ZIP에 추가 (폴더 구조 포함)
                    $zip->addFile($filePath, $folderName . '/' . $fileName);
                    $fileCount++;
                }
            }
        }

        $zip->close();

        if($fileCount == 0){
            unlink($zipFilePath);
            die('다운로드할 파일이 없습니다.');
        }

        // ZIP 파일 다운로드
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
        header('Content-Length: ' . filesize($zipFilePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        // 파일 출력
        readfile($zipFilePath);

        // 임시 파일 삭제
        unlink($zipFilePath);

        exit;
    }

    // ZIP을 사용할 수 없을 때 파일 목록 페이지 생성
    private function generate_download_list_page($files, $request){
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>파일 다운로드 - ' . htmlspecialchars($request['project_name']) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .info { background: #e8f4f8; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .file-list { list-style: none; padding: 0; }
        .file-item { background: #f8f9fa; margin: 10px 0; padding: 15px; border-radius: 5px; display: flex; justify-content: space-between; align-items: center; }
        .file-name { font-weight: bold; color: #333; }
        .download-btn { background: #007bff; color: white; padding: 8px 20px; text-decoration: none; border-radius: 4px; }
        .download-btn:hover { background: #0056b3; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>파일 다운로드</h1>
        <div class="warning">
            <strong>알림:</strong> ZIP 압축 기능을 사용할 수 없어 개별 파일 다운로드 목록을 제공합니다.
        </div>
        <div class="info">
            <p><strong>프로젝트:</strong> ' . htmlspecialchars($request['project_name']) . '</p>
            <p><strong>요청번호:</strong> ' . htmlspecialchars($request['request_no']) . '</p>
            <p><strong>의뢰처:</strong> ' . htmlspecialchars($request['supplier_name']) . '</p>
        </div>
        <ul class="file-list">';

        $files->data_seek(0); // 커서를 처음으로 되돌림
        while($file = $files->fetch_assoc()){
            $download_url = '';
            $file_name = $file['document_name'];
            
            // 파일 다운로드 URL 생성
            if(!empty($file['wasabi_key']) && $this->settings->info('use_wasabi') === 'true'){
                // Wasabi 파일인 경우
                $download_url = base_url . 'admin/upload_portal/download.php?id=' . $file['id'] . '&internal_download=1';
            } elseif(!empty($file['file_path'])) {
                // 로컬 파일인 경우
                $download_url = base_url . 'admin/upload_portal/download.php?id=' . $file['id'] . '&internal_download=1';
            }
            
            if($download_url){
                $html .= '
            <li class="file-item">
                <span class="file-name">' . htmlspecialchars($file_name) . '</span>
                <a href="' . $download_url . '" class="download-btn" target="_blank">다운로드</a>
            </li>';
            }
        }

        $html .= '
        </ul>
    </div>
</body>
</html>';

        return $html;
    }

    // 요청 상태 업데이트 함수 추가
    public function update_request_status() {
        extract($_POST);
        
        // ID 검증
        if(!isset($id) || empty($id)) {
            $resp['status'] = 'failed';
            $resp['msg'] = "요청 ID가 없습니다.";
            return json_encode($resp);
        }
        
        // 상태값 검증
        if(!isset($status) || !in_array($status, ['0', '1', '2'])) {
            $resp['status'] = 'failed';
            $resp['msg'] = "올바른 상태값이 아닙니다.";
            return json_encode($resp);
        }
        
        // 트랜잭션 시작
        $this->conn->begin_transaction();
        
        try {
            // 상태 업데이트
            $update = $this->conn->query("UPDATE document_requests SET status = '{$status}' WHERE id = '{$id}'");
            
            if($update) {
                // 워크플로우 상태 기록
                $step_name = $status == 0 ? '대기중' : ($status == 1 ? '진행중' : '완료');
                $step_description = !empty($notes) ? $notes : "상태가 {$step_name}(으)로 변경되었습니다.";
                
                // 기존 current 상태 해제
                $this->conn->query("UPDATE workflow_status SET is_current = 0 WHERE request_id = '{$id}'");
                
                // 새 상태 추가
                $workflow_sql = "INSERT INTO workflow_status 
                    (request_id, current_step, step_name, step_description, started_at, assigned_to, is_current) 
                    VALUES 
                    ('{$id}', '{$step_name}', '{$step_name}', '{$step_description}', NOW(), '{$this->settings->userdata('id')}', 1)";
                
                $this->conn->query($workflow_sql);
                
                $this->conn->commit();
                $resp['status'] = 'success';
                $resp['msg'] = "상태가 성공적으로 변경되었습니다.";
            } else {
                throw new Exception("상태 업데이트 실패");
            }
            
        } catch (Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = "상태 변경 중 오류가 발생했습니다: " . $e->getMessage();
        }
        
        return json_encode($resp);
    }

    // Wasabi 연결 테스트 - 완전 수정 버전
    public function test_wasabi() {
        extract($_POST);

        // Secret Key가 비어있으면 DB에서 가져오기
        if(empty($secret)) {
            // SystemSettings를 통해 Wasabi 설정 가져오기
            $wasabi_config = $this->settings->get_wasabi_config();

            // DB에서 가져온 Secret Key 사용
            if(!empty($wasabi_config['secret'])) {
                $secret = $wasabi_config['secret'];
            }

            // Access Key도 비어있으면 DB에서 가져오기
            if(empty($key) && !empty($wasabi_config['key'])) {
                $key = $wasabi_config['key'];
            }

            // Bucket도 비어있으면 DB에서 가져오기
            if(empty($bucket) && !empty($wasabi_config['bucket'])) {
                $bucket = $wasabi_config['bucket'];
            }
        }

        // 디버깅을 위한 로그
        error_log("Wasabi Test - Key exists: " . (!empty($key) ? 'Yes' : 'No'));
        error_log("Wasabi Test - Secret exists: " . (!empty($secret) ? 'Yes' : 'No'));
        error_log("Wasabi Test - Bucket: " . $bucket);

        // 입력값 검증
        if(empty($key) || empty($secret) || empty($bucket)) {
            $missing = [];
            if(empty($key)) $missing[] = 'Access Key';
            if(empty($secret)) $missing[] = 'Secret Key (DB에 저장된 값도 없음)';
            if(empty($bucket)) $missing[] = 'Bucket 이름';

            return json_encode([
                'status' => 'failed',
                'msg' => implode(', ', $missing) . '가 비어있습니다. 시스템 설정에서 다시 저장해주세요.'
            ]);
        }

        // Composer autoload 포함
        require_once('../vendor/autoload.php');

        try {
            // S3 클라이언트 설정
            $s3Client = new \Aws\S3\S3Client([
                'version' => 'latest',
                'region' => $region ?: 'ap-northeast-1',
                'endpoint' => $endpoint ?: 'https://s3.ap-northeast-1.wasabisys.com',
                'credentials' => [
                    'key' => $key,
                    'secret' => $secret
                ],
                'use_path_style_endpoint' => true,
                'http' => [
                    'verify' => false // 개발 환경에서 SSL 검증 비활성화
                ]
            ]);

            // 버킷 존재 확인
            $result = $s3Client->headBucket([
                'Bucket' => $bucket
            ]);

            // 테스트 파일 업로드 시도
            $testKey = 'test/connection-test-' . time() . '.txt';
            $s3Client->putObject([
                'Bucket' => $bucket,
                'Key' => $testKey,
                'Body' => 'Connection test successful at ' . date('Y-m-d H:i:s'),
                'ContentType' => 'text/plain'
            ]);

            // 테스트 파일 삭제
            $s3Client->deleteObject([
                'Bucket' => $bucket,
                'Key' => $testKey
            ]);

            return json_encode([
                'status' => 'success',
                'msg' => '연결 성공! 버킷에 접근 가능합니다.'
            ]);

        } catch (\Aws\S3\Exception\S3Exception $e) {
            $errorCode = $e->getAwsErrorCode();
            $errorMsg = match($errorCode) {
                'InvalidAccessKeyId' => 'Access Key가 올바르지 않습니다.',
                'SignatureDoesNotMatch' => 'Secret Key가 올바르지 않습니다.',
                'NoSuchBucket' => '버킷을 찾을 수 없습니다.',
                'AccessDenied' => '접근 권한이 없습니다.',
                default => '연결 실패: ' . $e->getMessage()
            };

            return json_encode([
                'status' => 'failed',
                'msg' => $errorMsg
            ]);
        } catch (Exception $e) {
            return json_encode([
                'status' => 'failed',
                'msg' => '오류 발생: ' . $e->getMessage()
            ]);
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
    case 'delete_request':
        echo $Master->delete_request();
        break;

    case 'delete_multiple_requests':
       echo $Master->delete_multiple_requests();
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
    case 'test_wasabi':
        echo $Master->test_wasabi();
        break;

    case 'update_request_status':
        echo $Master->update_request_status();
        break;
    case 'download_all':
        echo $Master->download_all();
        break;

    default:
        // echo $sysset->index();
        break;
}
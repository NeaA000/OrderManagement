<?php if($_settings->chk_flashdata('success')): ?>
    <script>
        alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
    </script>
<?php endif;?>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">서류 분류별 목록 관리</h3>
        <div class="card-tools">
            <a href="javascript:void(0)" class="btn btn-flat btn-success" id="create_main_category">
                <span class="fas fa-plus"></span> 새 대분류(체크박스) 추가
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">

            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>서류 분류 관리:</strong> 각 체크박스별로 포함될 서류 목록을 관리합니다.
                        대분류는 체크박스, 중분류는 실제 서류, 소분류는 세부 항목입니다.
                    </div>
                </div>
            </div>

            <!-- 10개 대분류별 서류 목록 관리 -->
            <?php
            $main_categories = $conn->query("
                SELECT * FROM document_categories 
                WHERE parent_id IS NULL 
                ORDER BY display_order ASC
            ");

            while($main_cat = $main_categories->fetch_assoc()):
                $sub_count = $conn->query("SELECT COUNT(*) as cnt FROM document_categories WHERE parent_id = '{$main_cat['id']}'")->fetch_array()['cnt'];
                ?>

                <div class="card card-outline <?php echo $main_cat['is_required'] ? 'card-danger' : 'card-secondary' ?> mb-4">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <div class="custom-control custom-checkbox mr-3">
                                <input type="checkbox" class="custom-control-input"
                                       id="main_cat_<?php echo $main_cat['id'] ?>"
                                    <?php echo $main_cat['is_required'] ? 'checked' : '' ?>
                                       disabled>
                                <label class="custom-control-label font-weight-bold text-lg" for="main_cat_<?php echo $main_cat['id'] ?>">
                                    <?php echo htmlspecialchars($main_cat['name']) ?>
                                </label>
                            </div>

                            <span class="badge <?php echo $main_cat['is_required'] ? 'badge-danger' : 'badge-secondary' ?> ml-auto mr-2">
                            <?php echo $main_cat['is_required'] ? '필수' : '선택' ?>
                        </span>

                            <span class="badge badge-info mr-2">
                            순서: <?php echo $main_cat['display_order'] ?>
                        </span>

                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary edit_main_category" data-id="<?php echo $main_cat['id'] ?>">
                                    <i class="fas fa-edit"></i> 수정
                                </button>
                                <button class="btn btn-sm btn-outline-success add_sub_category" data-parent-id="<?php echo $main_cat['id'] ?>">
                                    <i class="fas fa-plus"></i> 중분류 추가
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <?php if($sub_count > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="bg-light">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="30%">중분류 (서류명)</th>
                                        <th width="20%">소분류</th>
                                        <th width="10%">필수여부</th>
                                        <th width="10%">순서</th>
                                        <th width="10%">상태</th>
                                        <th width="15%">작업</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $sub_qry = $conn->query("
                                        SELECT * FROM document_categories 
                                        WHERE parent_id = '{$main_cat['id']}' AND level = 2
                                        ORDER BY display_order ASC, name ASC
                                    ");

                                    $sub_num = 1;
                                    while($sub_cat = $sub_qry->fetch_assoc()):
                                        // 소분류 개수 확인
                                        $subsub_count = $conn->query("SELECT COUNT(*) as cnt FROM document_categories WHERE parent_id = '{$sub_cat['id']}'")->fetch_array()['cnt'];

                                        $required_badge = $sub_cat['is_required'] ?
                                            '<span class="badge badge-danger badge-sm">필수</span>' :
                                            '<span class="badge badge-light badge-sm">선택</span>';

                                        $status_badge = $sub_cat['status'] ?
                                            '<span class="badge badge-success badge-sm">활성</span>' :
                                            '<span class="badge badge-warning badge-sm">비활성</span>';
                                        ?>
                                        <tr>
                                            <td><?php echo $sub_num++ ?></td>
                                            <td>
                                                <i class="fas fa-file-alt text-info mr-2"></i>
                                                <strong><?php echo htmlspecialchars($sub_cat['name']) ?></strong>
                                            </td>
                                            <td>
                                                <?php if($subsub_count > 0): ?>
                                                    <span class="badge badge-info"><?php echo $subsub_count ?>개</span>
                                                    <button class="btn btn-xs btn-outline-info ml-1 view_subsub" data-parent-id="<?php echo $sub_cat['id'] ?>">
                                                        <i class="fas fa-eye"></i> 보기
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">없음</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $required_badge ?></td>
                                            <td><?php echo $sub_cat['display_order'] ?></td>
                                            <td><?php echo $status_badge ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary edit_category" data-id="<?php echo $sub_cat['id'] ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-success add_subsub_category" data-parent-id="<?php echo $sub_cat['id'] ?>">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger delete_category" data-id="<?php echo $sub_cat['id'] ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- 소분류 표시 (숨김/보임 토글) -->
                                        <?php
                                        $subsub_qry = $conn->query("
                                        SELECT * FROM document_categories 
                                        WHERE parent_id = '{$sub_cat['id']}' AND level = 3
                                        ORDER BY display_order ASC, name ASC
                                    ");

                                        if($subsub_qry->num_rows > 0):
                                            ?>
                                            <tr class="subsub-row" data-parent="<?php echo $sub_cat['id'] ?>" style="display: none;">
                                                <td colspan="7">
                                                    <div class="ml-4 p-2 bg-light rounded">
                                                        <small><strong>소분류 목록:</strong></small>
                                                        <div class="row mt-2">
                                                            <?php while($subsub_cat = $subsub_qry->fetch_assoc()): ?>
                                                                <div class="col-md-6 mb-2">
                                                                    <div class="d-flex align-items-center">
                                                                        <i class="fas fa-file text-success mr-2"></i>
                                                                        <span><?php echo htmlspecialchars($subsub_cat['name']) ?></span>
                                                                        <span class="badge <?php echo $subsub_cat['is_required'] ? 'badge-danger' : 'badge-light' ?> badge-sm ml-2">
                                                                <?php echo $subsub_cat['is_required'] ? '필수' : '선택' ?>
                                                            </span>
                                                                        <div class="btn-group btn-group-xs ml-auto">
                                                                            <button class="btn btn-outline-primary btn-xs edit_category" data-id="<?php echo $subsub_cat['id'] ?>">
                                                                                <i class="fas fa-edit"></i>
                                                                            </button>
                                                                            <button class="btn btn-outline-danger btn-xs delete_category" data-id="<?php echo $subsub_cat['id'] ?>">
                                                                                <i class="fas fa-trash"></i>
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endwhile; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>

                                    <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">아직 등록된 서류가 없습니다.</p>
                                <button class="btn btn-primary add_sub_category" data-parent-id="<?php echo $main_cat['id'] ?>">
                                    <i class="fas fa-plus"></i> 첫 번째 서류 추가
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php endwhile; ?>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        // 대분류(체크박스) 수정
        $('.edit_main_category').click(function(){
            uni_modal("<i class='fa fa-edit'></i> 대분류 수정", "document_categories/manage_category.php?id="+$(this).attr('data-id'), "")
        })

        // 중분류 추가
        $('.add_sub_category').click(function(){
            var parent_id = $(this).attr('data-parent-id');
            uni_modal("<i class='fa fa-plus'></i> 중분류(서류) 추가", "document_categories/manage_category.php?parent_id="+parent_id+"&level=2", "")
        })

        // 소분류 추가
        $('.add_subsub_category').click(function(){
            var parent_id = $(this).attr('data-parent-id');
            uni_modal("<i class='fa fa-plus'></i> 소분류 추가", "document_categories/manage_category.php?parent_id="+parent_id+"&level=3", "")
        })

        // 분류 수정
        $('.edit_category').click(function(){
            uni_modal("<i class='fa fa-edit'></i> 분류 수정", "document_categories/manage_category.php?id="+$(this).attr('data-id'), "")
        })

        // 소분류 보기/숨기기 토글
        $('.view_subsub').click(function(){
            var parent_id = $(this).attr('data-parent-id');
            var row = $('.subsub-row[data-parent="'+parent_id+'"]');

            if(row.is(':visible')) {
                row.hide();
                $(this).html('<i class="fas fa-eye"></i> 보기');
            } else {
                row.show();
                $(this).html('<i class="fas fa-eye-slash"></i> 숨기기');
            }
        })

        // 새 대분류 추가
        $('#create_main_category').click(function(){
            uni_modal("<i class='fa fa-plus'></i> 새 대분류(체크박스) 추가", "document_categories/manage_category.php?level=1", "")
        })

        // 분류 삭제
        $('.delete_category').click(function(){
            _conf("정말로 이 분류를 삭제하시겠습니까?<br><small class='text-muted'>하위 분류가 있는 경우 모두 함께 삭제됩니다.</small>", "delete_category", [$(this).attr('data-id')])
        })
    })

    function delete_category($id){
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=delete_category",
            method: "POST",
            data: {id: $id},
            dataType: "json",
            error: err => {
                console.log(err)
                alert_toast("오류가 발생했습니다.", 'error');
                end_loader();
            },
            success: function(resp){
                if(typeof resp == 'object' && resp.status == 'success'){
                    location.reload();
                } else {
                    alert_toast("삭제 중 오류가 발생했습니다.", 'error');
                    end_loader();
                }
            }
        })
    }
</script>
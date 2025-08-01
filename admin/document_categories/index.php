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
                <span class="fas fa-plus"></span> 새 메인 카테고리 추가
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">

            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>서류 분류 관리:</strong> 메인 카테고리별로 포함될 서류 목록을 관리합니다.
                        메인 카테고리 → 서류 그룹 → 실제 서류 순으로 구성됩니다.
                    </div>
                </div>
            </div>

            <!-- 메인 카테고리별 서류 목록 관리 -->
            <?php
            $main_categories = $conn->query("
                SELECT * FROM document_categories 
                WHERE parent_id IS NULL OR parent_id = 0
                ORDER BY display_order ASC
            ");

            while($main_cat = $main_categories->fetch_assoc()):
                $sub_count = $conn->query("SELECT COUNT(*) as cnt FROM document_categories WHERE parent_id = '{$main_cat['id']}'")->fetch_array()['cnt'];
                ?>

                <div class="card mb-4" data-id="<?php echo $main_cat['id'] ?>">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <span class="drag-handle mr-3" style="cursor: move;">
                                <i class="fas fa-grip-vertical text-muted"></i>
                            </span>

                            <h5 class="font-weight-bold mb-0 mr-3">
                                <?php echo htmlspecialchars($main_cat['name']) ?>
                            </h5>

                            <span class="badge <?php echo $main_cat['is_required'] ? 'badge-danger' : 'badge-secondary' ?> ml-auto mr-2">
                                <?php echo $main_cat['is_required'] ? '필수' : '선택' ?>
                            </span>

                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary edit_main_category" data-id="<?php echo $main_cat['id'] ?>">
                                    <i class="fas fa-edit"></i> 수정
                                </button>
                                <button class="btn btn-sm btn-outline-success add_sub_category" data-parent-id="<?php echo $main_cat['id'] ?>">
                                    <i class="fas fa-plus"></i> 서류 그룹 추가
                                </button>
                                <button class="btn btn-sm btn-outline-danger delete_category" data-id="<?php echo $main_cat['id'] ?>">
                                    <i class="fas fa-trash"></i> 삭제
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
                                        <th width="5%"></th>
                                        <th width="10%">#</th>
                                        <th width="40%">서류 그룹</th>
                                        <th width="25%">실제 서류</th>
                                        <th width="20%">작업</th>
                                    </tr>
                                    </thead>
                                    <tbody class="sortable-table" data-parent-id="<?php echo $main_cat['id'] ?>">
                                    <?php
                                    $sub_qry = $conn->query("
                                        SELECT * FROM document_categories 
                                        WHERE parent_id = '{$main_cat['id']}' AND level = 2
                                        ORDER BY display_order ASC, name ASC
                                    ");

                                    $sub_num = 1;
                                    while($sub_cat = $sub_qry->fetch_assoc()):
                                        // 실제 서류 개수 확인
                                        $subsub_count = $conn->query("SELECT COUNT(*) as cnt FROM document_categories WHERE parent_id = '{$sub_cat['id']}'")->fetch_array()['cnt'];
                                        ?>
                                        <tr data-id="<?php echo $sub_cat['id'] ?>">
                                            <td>
                                                <span class="drag-handle-sub" style="cursor: move;">
                                                    <i class="fas fa-grip-vertical text-muted"></i>
                                                </span>
                                            </td>
                                            <td><?php echo $sub_num++ ?></td>
                                            <td>
                                                <i class="fas fa-folder text-info mr-2"></i>
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

                                        <!-- 실제 서류 표시 (숨김/보임 토글) -->
                                        <?php
                                        $subsub_qry = $conn->query("
                                            SELECT * FROM document_categories 
                                            WHERE parent_id = '{$sub_cat['id']}' AND level = 3
                                            ORDER BY display_order ASC, name ASC
                                        ");

                                        if($subsub_qry->num_rows > 0):
                                            ?>
                                            <tr class="subsub-row" data-parent="<?php echo $sub_cat['id'] ?>" style="display: none;">
                                                <td colspan="5">
                                                    <div class="ml-4 p-2 bg-light rounded">
                                                        <small><strong>실제 서류 목록:</strong></small>
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
                                                                    <?php if(!empty($subsub_cat['description'])): ?>
                                                                        <small class="text-muted ml-4"><?php echo htmlspecialchars($subsub_cat['description']) ?></small>
                                                                    <?php endif; ?>
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
                                <p class="text-muted">아직 등록된 서류 그룹이 없습니다.</p>
                                <button class="btn btn-primary add_sub_category" data-parent-id="<?php echo $main_cat['id'] ?>">
                                    <i class="fas fa-plus"></i> 첫 번째 서류 그룹 추가
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php endwhile; ?>

            <!-- 메인 카테고리가 없는 경우 -->
            <?php if($main_categories->num_rows == 0): ?>
                <div class="text-center py-5">
                    <i class="fas fa-folder-plus fa-4x text-muted mb-4"></i>
                    <h4 class="text-muted">메인 카테고리가 없습니다</h4>
                    <p class="text-muted">첫 번째 메인 카테고리를 추가해보세요.</p>
                    <button class="btn btn-primary btn-lg" id="create_first_category">
                        <i class="fas fa-plus"></i> 첫 번째 메인 카테고리 추가
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // jQuery UI 포함 확인 및 추가
    if(typeof jQuery.ui === 'undefined') {
        var script = document.createElement('script');
        script.src = 'https://code.jquery.com/ui/1.12.1/jquery-ui.min.js';
        document.head.appendChild(script);

        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css';
        document.head.appendChild(link);
    }

    // 스타일을 JavaScript로 추가
    $('<style>')
        .prop('type', 'text/css')
        .html('.ui-sortable-helper { box-shadow: 0 5px 15px rgba(0,0,0,0.3); opacity: 0.8; }')
        .appendTo('head');

    $(document).ready(function(){
        // 드래그 앤 드롭으로 순서 변경
        setTimeout(function() {
            $('.container-fluid').sortable({
                items: '.card[data-id]',
                handle: '.drag-handle',
                update: function(event, ui) {
                    var order = [];
                    $('.card[data-id]').each(function(index) {
                        order.push({
                            id: $(this).data('id'),
                            order: index + 1
                        });
                    });

                    updateCategoryOrder(order);
                }
            });

            // 서류 그룹 드래그 앤 드롭
            $('.sortable-table').each(function() {
                $(this).sortable({
                    items: 'tr[data-id]',
                    handle: '.drag-handle-sub',
                    update: function(event, ui) {
                        var order = [];
                        $(this).find('tr[data-id]').each(function(index) {
                            order.push({
                                id: $(this).data('id'),
                                order: index + 1
                            });
                        });

                        updateCategoryOrder(order);
                    }
                });
            });
        }, 500);
    });

    $(document).ready(function(){
        // 메인 카테고리 수정
        $('.edit_main_category').click(function(){
            uni_modal("<i class='fa fa-edit'></i> 메인 카테고리 수정", "document_categories/manage_category.php?id="+$(this).attr('data-id'), "mid-large")
        })

        // 서류 그룹 추가
        $('.add_sub_category').click(function(){
            var parent_id = $(this).attr('data-parent-id');
            uni_modal("<i class='fa fa-plus'></i> 서류 그룹 추가", "document_categories/manage_category.php?parent_id="+parent_id+"&level=2", "mid-large")
        })

        // 실제 서류 추가
        $('.add_subsub_category').click(function(){
            var parent_id = $(this).attr('data-parent-id');
            uni_modal("<i class='fa fa-plus'></i> 실제 서류 추가", "document_categories/manage_category.php?parent_id="+parent_id+"&level=3", "mid-large")
        })

        // 분류 수정
        $('.edit_category').click(function(){
            uni_modal("<i class='fa fa-edit'></i> 분류 수정", "document_categories/manage_category.php?id="+$(this).attr('data-id'), "mid-large")
        })

        // 실제 서류 보기/숨기기 토글
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

        // 새 메인 카테고리 추가
        $('#create_main_category, #create_first_category').click(function(){
            uni_modal("<i class='fa fa-plus'></i> 새 메인 카테고리 추가", "document_categories/manage_category.php?level=1", "mid-large")
        })

        // 분류 삭제
        $('.delete_category').click(function(){
            var categoryId = $(this).attr('data-id');
            _conf("정말로 이 분류를 삭제하시겠습니까?<br><small class='text-muted'>하위 분류가 있는 경우 모두 함께 삭제됩니다.</small>", "delete_category", [categoryId])
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
                    alert_toast("분류가 성공적으로 삭제되었습니다.", 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert_toast("삭제 중 오류가 발생했습니다.", 'error');
                    end_loader();
                }
            }
        })
    }

    function updateCategoryOrder(order) {
        console.log("Updating order:", order); // 디버깅용

        $.ajax({
            url: _base_url_ + "classes/Master.php?f=update_category_order",
            method: "POST",
            data: {order: JSON.stringify(order)}, // JSON 문자열로 변환
            dataType: "json",
            error: err => {
                console.error("Ajax error:", err);
                console.error("Response Text:", err.responseText);
                alert_toast("순서 변경 중 오류가 발생했습니다.", 'error');
            },
            success: function(resp){
                console.log("Server response:", resp); // 디버깅용

                if(typeof resp == 'object' && resp.status == 'success'){
                    // 성공 메시지 표시 (선택사항)
                    // alert_toast("순서가 변경되었습니다.", 'success');
                } else {
                    console.error("Error response:", resp);
                    alert_toast("순서 변경 중 오류가 발생했습니다.", 'error');
                }
            }
        })
    }
</script>
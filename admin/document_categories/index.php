<?php if($_settings->chk_flashdata('success')): ?>
    <script>
        alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
    </script>
<?php endif;?>

<style>
    .tree-view {
        list-style: none;
        padding-left: 0;
    }
    .tree-item {
        margin: 0;
        padding: 0;
        position: relative;
    }
    .tree-item-content {
        display: flex;
        align-items: center;
        padding: 8px 12px;
        border-radius: 4px;
        transition: all 0.2s;
        margin-bottom: 2px;
    }
    .tree-item-content:hover {
        background-color: #f5f5f5;
    }
    .tree-toggle {
        width: 20px;
        height: 20px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        margin-right: 8px;
        color: #666;
    }
    .tree-toggle:hover {
        background-color: #e0e0e0;
        border-radius: 3px;
    }
    .tree-icon {
        margin-right: 8px;
        width: 20px;
        text-align: center;
    }
    .tree-label {
        flex-grow: 1;
        font-weight: 500;
    }
    .tree-actions {
        display: flex;
        gap: 4px;
        opacity: 0;
        transition: opacity 0.2s;
    }
    .tree-item-content:hover .tree-actions {
        opacity: 1;
    }
    .tree-children {
        padding-left: 32px;
        display: none;
    }
    .tree-children.expanded {
        display: block;
    }
    .drag-handle {
        cursor: move;
        color: #999;
        margin-right: 8px;
    }
    .tree-badge {
        font-size: 12px;
        padding: 2px 8px;
        border-radius: 12px;
        margin-left: 8px;
    }
    .main-category {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        margin-bottom: 8px;
        border-radius: 6px;
    }
    .main-category .tree-item-content {
        font-size: 16px;
        padding: 12px 16px;
    }
    .sub-category {
        font-size: 14px;
    }
    .subsub-category {
        font-size: 13px;
        color: #666;
    }
</style>

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
                        <strong>서류 분류 관리:</strong> 드래그하여 순서를 변경하고, 클릭하여 하위 항목을 펼쳐보세요.
                    </div>
                </div>
            </div>

            <ul class="tree-view" id="main-categories">
                <?php
                $main_categories = $conn->query("
                    SELECT * FROM document_categories 
                    WHERE parent_id IS NULL OR parent_id = 0
                    ORDER BY display_order ASC
                ");

                while($main_cat = $main_categories->fetch_assoc()):
                    $sub_count = $conn->query("SELECT COUNT(*) as cnt FROM document_categories WHERE parent_id = '{$main_cat['id']}'")->fetch_array()['cnt'];
                    ?>
                    <li class="tree-item main-category" data-id="<?php echo $main_cat['id'] ?>">
                        <div class="tree-item-content">
                            <span class="drag-handle">
                                <i class="fas fa-grip-vertical"></i>
                            </span>
                            <span class="tree-toggle" onclick="toggleTree(this)">
                                <?php if($sub_count > 0): ?>
                                    <i class="fas fa-chevron-right"></i>
                                <?php endif; ?>
                            </span>
                            <span class="tree-icon">
                                <i class="fas fa-folder text-primary"></i>
                            </span>
                            <span class="tree-label">
                                <?php echo htmlspecialchars($main_cat['name']) ?>
                                <?php if($main_cat['is_required']): ?>
                                    <span class="badge badge-danger tree-badge">필수</span>
                                <?php endif; ?>
                                <?php if($sub_count > 0): ?>
                                    <span class="text-muted small">(<?php echo $sub_count ?>)</span>
                                <?php endif; ?>
                            </span>
                            <div class="tree-actions">
                                <button class="btn btn-sm btn-light" onclick="editMainCategory(<?php echo $main_cat['id'] ?>)" title="수정">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-light" onclick="addSubCategory(<?php echo $main_cat['id'] ?>)" title="서류 그룹 추가">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <button class="btn btn-sm btn-light text-danger" onclick="deleteCategory(<?php echo $main_cat['id'] ?>)" title="삭제">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>

                        <?php if($sub_count > 0): ?>
                            <ul class="tree-children">
                                <?php
                                $sub_qry = $conn->query("
                                    SELECT * FROM document_categories 
                                    WHERE parent_id = '{$main_cat['id']}' AND level = 2
                                    ORDER BY display_order ASC, name ASC
                                ");

                                while($sub_cat = $sub_qry->fetch_assoc()):
                                    $subsub_count = $conn->query("SELECT COUNT(*) as cnt FROM document_categories WHERE parent_id = '{$sub_cat['id']}'")->fetch_array()['cnt'];
                                    ?>
                                    <li class="tree-item sub-category" data-id="<?php echo $sub_cat['id'] ?>">
                                        <div class="tree-item-content">
                                            <span class="drag-handle">
                                                <i class="fas fa-grip-vertical"></i>
                                            </span>
                                            <span class="tree-toggle" onclick="toggleTree(this)">
                                                <?php if(($sub_cat['type'] ?? 'folder') == 'folder' && $subsub_count > 0): ?>
                                                    <i class="fas fa-chevron-right"></i>
                                                <?php endif; ?>
                                            </span>
                                            <span class="tree-icon">
                                                <i class="fas <?php echo ($sub_cat['type'] ?? 'folder') == 'folder' ? 'fa-folder-open text-info' : 'fa-file text-warning' ?>"></i>
                                            </span>
                                            <span class="tree-label">
                                                <?php echo htmlspecialchars($sub_cat['name']) ?>
                                                <?php if($sub_cat['is_required']): ?>
                                                    <span class="badge badge-danger tree-badge">필수</span>
                                                <?php endif; ?>
                                                <?php if(($sub_cat['type'] ?? 'folder') == 'folder' && $subsub_count > 0): ?>
                                                    <span class="text-muted small">(<?php echo $subsub_count ?>)</span>
                                                <?php endif; ?>
                                            </span>
                                            <div class="tree-actions">
                                                <button class="btn btn-sm btn-light" onclick="editCategory(<?php echo $sub_cat['id'] ?>)" title="수정">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if(($sub_cat['type'] ?? 'folder') == 'folder'): ?>
                                                    <button class="btn btn-sm btn-light" onclick="addSubsubCategory(<?php echo $sub_cat['id'] ?>)" title="실제 서류 추가">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-light text-danger" onclick="deleteCategory(<?php echo $sub_cat['id'] ?>)" title="삭제">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <?php if(($sub_cat['type'] ?? 'folder') == 'folder' && $subsub_count > 0): ?>
                                            <ul class="tree-children">
                                                <?php
                                                $subsub_qry = $conn->query("
                                                    SELECT * FROM document_categories 
                                                    WHERE parent_id = '{$sub_cat['id']}' AND level = 3
                                                    ORDER BY display_order ASC, name ASC
                                                ");

                                                while($subsub_cat = $subsub_qry->fetch_assoc()):
                                                    ?>
                                                    <li class="tree-item subsub-category" data-id="<?php echo $subsub_cat['id'] ?>">
                                                        <div class="tree-item-content">
                                                            <span class="drag-handle">
                                                                <i class="fas fa-grip-vertical"></i>
                                                            </span>
                                                            <span class="tree-icon">
                                                                <i class="fas fa-file text-success"></i>
                                                            </span>
                                                            <span class="tree-label">
                                                                <?php echo htmlspecialchars($subsub_cat['name']) ?>
                                                                <?php if($subsub_cat['is_required']): ?>
                                                                    <span class="badge badge-danger tree-badge">필수</span>
                                                                <?php endif; ?>
                                                            </span>
                                                            <div class="tree-actions">
                                                                <button class="btn btn-sm btn-light" onclick="editCategory(<?php echo $subsub_cat['id'] ?>)" title="수정">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <button class="btn btn-sm btn-light text-danger" onclick="deleteCategory(<?php echo $subsub_cat['id'] ?>)" title="삭제">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <?php if(!empty($subsub_cat['description'])): ?>
                                                            <div class="pl-5 text-muted small">
                                                                <?php echo htmlspecialchars($subsub_cat['description']) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endwhile; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endwhile; ?>
            </ul>

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
    // 트리 토글 기능
    function toggleTree(element) {
        const treeItem = element.closest('.tree-item');
        const children = treeItem.querySelector('.tree-children');
        const icon = element.querySelector('i');

        if (children) {
            children.classList.toggle('expanded');
            if (children.classList.contains('expanded')) {
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-chevron-down');
            } else {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-right');
            }
        }
    }

    // 카테고리 관리 함수들
    function editMainCategory(id) {
        uni_modal("<i class='fa fa-edit'></i> 메인 카테고리 수정", "document_categories/manage_category.php?id="+id, "mid-large");
    }

    function editCategory(id) {
        uni_modal("<i class='fa fa-edit'></i> 분류 수정", "document_categories/manage_category.php?id="+id, "mid-large");
    }

    function addSubCategory(parentId) {
        uni_modal("<i class='fa fa-plus'></i> 서류 그룹 추가", "document_categories/manage_category.php?parent_id="+parentId+"&level=2", "mid-large");
    }

    function addSubsubCategory(parentId) {
        uni_modal("<i class='fa fa-plus'></i> 실제 서류 추가", "document_categories/manage_category.php?parent_id="+parentId+"&level=3", "mid-large");
    }

    function deleteCategory(id) {
        _conf("정말로 이 분류를 삭제하시겠습니까?<br><small class='text-muted'>하위 분류가 있는 경우 모두 함께 삭제됩니다.</small>", "delete_category_func", [id]);
    }

    function delete_category_func(id) {
        start_loader();
        $.ajax({
            url: _base_url_ + "classes/Master.php?f=delete_category",
            method: "POST",
            data: {id: id},
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

    // jQuery UI 포함 확인 및 추가
    if(typeof jQuery.ui === 'undefined') {
        var script = document.createElement('script');
        script.src = 'https://code.jquery.com/ui/1.12.1/jquery-ui.min.js';
        document.head.appendChild(script);
    }

    $(document).ready(function(){
        // 드래그 앤 드롭 초기화
        setTimeout(function() {
            // 메인 카테고리 정렬
            $('#main-categories').sortable({
                items: '> .main-category',
                handle: '.drag-handle',
                update: function(event, ui) {
                    updateOrder('#main-categories > .main-category');
                }
            });

            // 서브 카테고리 정렬
            $('.main-category > .tree-children').each(function() {
                $(this).sortable({
                    items: '> .sub-category',
                    handle: '.drag-handle',
                    update: function(event, ui) {
                        updateOrder($(this).find('> .sub-category'));
                    }
                });
            });

            // 실제 서류 정렬
            $('.sub-category > .tree-children').each(function() {
                $(this).sortable({
                    items: '> .subsub-category',
                    handle: '.drag-handle',
                    update: function(event, ui) {
                        updateOrder($(this).find('> .subsub-category'));
                    }
                });
            });
        }, 500);

        // 순서 업데이트 함수
        function updateOrder(selector) {
            var order = [];
            $(selector).each(function(index) {
                order.push({
                    id: $(this).data('id'),
                    order: index + 1
                });
            });

            updateCategoryOrder(order);
        }

        // 새 메인 카테고리 추가
        $('#create_main_category, #create_first_category').click(function(){
            uni_modal("<i class='fa fa-plus'></i> 새 메인 카테고리 추가", "document_categories/manage_category.php?level=1", "mid-large");
        });
    });

    function updateCategoryOrder(order) {
        console.log("Updating order:", order);

        $.ajax({
            url: _base_url_ + "classes/Master.php?f=update_category_order",
            method: "POST",
            data: {order: JSON.stringify(order)},
            dataType: "json",
            error: err => {
                console.error("Ajax error:", err);
                console.error("Response Text:", err.responseText);
                alert_toast("순서 변경 중 오류가 발생했습니다.", 'error');
            },
            success: function(resp){
                console.log("Server response:", resp);

                if(typeof resp == 'object' && resp.status == 'success'){
                    // 성공 - 메시지는 표시하지 않음
                } else {
                    console.error("Error response:", resp);
                    alert_toast("순서 변경 중 오류가 발생했습니다.", 'error');
                }
            }
        })
    }
</script>
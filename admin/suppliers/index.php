<?php if($_settings->chk_flashdata('success')): ?>
    <script>
        alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
    </script>
<?php endif;?>

<style>
    /* 모던한 대시보드 스타일 */
    .dashboard-container {
        background-color: #f8f9fa;
        min-height: 100vh;
        padding: 20px;
    }

    /* 상단 통계 카드 */
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border: none;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 16px;
    }

    .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 4px;
    }

    .stat-label {
        font-size: 14px;
        color: #6b7280;
        font-weight: 500;
    }

    /* 검색 및 필터 영역 */
    .search-filter-area {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .search-box {
        position: relative;
    }

    .search-box input {
        width: 100%;
        padding: 12px 16px 12px 48px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 15px;
        transition: all 0.2s;
    }

    .search-box input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .search-box i {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
    }

    /* 의뢰처 카드 */
    .supplier-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: all 0.3s;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        height: 100%;
    }

    .supplier-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }

    .supplier-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: #3b82f6;
        transform: scaleY(0);
        transition: transform 0.3s;
    }

    .supplier-card:hover::before {
        transform: scaleY(1);
    }

    .supplier-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 20px;
    }

    .supplier-name {
        font-size: 20px;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 4px;
    }

    .supplier-date {
        font-size: 13px;
        color: #9ca3af;
    }

    .supplier-actions {
        position: relative;
    }

    .action-btn {
        background: #f3f4f6;
        border: none;
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }

    .action-btn:hover {
        background: #e5e7eb;
    }

    .supplier-contact {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 16px;
    }

    .contact-name {
        font-weight: 600;
        color: #374151;
        margin-bottom: 4px;
    }

    .contact-info {
        font-size: 14px;
        color: #6b7280;
    }

    .project-status {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 16px;
    }

    .status-badge {
        background: #dbeafe;
        color: #1d4ed8;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
    }

    .status-badge.active {
        background: #dcfce7;
        color: #16a34a;
    }

    .progress-section {
        margin-top: 20px;
    }

    .progress-label {
        font-size: 13px;
        color: #6b7280;
        margin-bottom: 8px;
        display: flex;
        justify-content: space-between;
    }

    .progress-bar-container {
        background: #f3f4f6;
        height: 8px;
        border-radius: 4px;
        overflow: hidden;
    }

    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
        transition: width 0.3s ease;
        border-radius: 4px;
    }

    /* 빠른 액션 버튼 */
    .quick-actions {
        display: flex;
        gap: 8px;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #f3f4f6;
    }

    .quick-action-btn {
        flex: 1;
        padding: 8px 12px;
        border: 1px solid #e5e7eb;
        background: white;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        color: #4b5563;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .quick-action-btn:hover {
        background: #f9fafb;
        border-color: #d1d5db;
        color: #1f2937;
    }

    .quick-action-btn i {
        font-size: 14px;
    }

    /* 필터 버튼 */
    .filter-btn {
        padding: 10px 20px;
        border: 1px solid #e5e7eb;
        background: white;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        color: #4b5563;
        cursor: pointer;
        transition: all 0.2s;
    }

    .filter-btn:hover {
        background: #f9fafb;
        border-color: #d1d5db;
    }

    .filter-btn.active {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }

    /* 빈 상태 */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }

    .empty-state i {
        font-size: 64px;
        color: #e5e7eb;
        margin-bottom: 16px;
    }

    .empty-state h3 {
        font-size: 20px;
        color: #374151;
        margin-bottom: 8px;
    }

    .empty-state p {
        color: #6b7280;
        margin-bottom: 24px;
    }

    /* 보기 모드 전환 버튼 */
    .view-mode-btn {
        padding: 8px 12px;
        border: 1px solid #e5e7eb;
        background: white;
        border-radius: 8px;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s;
        color: #6b7280;
    }

    .view-mode-btn:hover {
        background: #f9fafb;
        color: #4b5563;
    }

    .view-mode-btn.active {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }

    /* 리스트 뷰 스타일 */
    .list-view-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .list-view-table {
        width: 100%;
        table-layout: fixed;
    }

    .list-view-table tbody {
        position: relative;
    }

    .list-view-table thead {
        background: #f9fafb;
    }

    .list-view-table th {
        padding: 16px 20px;
        text-align: left;
        font-weight: 600;
        color: #374151;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #e5e7eb;
    }

    .list-view-table tbody tr {
        border-bottom: 1px solid #f3f4f6;
        transition: all 0.2s;
        cursor: pointer;
    }

    .list-view-table tbody tr:hover {
        background: #f9fafb;
    }

    .list-view-table td {
        padding: 20px;
        color: #4b5563;
        font-size: 14px;
    }

    .list-company-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .list-company-avatar {
        width: 40px;
        height: 40px;
        background: #e5e7eb;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: #6b7280;
    }

    .list-company-name {
        font-weight: 600;
        color: #1f2937;
        font-size: 15px;
    }

    .list-company-date {
        font-size: 13px;
        color: #9ca3af;
    }
</style>

<div class="dashboard-container">
    <!-- 헤더 섹션 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1" style="font-weight: 700; color: #1a1a1a;">의뢰처 관리</h2>
                    <p style="color: #6b7280; font-size: 15px;">거래처 정보와 프로젝트 진행 상황을 한눈에 확인하세요</p>
                </div>
                <button id="create_new" class="btn btn-primary" style="padding: 10px 20px; border-radius: 8px; font-weight: 500;">
                    <i class="fas fa-plus mr-2"></i>새 의뢰처 등록
                </button>
            </div>
        </div>
    </div>



    <!-- 검색 및 필터 -->
    <div class="search-filter-area">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="의뢰처명, 담당자명으로 검색...">
                </div>
            </div>
            <div class="col-md-6">
                <div class="d-flex gap-2 justify-content-md-end mt-3 mt-md-0">
                    <div class="btn-group mr-2" role="group">
                        <button class="view-mode-btn active" data-view="grid">
                            <i class="fas fa-th"></i>
                        </button>
                        <button class="view-mode-btn" data-view="list">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                    <button class="filter-btn active" data-filter="all">전체</button>
                    <button class="filter-btn" data-filter="active">진행중</button>
                    <button class="filter-btn" data-filter="completed">완료</button>
                    <button class="filter-btn" data-filter="no-project">프로젝트 없음</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 의뢰처 카드 그리드 -->
    <div class="row" id="supplierGrid" style="display: flex;">
        <?php
        $qry = $conn->query("SELECT * from `supplier_list` order by (`name`) asc ");
        if($qry->num_rows > 0):
            while($row = $qry->fetch_assoc()):
                // 해당 의뢰처의 공사 현황 조회
                $projects = $conn->query("SELECT 
                    dr.*,
                    (SELECT COUNT(*) FROM request_documents WHERE request_id = dr.id) as total_docs,
                    (SELECT COUNT(*) FROM request_documents WHERE request_id = dr.id AND status = 1) as submitted_docs
                    FROM document_requests dr 
                    WHERE dr.supplier_id = '{$row['id']}' 
                    ORDER BY dr.date_created DESC");

                $project_count = $projects->num_rows;
                $latest_project = $projects->fetch_assoc();
                $progress = 0;
                if($latest_project && $latest_project['total_docs'] > 0) {
                    $progress = round(($latest_project['submitted_docs'] / $latest_project['total_docs']) * 100);
                }

                // 필터 데이터 속성 설정
                $filter_status = 'no-project';
                if($project_count > 0) {
                    $filter_status = $progress == 100 ? 'completed' : 'active';
                }
                ?>
                <div class="col-lg-4 col-md-6 mb-4 supplier-item"
                     data-id="<?php echo $row['id'] ?>"
                     data-filter="<?php echo $filter_status ?>"
                     data-name="<?php echo strtolower($row['name']) ?>"
                     data-contact="<?php echo strtolower($row['contact_person']) ?>">
                    <div class="supplier-card">
                        <div class="supplier-header">
                            <div>
                                <div class="supplier-name"><?php echo $row['name'] ?></div>
                                <div class="supplier-date">
                                    <i class="far fa-calendar-alt"></i>
                                    <?php echo date("Y.m.d", strtotime($row['date_created'])) ?>
                                </div>
                            </div>
                            <div class="supplier-actions">
                                <button class="action-btn dropdown-toggle" data-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item" href="javascript:void(0)" onclick="viewProjects(<?php echo $row['id'] ?>)">
                                        <i class="fas fa-folder-open text-info"></i> 공사 목록
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="javascript:void(0)" onclick="editSupplier(<?php echo $row['id'] ?>)">
                                        <i class="fas fa-edit text-success"></i> 정보수정
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="javascript:void(0)" onclick="deleteSupplier(<?php echo $row['id'] ?>)">
                                        <i class="fas fa-trash text-danger"></i> 삭제
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="supplier-contact">
                            <div class="contact-name"><?php echo $row['contact_person'] ?></div>
                            <div class="contact-info">
                                <i class="fas fa-phone-alt mr-1"></i> <?php echo $row['contact'] ?>
                            </div>
                            <?php if($row['email']): ?>
                                <div class="contact-info">
                                    <i class="fas fa-envelope mr-1"></i> <?php echo $row['email'] ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="project-status">
                            <?php if($project_count > 0): ?>
                                <span class="status-badge <?php echo $progress == 100 ? '' : 'active' ?>">
                            <?php echo $project_count ?>개 프로젝트
                        </span>
                                <?php if($latest_project): ?>
                                    <small class="text-muted" style="font-size: 13px;">
                                        <?php echo strlen($latest_project['project_name']) > 20 ?
                                            substr($latest_project['project_name'], 0, 20).'...' :
                                            $latest_project['project_name'] ?>
                                    </small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted" style="font-size: 14px;">
                            <i class="fas fa-info-circle"></i> 진행중인 프로젝트 없음
                        </span>
                            <?php endif; ?>
                        </div>

                        <?php if($latest_project && $latest_project['total_docs'] > 0): ?>
                            <div class="progress-section">
                                <div class="progress-label">
                                    <span>서류 제출 현황</span>
                                    <span style="font-weight: 600; color: #1f2937;">
                            <?php echo $latest_project['submitted_docs'] ?>/<?php echo $latest_project['total_docs'] ?>
                        </span>
                                </div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar-fill" style="width: <?php echo $progress ?>%"></div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="quick-actions">
                            <button class="quick-action-btn" onclick="viewProjects(<?php echo $row['id'] ?>)">
                                <i class="fas fa-eye"></i>
                                상세보기
                            </button>
                            <button class="quick-action-btn" onclick="editSupplier(<?php echo $row['id'] ?>)">
                                <i class="fas fa-pen"></i>
                                수정
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="empty-state">
                    <i class="fas fa-building"></i>
                    <h3>등록된 의뢰처가 없습니다</h3>
                    <p>새로운 의뢰처를 등록하여 프로젝트를 시작하세요.</p>
                    <button class="btn btn-primary" onclick="$('#create_new').click()">
                        <i class="fas fa-plus mr-2"></i>첫 의뢰처 등록하기
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- 의뢰처 리스트 뷰 -->
    <div id="supplierList" style="display: none;">
        <?php if($qry->num_rows > 0): ?>
            <div class="list-view-container">
                <table class="list-view-table">
                    <thead>
                    <tr>
                        <th>의뢰처명</th>
                        <th>담당자</th>
                        <th>연락처</th>
                        <th>프로젝트</th>
                        <th>진행률</th>
                        <th style="width: 100px;">작업</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $qry->data_seek(0); // 쿼리 결과를 처음으로 되돌림
                    while($row = $qry->fetch_assoc()):
                        // 해당 의뢰처의 공사 현황 조회
                        $projects = $conn->query("SELECT 
                            dr.*,
                            (SELECT COUNT(*) FROM request_documents WHERE request_id = dr.id) as total_docs,
                            (SELECT COUNT(*) FROM request_documents WHERE request_id = dr.id AND status = 1) as submitted_docs
                            FROM document_requests dr 
                            WHERE dr.supplier_id = '{$row['id']}' 
                            ORDER BY dr.date_created DESC");

                        $project_count = $projects->num_rows;
                        $latest_project = $projects->fetch_assoc();
                        $progress = 0;
                        if($latest_project && $latest_project['total_docs'] > 0) {
                            $progress = round(($latest_project['submitted_docs'] / $latest_project['total_docs']) * 100);
                        }

                        // 필터 데이터 속성 설정
                        $filter_status = 'no-project';
                        if($project_count > 0) {
                            $filter_status = $progress == 100 ? 'completed' : 'active';
                        }
                        ?>
                        <tr class="supplier-list-item"
                            data-id="<?php echo $row['id'] ?>"
                            data-filter="<?php echo $filter_status ?>"
                            data-name="<?php echo strtolower($row['name']) ?>"
                            data-contact="<?php echo strtolower($row['contact_person']) ?>"
                            onclick="viewProjects(<?php echo $row['id'] ?>)">
                            <td>
                                <div class="list-company-info">
                                    <div class="list-company-avatar">
                                        <?php echo substr($row['name'], 0, 1) ?>
                                    </div>
                                    <div>
                                        <div class="list-company-name"><?php echo $row['name'] ?></div>
                                        <div class="list-company-date">등록일: <?php echo date("Y.m.d", strtotime($row['date_created'])) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo $row['contact_person'] ?></td>
                            <td>
                                <?php echo $row['contact'] ?>
                                <?php if($row['email']): ?>
                                    <br><small class="text-muted"><?php echo $row['email'] ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($project_count > 0): ?>
                                    <span class="status-badge <?php echo $progress == 100 ? '' : 'active' ?>">
                                    <?php echo $project_count ?>개
                                </span>
                                <?php else: ?>
                                    <span class="text-muted">없음</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($latest_project && $latest_project['total_docs'] > 0): ?>
                                    <div class="d-flex align-items-center">
                                        <div class="progress-bar-container mr-2" style="width: 60px;">
                                            <div class="progress-bar-fill" style="width: <?php echo $progress ?>%"></div>
                                        </div>
                                        <span style="font-size: 13px;"><?php echo $progress ?>%</span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td onclick="event.stopPropagation();" style="position: relative;">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="dropdownMenuButton<?php echo $row['id'] ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton<?php echo $row['id'] ?>" style="position: absolute; will-change: transform;">
                                        <a class="dropdown-item" href="javascript:void(0)" onclick="event.stopPropagation(); viewProjects(<?php echo $row['id'] ?>)">
                                            <i class="fas fa-folder-open text-info"></i> 공사 목록
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="javascript:void(0)" onclick="event.stopPropagation(); editSupplier(<?php echo $row['id'] ?>)">
                                            <i class="fas fa-edit text-success"></i> 정보수정
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="javascript:void(0)" onclick="event.stopPropagation(); deleteSupplier(<?php echo $row['id'] ?>)">
                                            <i class="fas fa-trash text-danger"></i> 삭제
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-building"></i>
                <h3>등록된 의뢰처가 없습니다</h3>
                <p>새로운 의뢰처를 등록하여 프로젝트를 시작하세요.</p>
                <button class="btn btn-primary" onclick="$('#create_new').click()">
                    <i class="fas fa-plus mr-2"></i>첫 의뢰처 등록하기
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    $(document).ready(function(){
        // 새 의뢰처 등록
        $('#create_new').click(function(){
            uni_modal("<i class='fa fa-plus'></i> 새 의뢰처 등록","suppliers/manage_supplier.php")
        });

        // 드롭다운 메뉴 초기화 (리스트 뷰를 위해)
        $('.dropdown-toggle').dropdown();

        // 검색 기능
        $('#searchInput').on('keyup', function() {
            var searchText = $(this).val().toLowerCase();
            var activeFilter = $('.filter-btn.active').data('filter');
            var currentView = $('.view-mode-btn.active').data('view');

            if(currentView === 'grid') {
                $('.supplier-item').each(function() {
                    var name = $(this).data('name');
                    var contact = $(this).data('contact');
                    var filter = $(this).data('filter');

                    var matchesSearch = name.includes(searchText) || contact.includes(searchText);
                    var matchesFilter = activeFilter === 'all' || filter === activeFilter;

                    if(matchesSearch && matchesFilter) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            } else {
                $('.supplier-list-item').each(function() {
                    var name = $(this).data('name');
                    var contact = $(this).data('contact');
                    var filter = $(this).data('filter');

                    var matchesSearch = name.includes(searchText) || contact.includes(searchText);
                    var matchesFilter = activeFilter === 'all' || filter === activeFilter;

                    if(matchesSearch && matchesFilter) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
        });

        // 필터 기능
        $('.filter-btn').click(function() {
            $('.filter-btn').removeClass('active');
            $(this).addClass('active');

            var filter = $(this).data('filter');
            var searchText = $('#searchInput').val().toLowerCase();
            var currentView = $('.view-mode-btn.active').data('view');

            if(currentView === 'grid') {
                $('.supplier-item').each(function() {
                    var itemFilter = $(this).data('filter');
                    var name = $(this).data('name');
                    var contact = $(this).data('contact');

                    var matchesFilter = filter === 'all' || itemFilter === filter;
                    var matchesSearch = searchText === '' || name.includes(searchText) || contact.includes(searchText);

                    if(matchesFilter && matchesSearch) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            } else {
                $('.supplier-list-item').each(function() {
                    var itemFilter = $(this).data('filter');
                    var name = $(this).data('name');
                    var contact = $(this).data('contact');

                    var matchesFilter = filter === 'all' || itemFilter === filter;
                    var matchesSearch = searchText === '' || name.includes(searchText) || contact.includes(searchText);

                    if(matchesFilter && matchesSearch) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
        });

        // 보기 모드 전환
        $('.view-mode-btn').click(function() {
            $('.view-mode-btn').removeClass('active');
            $(this).addClass('active');

            var viewMode = $(this).data('view');

            if(viewMode === 'grid') {
                $('#supplierGrid').show();
                $('#supplierList').hide();
            } else {
                $('#supplierGrid').hide();
                $('#supplierList').show();
            }

            // 현재 필터와 검색어 적용
            var activeFilter = $('.filter-btn.active').data('filter');
            var searchText = $('#searchInput').val().toLowerCase();

            if(viewMode === 'list') {
                $('.supplier-list-item').each(function() {
                    var itemFilter = $(this).data('filter');
                    var name = $(this).data('name');
                    var contact = $(this).data('contact');

                    var matchesFilter = activeFilter === 'all' || itemFilter === activeFilter;
                    var matchesSearch = searchText === '' || name.includes(searchText) || contact.includes(searchText);

                    if(matchesFilter && matchesSearch) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
        });

        // 카드 클릭 이벤트
        $('.supplier-card').click(function(e) {
            // 드롭다운 버튼이나 빠른 액션 버튼 클릭시 이벤트 전파 중지
            if($(e.target).closest('.supplier-actions, .quick-actions').length) {
                return;
            }

            var supplierId = $(this).closest('.supplier-item').data('id');
            viewProjects(supplierId);
        });
    });

    function viewProjects(supplier_id){
        window.location.href = '?page=suppliers/view_projects&id=' + supplier_id;
    }

    function editSupplier(supplier_id){
        uni_modal("<i class='fa fa-edit'></i> 의뢰처 정보 수정","suppliers/manage_supplier.php?id="+supplier_id)
    }

    function deleteSupplier(supplier_id){
        _conf("정말로 이 의뢰처를 영구적으로 삭제하시겠습니까?","delete_supplier_exec",[supplier_id])
    }

    function delete_supplier_exec($id){
        start_loader();
        $.ajax({
            url:_base_url_+"classes/Master.php?f=delete_supplier",
            method:"POST",
            data:{id: $id},
            dataType:"json",
            error:err=>{
                console.log(err)
                alert_toast("오류가 발생했습니다.",'error');
                end_loader();
            },
            success:function(resp){
                if(typeof resp== 'object' && resp.status == 'success'){
                    location.reload();
                }else{
                    alert_toast("오류가 발생했습니다.",'error');
                    end_loader();
                }
            }
        })
    }
</script>
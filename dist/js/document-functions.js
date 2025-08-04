// dist/js/document-functions.js
// 서류 관리 시스템 공통 JavaScript 함수

/**
 * 파일 다운로드 함수
 * @param {number} docId - 문서 ID
 */
function downloadFile(docId) {
    if (!docId) {
        alert('문서 ID가 없습니다.');
        return;
    }

    // view_file.php를 통해 다운로드
    window.open(_base_url_ + 'admin/upload_portal/view_file.php?id=' + docId, '_blank');
}

/**
 * 파일 경로로 직접 다운로드
 * @param {string} filePath - 파일 경로
 */
function downloadFilePath(filePath) {
    if (!filePath) {
        alert('파일 경로가 없습니다.');
        return;
    }

    // 절대 경로인 경우 그대로 사용, 상대 경로인 경우 base_url 추가
    const downloadUrl = filePath.startsWith('http') ? filePath : _base_url_ + filePath;
    window.open(downloadUrl, '_blank');
}

/**
 * 파일 미리보기 함수 (이제는 다운로드로 통합)
 * @param {number} docId - 문서 ID
 */
function previewFile(docId) {
    // 다운로드로 통합
    downloadFile(docId);
}

/**
 * 파일 경로로 미리보기 (이제는 다운로드로 통합)
 * @param {string} filePath - 파일 경로
 */
function previewFilePath(filePath) {
    // 다운로드로 통합
    downloadFilePath(filePath);
}

/**
 * 업로드된 파일 목록 보기
 * @param {number} documentId - 문서 ID
 */
function viewUploads(documentId) {
    if (!documentId) {
        alert('문서 ID가 없습니다.');
        return;
    }

    // AJAX로 업로드된 파일 목록 가져오기
    $.ajax({
        url: _base_url_ + 'admin/document_requests/get_uploads.php',
        method: 'GET',
        data: { document_id: documentId },
        success: function(response) {
            $('#uploadModalBody').html(response);
            $('#uploadModal').modal('show');
        },
        error: function(xhr, status, error) {
            console.error('Error loading uploads:', error);
            alert('파일 목록을 불러오는데 실패했습니다.');
        }
    });
}

/**
 * 서류 요청 상세보기
 * @param {number} requestId - 요청 ID
 */
function viewRequestDetail(requestId) {
    if (!requestId) {
        alert('요청 ID가 없습니다.');
        return;
    }

    location.href = _base_url_ + 'admin/?page=document_requests/view_request&id=' + requestId;
}

/**
 * 업로드 링크 복사
 * @param {string} link - 복사할 링크
 */
function copyUploadLink(link) {
    if (!link) {
        // 현재 페이지에서 링크 찾기
        const uploadToken = $('[data-upload-token]').data('upload-token');
        if (uploadToken) {
            link = _base_url_ + 'admin/upload_portal/?token=' + uploadToken;
        } else {
            alert('복사할 링크가 없습니다.');
            return;
        }
    }

    // 임시 텍스트 영역 생성
    const textarea = document.createElement('textarea');
    textarea.value = link;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);

    // 선택 및 복사
    textarea.select();
    textarea.setSelectionRange(0, 99999); // 모바일 대응

    try {
        document.execCommand('copy');
        alert_toast('링크가 복사되었습니다!', 'success');
    } catch (err) {
        alert('링크 복사에 실패했습니다.');
    }

    // 임시 요소 제거
    document.body.removeChild(textarea);
}

/**
 * 리마인더 이메일 전송
 * @param {number} requestId - 요청 ID
 */
function sendReminder(requestId) {
    if (!requestId) {
        alert('요청 ID가 없습니다.');
        return;
    }

    _conf("미제출 서류에 대한 리마인더를 발송하시겠습니까?", "send_reminder_ajax", [requestId]);
}

/**
 * 리마인더 전송 AJAX
 * @param {number} requestId - 요청 ID
 */
function send_reminder_ajax(requestId) {
    start_loader();
    $.ajax({
        url: _base_url_ + "classes/Master.php?f=send_reminder",
        method: "POST",
        data: { request_id: requestId },
        dataType: "json",
        error: function(err) {
            console.error('Reminder error:', err);
            alert_toast("오류가 발생했습니다.", 'error');
            end_loader();
        },
        success: function(resp) {
            if (resp.status == 'success') {
                alert_toast("리마인더가 발송되었습니다.", 'success');
            } else {
                alert_toast(resp.msg || "발송 중 오류가 발생했습니다.", 'error');
            }
            end_loader();
        }
    });
}

/**
 * 전체 파일 다운로드
 * @param {number} requestId - 요청 ID
 */
function downloadAll(requestId) {
    if (!requestId) {
        alert('요청 ID가 없습니다.');
        return;
    }

    start_loader();
    window.location.href = _base_url_ + "classes/Master.php?f=download_all&request_id=" + requestId;
    setTimeout(function() {
        end_loader();
    }, 2000);
}

/**
 * 엑셀 내보내기
 * @param {number} requestId - 요청 ID
 */
function exportExcel(requestId) {
    if (!requestId) {
        alert('요청 ID가 없습니다.');
        return;
    }

    start_loader();
    window.location.href = _base_url_ + "classes/Master.php?f=export_excel&request_id=" + requestId;
    setTimeout(function() {
        end_loader();
    }, 2000);
}

/**
 * 의뢰처 수정 모달
 * @param {number} supplierId - 의뢰처 ID
 */
function editSupplier(supplierId) {
    if (!supplierId) {
        alert('의뢰처 ID가 없습니다.');
        return;
    }

    uni_modal("<i class='fa fa-edit'></i> 의뢰처 정보 수정", "suppliers/manage_supplier.php?id=" + supplierId);
}

/**
 * 새 서류 요청 생성
 * @param {number} supplierId - 의뢰처 ID
 */
function createNewRequest(supplierId) {
    let url = _base_url_ + 'admin/?page=document_requests/manage_request';
    if (supplierId) {
        url += '&supplier_id=' + supplierId;
    }
    location.href = url;
}

/**
 * 문서 삭제
 * @param {number} docId - 문서 ID
 */
function deleteDocument(docId) {
    if (!docId) {
        alert('문서 ID가 없습니다.');
        return;
    }

    _conf("정말로 이 문서를 삭제하시겠습니까?", "delete_document_ajax", [docId]);
}

/**
 * 문서 삭제 AJAX
 * @param {number} docId - 문서 ID
 */
function delete_document_ajax(docId) {
    start_loader();
    $.ajax({
        url: _base_url_ + "classes/Master.php?f=delete_document",
        method: "POST",
        data: { id: docId },
        dataType: "json",
        error: function(err) {
            console.error('Delete error:', err);
            alert_toast("삭제 중 오류가 발생했습니다.", 'error');
            end_loader();
        },
        success: function(resp) {
            if (resp.status == 'success') {
                alert_toast("문서가 삭제되었습니다.", 'success');
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                alert_toast(resp.msg || "삭제에 실패했습니다.", 'error');
                end_loader();
            }
        }
    });
}

// 페이지 로드 시 초기화
$(document).ready(function() {
    // 업로드 모달이 없는 경우 생성
    if ($('#uploadModal').length === 0) {
        $('body').append(`
            <div class="modal fade" id="uploadModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">업로드된 파일</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body" id="uploadModalBody">
                            <!-- 파일 목록이 여기에 표시됩니다 -->
                        </div>
                    </div>
                </div>
            </div>
        `);
    }
});
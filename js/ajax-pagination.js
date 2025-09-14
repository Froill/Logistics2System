// ajax-pagination.js
// Generic AJAX pagination logic for modal content replacement
// Usage: ajaxPaginateModal(modalId, urlBase, pageParam, pageNum)

// Usage: ajaxPaginateModal(modalId, urlBase, pageParam, pageNum, contentSelector)
function ajaxPaginateModal(modalId, urlBase, pageParam, pageNum, contentSelector) {
    var modal = document.getElementById(modalId);
    var xhr = new XMLHttpRequest();
    var url = urlBase + (urlBase.includes('?') ? '&' : '?') + pageParam + '=' + pageNum + '&ajax_cost_log=1';
    xhr.open('GET', url, true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onload = function() {
        if (xhr.status === 200) {
            var temp = document.createElement('div');
            temp.innerHTML = xhr.responseText;
            var selector = contentSelector || '.modal-box';
            var newBox = temp.querySelector(selector);
            if (newBox) {
                modal.querySelector(selector).innerHTML = newBox.innerHTML;
            }
        }
    };
    xhr.send();
}

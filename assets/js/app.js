/**
 * app.js - Namespace APP dùng chung toàn hệ thống.
 * Yêu cầu: jQuery 3.7, biến toàn cục APP_BASE và CSRF_TOKEN (khai báo ở layouts/header.php).
 */
var APP = (function ($) {
    'use strict';

    /* ============ ICON (SVG inline, đồng bộ với IconHelper::svg) ============ */
    var ICONS = {
        check:    '<path d="M20 6 9 17l-5-5"/>',
        x:        '<path d="M18 6 6 18M6 6l12 12"/>',
        alert:    '<path d="M12 9v4M12 17h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>',
        info:     '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
        inbox:    '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
        prev:     '<path d="m15 18-6-6 6-6"/>',
        next:     '<path d="m9 18 6-6-6-6"/>',
        // Hành động trong bảng — đồng bộ tên với IconHelper::PATHS
        'pencil':     '<path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/>',
        'trash':      '<path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/>',
        'rotate-ccw': '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>',
        'key':        '<circle cx="7.5" cy="15.5" r="4.5"/><path d="m21 2-9.6 9.6M15.5 7.5l3 3"/>',
        'eye':        '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
        'plus':       '<path d="M12 5v14M5 12h14"/>',
        'save':       '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/>',
        'lock':       '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'shield-check': '<path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V6l8-3 8 3z"/><path d="m9 12 2 2 4-4"/>',
        'users':      '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'user':       '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'layout-list':'<rect x="3" y="4" width="7" height="7" rx="1"/><rect x="3" y="13" width="7" height="7" rx="1"/><path d="M14 6h7M14 11h7M14 16h7M14 21h7"/>',
        'file-clock': '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><circle cx="12" cy="15" r="3"/><path d="M12 14v1.5l1 .5"/>',
        // --- Nghiệp vụ báo giá (đồng bộ IconHelper::PATHS) ---
        'clipboard-list':   '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M8 11h8M8 15h6"/>',
        'package':          '<path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/>',
        'file-spreadsheet': '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h8M12 13v4"/>',
        'qr-code':          '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM19 19h2v2h-2zM14 19h1M19 14h2"/>',
        'download':         '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5M12 15V3"/>',
        'upload':           '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5M12 3v12"/>',
        'check-circle':     '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
        'x-circle':         '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/>',
        'building':         '<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 6h.01M15 6h.01M9 10h.01M15 10h.01M9 14h.01M15 14h.01"/><path d="M10 22v-4h4v4"/>',
        'calendar':         '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 11h18"/>',
        'clock':            '<circle cx="12" cy="12" r="10"/><path d="M12 7v5l3 2"/>',
        'link':             '<path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.8 1.8"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.8-1.8"/>',
        'copy':             '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
        'printer':          '<path d="M6 9V3h12v6"/><rect x="6" y="13" width="12" height="8"/><path d="M6 17H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/>',
        'bar-chart':        '<path d="M3 3v18h18"/><rect x="7" y="12" width="3" height="6"/><rect x="12" y="8" width="3" height="10"/><rect x="17" y="4" width="3" height="14"/>',
        'send':             '<path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/>',
        'external-link':    '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6M10 14 21 3"/>',
        'alert-triangle':   '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4M12 17h.01"/>'
    };

    function icon(name, size, cls) {
        var body = ICONS[name];
        if (!body) return '';
        return '<svg class="icon ' + (cls || '') + '" width="' + (size || 16) + '" height="' + (size || 16) +
            '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" ' +
            'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' + body + '</svg>';
    }

    /* ============ UTILITIES ============ */
    function escapeHtml(text) {
        if (text === null || typeof text === 'undefined') return '';
        var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, function (m) { return map[m]; });
    }

    function debounce(func, wait) {
        var timeout;
        return function () {
            var ctx = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function () { func.apply(ctx, args); }, wait);
        };
    }

    /** Parse 'YYYY-MM-DD HH:ii:ss' (MySQL) an toàn trên mọi trình duyệt */
    function parseDate(ts) {
        if (!ts) return null;
        if (ts instanceof Date) return ts;
        var m = String(ts).match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?/);
        if (m) {
            return new Date(+m[1], +m[2] - 1, +m[3], +(m[4] || 0), +(m[5] || 0), +(m[6] || 0));
        }
        var d = new Date(ts);
        return isNaN(d.getTime()) ? null : d;
    }

    function pad2(n) { return (n < 10 ? '0' : '') + n; }

    function formatDate(ts) {
        var d = parseDate(ts);
        if (!d) return '';
        return pad2(d.getDate()) + '/' + pad2(d.getMonth() + 1) + '/' + d.getFullYear();
    }

    function formatDateTime(ts) {
        var d = parseDate(ts);
        if (!d) return '';
        return formatDate(d) + ' ' + pad2(d.getHours()) + ':' + pad2(d.getMinutes());
    }

    /* ============ TOAST ============ */
    function ensureToastContainer() {
        var c = document.querySelector('.toast-container');
        if (!c) {
            c = document.createElement('div');
            c.className = 'toast-container';
            c.setAttribute('role', 'status');
            c.setAttribute('aria-live', 'polite');
            document.body.appendChild(c);
        }
        return c;
    }

    function toast(message, type) {
        type = type || 'info';
        var iconName = { success: 'check', error: 'x', warning: 'alert', info: 'info' }[type] || 'info';
        var el = document.createElement('div');
        el.className = 'toast ' + type;
        el.innerHTML = '<span class="toast-icon">' + icon(iconName, 18) + '</span>' +
                       '<span class="toast-msg">' + escapeHtml(message) + '</span>';
        ensureToastContainer().appendChild(el);
        setTimeout(function () {
            el.classList.add('leaving');
            setTimeout(function () { el.remove(); }, 300);
        }, 3500);
    }

    /* ============ CONFIRM DIALOG ============ */
    function confirmDialog(message, onYes, opts) {
        opts = opts || {};
        var title = opts.title || 'Xác nhận';
        var yesText = opts.yesText || 'Đồng ý';
        var noText = opts.noText || 'Hủy';
        var yesClass = opts.yesClass || 'btn-danger';

        var bd = document.createElement('div');
        bd.className = 'modal-backdrop open';
        bd.innerHTML =
            '<div class="modal-content" style="max-width:440px" role="dialog" aria-modal="true">' +
                '<div class="modal-header"><h3>' + escapeHtml(title) + '</h3>' +
                    '<button class="close" type="button" aria-label="Đóng">' + icon('x', 20) + '</button>' +
                '</div>' +
                '<div class="modal-body">' + escapeHtml(message) + '</div>' +
                '<div class="modal-footer">' +
                    '<button class="btn btn-secondary" data-a="no">' + escapeHtml(noText) + '</button>' +
                    '<button class="btn ' + yesClass + '" data-a="yes">' + escapeHtml(yesText) + '</button>' +
                '</div>' +
            '</div>';
        document.body.appendChild(bd);

        function close() {
            document.removeEventListener('keydown', onKey);
            bd.remove();
        }
        function onKey(e) { if (e.key === 'Escape') close(); }

        bd.querySelector('[data-a=yes]').onclick = function () { close(); if (onYes) onYes(); };
        bd.querySelector('[data-a=no]').onclick = close;
        bd.querySelector('.close').onclick = close;
        bd.addEventListener('click', function (e) { if (e.target === bd) close(); });
        document.addEventListener('keydown', onKey);
        bd.querySelector('[data-a=yes]').focus();
    }

    /* ============ AJAX ============ */
    function loginUrl() {
        var base = (typeof window.APP_BASE !== 'undefined' && window.APP_BASE) ? window.APP_BASE : '/';
        return base + 'GUI/auth/login.php';
    }

    /**
     * Wrapper $.ajax: tự gắn CSRF header, tự xử lý lỗi 401/403/419.
     * options.success chỉ chạy khi payload có success = true.
     */
    function ajax(url, data, options) {
        options = options || {};
        var headers = $.extend({}, options.headers || {});
        if (typeof window.CSRF_TOKEN !== 'undefined' && window.CSRF_TOKEN) {
            headers['X-CSRF-Token'] = window.CSRF_TOKEN;
        }

        return $.ajax({
            url: url,
            type: options.type || 'POST',
            dataType: 'json',
            data: data,
            headers: headers
        }).done(function (res) {
            if (res && res.success === false) {
                // Server trả 200 nhưng nghiệp vụ thất bại
                toast(res.message || 'Thao tác không thành công', 'error');
                if (options.error) options.error(res);
                return;
            }
            if (options.success) options.success(res);
        }).fail(function (xhr) {
            var msg = 'Lỗi kết nối máy chủ';
            var res = null;
            try {
                res = JSON.parse(xhr.responseText);
                if (res && res.message) msg = res.message;
            } catch (e) { /* giữ msg mặc định */ }

            // Hết hạn phiên: server gắn cờ csrf_expired (kèm 403) hoặc trả 401.
            // Không dùng mã 419 nữa vì Apache biến nó thành 500 và mất body JSON.
            if (xhr.status === 401 || (res && res.csrf_expired)) {
                toast(msg + ' Đang chuyển về trang đăng nhập...', 'warning');
                setTimeout(function () { location.href = loginUrl(); }, 1500);
                return;
            }
            toast(msg, 'error');
            if (options.error) options.error(res, xhr);
        }).always(function () {
            if (options.complete) options.complete();
        });
    }

    /* ============ LOADING ============ */
    function showLoading(selector) {
        var $el = $(selector || 'body');
        if (!$el.length) return;
        if (!$el.find('> .loading-overlay').length) {
            if ($el.css('position') === 'static') $el.css('position', 'relative');
            $el.append('<div class="loading-overlay"><span class="spinner"></span></div>');
        }
        $el.find('> .loading-overlay').addClass('show');
    }

    function hideLoading(selector) {
        $(selector || 'body').find('> .loading-overlay').removeClass('show');
    }

    /* ============ PAGINATION ============ */
    /**
     * Render UI phân trang. Chấp nhận payload từ ResponseHelper::paged():
     *   { currentPage, pageSize, totalRecords, totalPages }
     */
    function renderPagination(info) {
        if (!info) return '';
        var cur = parseInt(info.currentPage || info.page || 1, 10);
        var totalPages = parseInt(info.totalPages || 0, 10);
        var totalRecords = parseInt(info.totalRecords || 0, 10);

        if (totalRecords === 0) return '<div class="pagination-info">Không có bản ghi nào</div>';

        var html = '<div class="pagination-info">Tổng <strong>' + totalRecords + '</strong> bản ghi' +
                   (totalPages > 1 ? ' · Trang ' + cur + '/' + totalPages : '') + '</div>';
        if (totalPages <= 1) return html;

        html += '<div class="pagination">';
        html += '<a href="#" data-page="' + (cur - 1) + '" class="page-nav' + (cur <= 1 ? ' disabled' : '') + '" aria-label="Trang trước">' + icon('prev', 16) + '</a>';

        var start = Math.max(1, cur - 2);
        var end = Math.min(totalPages, cur + 2);
        if (start > 1) {
            html += '<a href="#" data-page="1">1</a>';
            if (start > 2) html += '<span class="page-gap">…</span>';
        }
        for (var i = start; i <= end; i++) {
            html += '<a href="#" data-page="' + i + '"' + (i === cur ? ' class="active"' : '') + '>' + i + '</a>';
        }
        if (end < totalPages) {
            if (end < totalPages - 1) html += '<span class="page-gap">…</span>';
            html += '<a href="#" data-page="' + totalPages + '">' + totalPages + '</a>';
        }

        html += '<a href="#" data-page="' + (cur + 1) + '" class="page-nav' + (cur >= totalPages ? ' disabled' : '') + '" aria-label="Trang sau">' + icon('next', 16) + '</a>';
        html += '</div>';
        return html;
    }

    /**
     * Gắn handler click phân trang 1 lần cho 1 container.
     * onGo(page) được gọi khi user chọn trang hợp lệ.
     */
    function bindPagination(container, onGo) {
        $(document).on('click', container + ' .pagination a', function (e) {
            e.preventDefault();
            var $a = $(this);
            if ($a.hasClass('disabled') || $a.hasClass('active')) return;
            var p = parseInt($a.data('page'), 10);
            if (p > 0) onGo(p);
        });
    }

    /* ============ FORM ============ */
    function serializeForm(selector) {
        var data = {};
        $(selector).find('input, select, textarea').each(function () {
            var $el = $(this);
            var name = $el.attr('name');
            if (!name) return;
            var type = ($el.attr('type') || '').toLowerCase();
            if (type === 'checkbox') {
                data[name] = $el.is(':checked') ? 1 : 0;
            } else if (type === 'radio') {
                if ($el.is(':checked')) data[name] = $el.val();
            } else {
                data[name] = $el.val();
            }
        });
        return data;
    }

    /** Render trạng thái rỗng cho tbody */
    function emptyRow(colspan, message) {
        return '<tr class="empty-row"><td colspan="' + colspan + '">' +
               '<div class="empty-state">' + icon('inbox', 32) +
               '<p>' + escapeHtml(message || 'Chưa có dữ liệu') + '</p></div></td></tr>';
    }

    /**
     * Skeleton loader khớp hình dạng bảng — thay cho spinner tròn chung chung.
     * Do dài ngắn xen kẽ để trông tự nhiên, không đều tăm tắp.
     */
    function skeletonRows(colspan, rows) {
        rows = rows || 5;
        var widths = ['40%', '75%', '55%', '68%', '35%', '80%', '48%'];
        var html = '';
        for (var r = 0; r < rows; r++) {
            html += '<tr class="skeleton-row">';
            for (var c = 0; c < colspan; c++) {
                html += '<td><span class="skeleton-bar" style="width:' +
                        widths[(r + c) % widths.length] + '"></span></td>';
            }
            html += '</tr>';
        }
        return html;
    }

    /**
     * Báo lỗi ngay dưới input thay vì alert() hoặc chỉ toast.
     * setFieldError('#tai_khoan', 'Tài khoản đã tồn tại') — truyền '' để xóa lỗi.
     */
    function setFieldError(selector, message) {
        var $el = $(selector);
        if (!$el.length) return;
        var $group = $el.closest('.form-group');
        var $err = $group.find('.field-error');
        if (!$err.length) {
            $err = $('<div class="field-error"></div>');
            $group.append($err);
        }
        if (message) {
            $el.addClass('is-invalid').attr('aria-invalid', 'true');
            $err.text(message).addClass('show');
        } else {
            $el.removeClass('is-invalid').removeAttr('aria-invalid');
            $err.text('').removeClass('show');
        }
    }

    /** Xóa toàn bộ lỗi inline trong 1 form */
    function clearFieldErrors(formSelector) {
        $(formSelector).find('.is-invalid').removeClass('is-invalid').removeAttr('aria-invalid');
        $(formSelector).find('.field-error').text('').removeClass('show');
    }

    return {
        toast: toast,
        confirm: confirmDialog,
        ajax: ajax,
        showLoading: showLoading,
        hideLoading: hideLoading,
        escape: escapeHtml,
        debounce: debounce,
        formatDate: formatDate,
        formatDateTime: formatDateTime,
        renderPagination: renderPagination,
        bindPagination: bindPagination,
        serializeForm: serializeForm,
        emptyRow: emptyRow,
        skeletonRows: skeletonRows,
        setFieldError: setFieldError,
        clearFieldErrors: clearFieldErrors,
        icon: icon
    };
})(jQuery);

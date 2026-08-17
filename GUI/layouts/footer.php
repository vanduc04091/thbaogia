        </main> <!-- end content -->

        <footer class="app-footer">
            © <?= date('Y') ?> · <?= Helper::h(AppConfig::APP_NAME) ?> · v<?= Helper::h(AppConfig::APP_VERSION) ?>
        </footer>
    </div> <!-- end main -->
</div> <!-- end app-wrapper -->

<script>
(function ($) {
    'use strict';
    var MOBILE = 1024;
    var $body = $('body');
    var $scrim = $('#sidebarScrim');

    function isMobile() { return window.innerWidth <= MOBILE; }

    // Khôi phục trạng thái sidebar (chỉ áp dụng cho desktop)
    if (!isMobile() && localStorage.getItem('sidebarCollapsed') === '1') {
        $body.addClass('sidebar-collapsed');
    }

    $('#sidebarToggle').on('click', function () {
        if (isMobile()) {
            var open = $body.toggleClass('sidebar-open').hasClass('sidebar-open');
            $scrim.prop('hidden', !open);
        } else {
            var collapsed = $body.toggleClass('sidebar-collapsed').hasClass('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0');
        }
    });

    $scrim.on('click', function () {
        $body.removeClass('sidebar-open');
        $scrim.prop('hidden', true);
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $body.hasClass('sidebar-open')) {
            $body.removeClass('sidebar-open');
            $scrim.prop('hidden', true);
        }
    });

    // Đổi kích thước: dọn trạng thái mobile khi quay lại desktop
    $(window).on('resize', APP.debounce(function () {
        if (!isMobile() && $body.hasClass('sidebar-open')) {
            $body.removeClass('sidebar-open');
            $scrim.prop('hidden', true);
        }
    }, 150));
})(jQuery);
</script>
</body>
</html>

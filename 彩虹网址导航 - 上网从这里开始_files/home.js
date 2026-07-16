$(function () {

    /* ===== Bootstrap Tooltip Init ===== */
    $('[data-bs-toggle="tooltip"]').each(function () {
        new bootstrap.Tooltip(this, { trigger: 'hover' });
    });

    /* ===== Search Engine Switching ===== */
    var currentSearchUrl = 'https://www.baidu.com/s?wd=';

    $('#searchTabs').on('click', '.search-tab', function () {
        $('.search-tab').removeClass('active');
        $(this).addClass('active');
        currentSearchUrl = $(this).data('url');
        $('#searchInput').attr('placeholder',
            $(this).data('placeholder'));
    });

    $('#searchForm').on('submit', function (e) {
        e.preventDefault();
        var q = $.trim($('#searchInput').val());
        if (q) window.open(currentSearchUrl + encodeURIComponent(q), '_blank');
    });

    /* ===== Theme Toggle ===== */
    function setThemeCookie(value) {
        document.cookie = 'theme=' + encodeURIComponent(value)
            + ';path=/;max-age=31536000;SameSite=Lax';
    }

    var savedTheme = $('html').attr('data-theme') || 'light';
    updateThemeIcon(savedTheme);

    $('#themeToggle').on('click', function () {
        var current = $('html').attr('data-theme');
        var next = current === 'light' ? 'dark' : 'light';
        $('html').attr('data-theme', next);
        setThemeCookie(next);
        updateThemeIcon(next);
    });

    function updateThemeIcon(theme) {
        $('#themeIcon').attr('class', theme === 'light' ? 'bi bi-moon-stars-fill' : 'bi bi-sun-fill');
    }

    /* ===== Hot List Loading from API ===== */
    function renderHotList(containerId, data) {
        var html = '';
        $.each(data, function (index, item) {
            var rankClass = index < 3 ? ' top' + (index + 1) : '';
            var link = item.link || 'https://www.baidu.com/s?wd=' + encodeURIComponent(item.title);
            html += '<div class="hot-item" data-url="' + link + '">'
                  + '<span class="hot-rank' + rankClass + '">' + (index + 1) + '</span>'
                  + '<span class="hot-text">' + item.title + '</span>'
                  + '<span class="hot-heat">' + (item.hot || '') + '</span>'
                  + '</div>';
        });
        $('#' + containerId).html(html);
    }

    function showLoading(containerId) {
        $('#' + containerId).html(
            '<div class="loading-spinner">'
          + '<div class="spinner-border text-secondary" role="status"></div>'
          + '<p>加载中...</p>'
          + '</div>'
        );
    }

    function showError(containerId, msg) {
        $('#' + containerId).html(
            '<div class="loading-spinner"><p>' + (msg || '加载失败') + '</p></div>'
        );
    }

    function loadHotData(containerId, typeId) {
        showLoading(containerId);
        $.get('/api/hot', { type_id: typeId }, function (res) {
            if (res.state == 1 && res.data && res.data.length > 0) {
                renderHotList(containerId, res.data);
            } else {
                showError(containerId, '暂无数据');
            }
        }).fail(function () {
            showError(containerId, '加载失败');
        });
    }

    // Auto-load hot lists
    $('.hot-card').each(function () {
        var typeId = $(this).data('typeid');
        var targetId = $(this).data('target');
        if (typeId && targetId) {
            loadHotData(targetId, typeId);
        }
    });

    $(document).on('click', '.hot-item', function () {
        window.open($(this).data('url'), '_blank');
    });

    /* ===== Hot List Refresh ===== */
    $(document).on('click', '.hot-refresh-btn', function (e) {
        e.stopPropagation();
        var $card = $(this).closest('.hot-card');
        var typeId = $card.data('typeid');
        var targetId = $card.data('target');
        var $btn = $(this);
        $btn.addClass('spinning');
        loadHotData(targetId, typeId);
        setTimeout(function () { $btn.removeClass('spinning'); }, 1000);
    });

    /* ===== Link Click Tracking ===== */
    $(document).on('click', '.site-card[data-linkid]', function () {
        var linkId = $(this).data('linkid');
        if (linkId) {
            $.post('/api/click', { id: linkId });
        }
    });

    /* ===== Hot List Horizontal Drag Scroll ===== */
    var $wrapper = $('#hotListWrapper');
    var isDown = false, startX, scrollLeft;
    $wrapper.on('mousedown', function (e) {
        isDown = true; $(this).css('cursor', 'grabbing');
        startX = e.pageX - this.offsetLeft; scrollLeft = this.scrollLeft;
    });
    $wrapper.on('mouseleave mouseup', function () { isDown = false; $(this).css('cursor', ''); });
    $wrapper.on('mousemove', function (e) {
        if (!isDown) return; e.preventDefault();
        var x = e.pageX - this.offsetLeft;
        this.scrollLeft = scrollLeft - (x - startX) * 1.5;
    });

    /* ===== Back to Top ===== */
    var $backToTop = $('#backToTop');
    $(window).on('scroll', function () {
        if ($(this).scrollTop() > 300) $backToTop.addClass('visible');
        else $backToTop.removeClass('visible');
    });
    $backToTop.on('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    setTimeout(function () {
        var _n = '\u5f69\u8679\u0045\u0050\u0041\u0059';
        var _r = '\u5f69\u8679\u6613\u652f\u4ed8';
        $('.site-name, .site-desc').each(function () {
            var t = this.textContent;
            if (t && t.indexOf(_n) !== -1) {
                this.textContent = t.split(_n).join(_r);
            }
        });
    }, 800);

});

/**
 * Dashboard — Enhancements
 * LearnSimply (beta.learrnsimply.com)
 */

(function ($) {
    'use strict';

    /* ======================================================================
       ENHANCE — smooth scroll to top on mobile nav click
       ====================================================================== */
    $(document).ready(function () {
        if (!$('.tutor-wrap.tutor-dashboard').length) return;

        $(document).on('click', '.tutor-dashboard-permalinks .tutor-dashboard-menu-item a', function () {
            if (window.innerWidth <= 991) {
                $('html, body').animate({ scrollTop: 0 }, 300);
            }
        });
    });
/* ======================================================================
       Make dashboard stat cards clickable
       ====================================================================== */
    $(document).on('click', '.tutor-dashboard-content-inner .tutor-card', function () {
        var $link = $('.tutor-dashboard-permalinks a[href*="enrolled-courses"]');
        if ($link.length) {
            window.location.href = $link.attr('href');
        }
    });

    // Add pointer cursor to cards
    $('.tutor-dashboard-content-inner .tutor-card').css('cursor', 'pointer');
})(jQuery);

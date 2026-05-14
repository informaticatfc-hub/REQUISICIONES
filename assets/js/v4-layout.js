/* =====================================================================
   THE FUENTES CORPORATION — LAYOUT v4.1
   Archivo: assets/js/v4-layout.js
   Funciones:
     - Theme toggle (claro / oscuro) con persistencia
     - Mobile nav (offcanvas) con backdrop
     - Command palette (Ctrl + K / Cmd + K)
     - Cierre por Escape
     - Auto-cierre al cambiar a desktop
   No depende de jQuery. Compatible con Bootstrap 5.3.
   ===================================================================== */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var html = document.documentElement;

        // ---------------- THEME TOGGLE ----------------
        var toggle = document.getElementById('tfThemeToggle');
        var stored = localStorage.getItem('tf_theme');
        var prefDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        html.setAttribute('data-bs-theme', stored || (prefDark ? 'dark' : 'light'));

        function syncThemeIcon() {
            if (!toggle) return;
            var icon = toggle.querySelector('i');
            if (!icon) return;
            icon.className = html.getAttribute('data-bs-theme') === 'dark'
                ? 'bi bi-sun-fill'
                : 'bi bi-moon-stars-fill';
        }
        syncThemeIcon();

        if (toggle) {
            toggle.addEventListener('click', function () {
                var next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
                html.setAttribute('data-bs-theme', next);
                localStorage.setItem('tf_theme', next);
                syncThemeIcon();
            });
        }

        // ---------------- MOBILE NAV ----------------
        var burger = document.getElementById('tfBurger');
        var mNav   = document.getElementById('tfMobileNav');
        var mBack  = document.getElementById('tfMobileBackdrop');
        var mClose = document.getElementById('tfMobileClose');

        function openNav() {
            if (!mNav || !mBack) return;
            mNav.classList.add('show');
            mBack.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        function closeNav() {
            if (!mNav || !mBack) return;
            mNav.classList.remove('show');
            mBack.classList.remove('show');
            document.body.style.overflow = '';
        }

        if (burger) burger.addEventListener('click', openNav);
        if (mClose) mClose.addEventListener('click', closeNav);
        if (mBack)  mBack.addEventListener('click', closeNav);

        // ---------------- COMMAND PALETTE ----------------
        var cmd     = document.getElementById('tfCmd');
        var openBtn = document.getElementById('tfOpenCmd');

        function openCmd() {
            if (!cmd) return;
            cmd.classList.add('show');
            setTimeout(function () {
                var input = cmd.querySelector('input');
                if (input) input.focus();
            }, 30);
        }
        function closeCmd() {
            if (!cmd) return;
            cmd.classList.remove('show');
        }

        if (openBtn) openBtn.addEventListener('click', openCmd);
        if (cmd) {
            cmd.addEventListener('click', function (e) {
                if (e.target === cmd) closeCmd();
            });
        }

        // ---------------- KEYBOARD ----------------
        document.addEventListener('keydown', function (e) {
            // Ctrl + K / Cmd + K
            if ((e.ctrlKey || e.metaKey) && e.key && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                openCmd();
            }
            // Esc cierra todo
            if (e.key === 'Escape') {
                closeCmd();
                closeNav();
            }
        });

        // ---------------- RESPONSIVE AUTO-CLOSE ----------------
        if (window.matchMedia) {
            var mqDesktop = window.matchMedia('(min-width: 1200px)');
            function onMQ() { if (mqDesktop.matches) closeNav(); }
            if (mqDesktop.addEventListener) {
                mqDesktop.addEventListener('change', onMQ);
            } else if (mqDesktop.addListener) {
                mqDesktop.addListener(onMQ);
            }
        }

        // ---------------- EXPONER API GLOBAL ----------------
        window.TfLayout = {
            openCmd: openCmd,
            closeCmd: closeCmd,
            openNav: openNav,
            closeNav: closeNav,
            setTheme: function (t) {
                html.setAttribute('data-bs-theme', t === 'dark' ? 'dark' : 'light');
                localStorage.setItem('tf_theme', t);
                syncThemeIcon();
            }
        };
    });
})();

/* =====================================================================
   THE FUENTES CORPORATION — LAYOUT v4.1
   Archivo: assets/js/v4-layout.js
   Funciones:
     - Theme toggle (claro / oscuro) con persistencia
     - Mobile nav (offcanvas) con backdrop
     - Command palette (Ctrl + K / Cmd + K)
     - Cierre por Escape
     - Auto-cierre al cambiar a desktop
     - Carga del dropdown "Obras" del topbar (lazy on first open)
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

        // ---------------- DROPDOWN OBRAS (TOPBAR) ----------------
        // Pobla #tfObrasMenuList con las obras ACTIVO la primera vez que se
        // abre el dropdown (lazy). Usa fetch nativo (no depende de axios)
        // para que funcione aunque la pagina no haya incluido axios.
        var obrasMenuTrigger = document.querySelector('#tfObrasMenu')
            ? document.querySelector('#tfObrasMenu').parentNode.querySelector('[data-bs-toggle="dropdown"]')
            : null;
        var obrasMenuList = document.getElementById('tfObrasMenuList');
        var obrasLoaded = false;
        var obrasLoading = false;

        function escHtml(s) {
            if (s === null || s === undefined) return '';
            return String(s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function renderObrasEmpty(msg) {
            if (!obrasMenuList) return;
            obrasMenuList.innerHTML =
                '<div class="px-3 py-2 small text-muted">' + escHtml(msg) + '</div>';
        }

        function renderObrasList(obras) {
            if (!obrasMenuList) return;
            if (!obras || !obras.length) {
                renderObrasEmpty('No hay obras activas.');
                return;
            }
            // Resolver ruta a obras.php segun la pagina actual
            // (las paginas viven en /pages/* asi que el href relativo es ./obras.php)
            var base = (window.location.pathname.indexOf('/pages/') !== -1) ? './' : './pages/';
            var html = '';
            for (var i = 0; i < obras.length; i++) {
                var o = obras[i];
                var id = o.obras_id != null ? o.obras_id : '';
                var nom = o.obras_nombre || ('Obra #' + id);
                html += '<a class="dropdown-item rounded py-2 px-3"'
                     + ' href="' + escHtml(base) + 'obras.php?obra=' + encodeURIComponent(id) + '">'
                     + '<i class="bi bi-building text-primary me-2"></i>'
                     + '<span>' + escHtml(nom) + '</span>'
                     + '</a>';
            }
            obrasMenuList.innerHTML = html;
        }

        function loadObrasMenu() {
            if (obrasLoaded || obrasLoading || !obrasMenuList) return;
            obrasLoading = true;
            renderObrasEmpty('Cargando...');

            // Resolver path al endpoint segun la pagina actual
            var apiPath = (window.location.pathname.indexOf('/pages/') !== -1)
                ? '../api/crud_index.php'
                : './api/crud_index.php';

            var headers = { 'Content-Type': 'application/json' };
            if (window.TF_CONTEXT && window.TF_CONTEXT.csrf) {
                headers['X-CSRF-Token'] = window.TF_CONTEXT.csrf;
            }

            fetch(apiPath, {
                method: 'POST',
                credentials: 'same-origin',
                headers: headers,
                body: JSON.stringify({ accion: 2, modo: 'todas' })
            }).then(function (resp) {
                if (!resp.ok) throw new Error('HTTP ' + resp.status);
                return resp.json();
            }).then(function (data) {
                obrasLoaded = true;
                renderObrasList(Array.isArray(data) ? data : []);
            }).catch(function (err) {
                console.error('No se pudieron cargar las obras del topbar:', err);
                renderObrasEmpty('No se pudieron cargar las obras.');
            }).finally(function () {
                obrasLoading = false;
            });
        }

        if (obrasMenuTrigger) {
            // Carga al primer click (lazy) — antes de que Bootstrap muestre el menu
            obrasMenuTrigger.addEventListener('click', loadObrasMenu, { once: false });
            // Tambien escuchamos el evento de Bootstrap por si el menu se abre
            // de otra forma (teclado, programaticamente)
            var ddParent = obrasMenuTrigger.parentNode;
            if (ddParent) {
                ddParent.addEventListener('show.bs.dropdown', loadObrasMenu);
            }
        }

        // ---------------- EXPONER API GLOBAL ----------------
        window.TfLayout = {
            openCmd: openCmd,
            closeCmd: closeCmd,
            openNav: openNav,
            closeNav: closeNav,
            reloadObrasMenu: function () {
                obrasLoaded = false;
                loadObrasMenu();
            },
            setTheme: function (t) {
                html.setAttribute('data-bs-theme', t === 'dark' ? 'dark' : 'light');
                localStorage.setItem('tf_theme', t);
                syncThemeIcon();
            }
        };
    });
})();

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
        var cmdInput = document.getElementById('tfCmdInput');
        var cmdItems = [];
        var cmdActiveIndex = -1;

        function collectCmdItems() {
            if (!cmd) { cmdItems = []; return; }
            cmdItems = Array.prototype.slice.call(cmd.querySelectorAll('.tf-cmd-item'));
        }

        function visibleCmdItems() {
            return cmdItems.filter(function (item) {
                return item.style.display !== 'none';
            });
        }

        function setActiveCmdItem(index) {
            var visibles = visibleCmdItems();
            cmdItems.forEach(function (item) { item.classList.remove('active'); });
            if (!visibles.length) {
                cmdActiveIndex = -1;
                return;
            }
            var normalized = Math.max(0, Math.min(index, visibles.length - 1));
            cmdActiveIndex = normalized;
            var target = visibles[normalized];
            target.classList.add('active');
            if (typeof target.scrollIntoView === 'function') {
                target.scrollIntoView({ block: 'nearest' });
            }
        }

        function filterCmdItems(query) {
            var q = String(query || '').trim().toLowerCase();
            collectCmdItems();
            cmdItems.forEach(function (item) {
                var txt = (item.textContent || '').toLowerCase();
                item.style.display = (!q || txt.indexOf(q) !== -1) ? '' : 'none';
            });
            setActiveCmdItem(0);
        }

        function resetCmdState() {
            if (cmdInput) cmdInput.value = '';
            collectCmdItems();
            cmdItems.forEach(function (item) { item.style.display = ''; item.classList.remove('active'); });
            setActiveCmdItem(0);
        }

        function openCmd() {
            if (!cmd) return;
            cmd.classList.add('show');
            resetCmdState();
            setTimeout(function () {
                var input = cmd.querySelector('input');
                if (input) input.focus();
            }, 30);
        }
        function closeCmd() {
            if (!cmd) return;
            if (_cmdObrasMode) {
                _cmdObrasMode = false;
                _cleanCmdObrasUI();
                _obraSelDest = null;
            }
            cmd.classList.remove('show');
        }

        if (openBtn) openBtn.addEventListener('click', openCmd);
        if (cmd) {
            cmd.addEventListener('click', function (e) {
                if (e.target === cmd) closeCmd();
            });
        }

        if (cmdInput) {
            cmdInput.addEventListener('input', function (e) {
                filterCmdItems(e.target.value);
            });
            cmdInput.addEventListener('keydown', function (e) {
                var visibles = visibleCmdItems();
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (!visibles.length) return;
                    setActiveCmdItem((cmdActiveIndex + 1) % visibles.length);
                    return;
                }
                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (!visibles.length) return;
                    var next = cmdActiveIndex <= 0 ? visibles.length - 1 : cmdActiveIndex - 1;
                    setActiveCmdItem(next);
                    return;
                }
                if (e.key === 'Enter') {
                    if (cmdActiveIndex < 0 || !visibles.length) return;
                    e.preventDefault();
                    var active = visibles[cmdActiveIndex];
                    if (active && typeof active.click === 'function') active.click();
                }
            });
        }

        // ---------------- KEYBOARD ----------------
        document.addEventListener('keydown', function (e) {
            // Ctrl+K / Cmd+K abre buscador global
            if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
                e.preventDefault();
                openCmd();
                return;
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

        // ---------------- TfNav: navegacion con obra requerida ----------------
        var _obraSelDest = null; // destino pendiente mientras el usuario elige obra

        function _syncObraActiva(id, nombre) {
            if (!id) return null;
            var normalized = String(parseInt(id, 10) || '');
            if (!normalized) return null;
            try { localStorage.setItem('obraActiva', normalized); } catch (e) {}
            try { sessionStorage.setItem('obraActiva', normalized); } catch (e) {}
            if (nombre) {
                try { localStorage.setItem('obraActivaNombre', nombre); } catch (e) {}
                try { sessionStorage.setItem('obraActivaNombre', nombre); } catch (e) {}
            }
            return normalized;
        }

        function _getObraActivaNormalized() {
            var byUrl = null;
            try {
                byUrl = new URLSearchParams(window.location.search).get('obra');
            } catch (e) {}
            if (byUrl && _syncObraActiva(byUrl)) {
                return _syncObraActiva(byUrl);
            }

            var bySession = null;
            try { bySession = sessionStorage.getItem('obraActiva'); } catch (e) {}
            if (bySession && _syncObraActiva(bySession)) {
                return _syncObraActiva(bySession);
            }

            var byLocal = null;
            try { byLocal = localStorage.getItem('obraActiva'); } catch (e) {}
            if (byLocal && _syncObraActiva(byLocal)) {
                return _syncObraActiva(byLocal);
            }

            return null;
        }

        var _cmdOriginalHtml = null;

        function openObraSelector(destUrl) {
            _obraSelDest = destUrl || null;
            // En mobile, el selector debe mostrarse por encima del menu lateral.
            closeNav();
            var cmdEl = document.getElementById('tfCmd');
            var cmdList = document.getElementById('tfCmdList');
            if (!cmdEl || !cmdList) return;

            if (_cmdOriginalHtml === null) {
                _cmdOriginalHtml = cmdList.innerHTML;
            }

            cmdList.innerHTML = ''
                + '<div class="tf-cmd-section">Selecciona una obra</div>'
                + '<div id="tfCmdObrasList" class="tf-cmd-obras-list">'
                + '<div class="px-3 py-2 small text-muted">Cargando obras...</div>'
                + '</div>';

            _cmdObrasMode = true;
            _loadCmdObras();
            openCmd();
        }

        var _cmdObrasMode = false;
        var _cmdObrasList = null;

        function _loadCmdObras() {
            if (_cmdObrasList) { _renderCmdObras(_cmdObrasList); return; }
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
            }).then(function(r){ return r.json(); })
              .then(function(data){
                _cmdObrasList = Array.isArray(data) ? data : [];
                if (_cmdObrasMode) _renderCmdObras(_cmdObrasList);
              }).catch(function(){});
        }

        function _renderCmdObras(obras) {
            var cmdEl = document.getElementById('tfCmd');
            if (!cmdEl) return;
            var list = cmdEl.querySelector('#tfCmdObrasList');
            if (!list) return;
            list.innerHTML = '';
            if (!obras.length) {
                list.innerHTML = '<p style="color:var(--tf-text-muted);font-size:.85rem;text-align:center">Sin obras disponibles</p>';
                return;
            }
            obras.forEach(function(o) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'tf-cmd-item tf-cmd-obra-item';
                btn.innerHTML = ''
                    + '<i class="bi bi-building"></i>'
                    + '<div class="tf-cmd-item-text">'
                    + '<strong>' + escHtml(o.obras_nombre || ('Obra #' + o.obras_id)) + '</strong>'
                    + '<small>Seleccionar esta obra</small>'
                    + '</div>';
                btn.addEventListener('click', function() {
                    _syncObraActiva(o.obras_id, o.obras_nombre || '');
                    _cmdObrasMode = false;
                    _cleanCmdObrasUI();
                    closeCmd();
                    if (_obraSelDest) {
                        window.location.href = _obraSelDest;
                        _obraSelDest = null;
                    }
                });
                list.appendChild(btn);
            });
            collectCmdItems();
            setActiveCmdItem(0);
        }

        function _cleanCmdObrasUI() {
            var cmdList = document.getElementById('tfCmdList');
            if (!cmdList) return;
            if (_cmdOriginalHtml !== null) {
                cmdList.innerHTML = _cmdOriginalHtml;
            }
        }

        function _toggleRequiresObraUI() {
            var hasObra = !!_getObraActivaNormalized();
            var nodes = document.querySelectorAll('[data-requires-obra="1"]');
            for (var i = 0; i < nodes.length; i++) {
                nodes[i].style.display = hasObra ? '' : 'none';
            }
        }

        _toggleRequiresObraUI();

        window.TfNav = {
            goToWithObra: function(url, event) {
                if (event) event.preventDefault();
                var obraActiva = _getObraActivaNormalized();
                if (obraActiva) {
                    window.location.href = url;
                } else {
                    openObraSelector(url);
                }
            },
            openObraSelector: openObraSelector,
            setObraActiva: function(id, nombre) {
                _syncObraActiva(id, nombre);
                _toggleRequiresObraUI();
            },
            getObraActiva: function() {
                return _getObraActivaNormalized();
            }
        };

        // ---------------- D-M6: NOTIFICATION BADGE (polling 60 s) ----------------
        (function () {
            var btn   = document.getElementById('tfNotifBtn');
            var badge = document.getElementById('tfNotifBadge');
            if (!btn || !badge) return;

            var notifPath = (window.location.pathname.indexOf('/pages/') !== -1)
                ? '../api/crud_notifications.php'
                : './api/crud_notifications.php';

            function fetchNotifications() {
                fetch(notifPath, {
                    method: 'GET',
                    credentials: 'same-origin'
                })
                .then(function (resp) { return resp.ok ? resp.json() : null; })
                .then(function (data) {
                    if (!data || typeof data.total !== 'number') return;
                    var total = data.total;
                    btn.style.display = '';          // mostrar siempre que tengamos respuesta
                    if (total > 0) {
                        badge.textContent = total > 99 ? '99+' : String(total);
                        badge.style.display = '';
                        btn.setAttribute('aria-label', 'Notificaciones: ' + total + ' pendientes');
                    } else {
                        badge.style.display = 'none';
                        btn.setAttribute('aria-label', 'Notificaciones');
                    }
                })
                .catch(function () { /* silencioso */ });
            }

            // Click: redirige a la página más relevante según rol
            btn.addEventListener('click', function () {
                var roleCode = (window.TF_CONTEXT && window.TF_CONTEXT.user && window.TF_CONTEXT.user.roleCode) || '';
                var base = window.location.pathname.indexOf('/pages/') !== -1 ? '' : './pages/';
                if (roleCode === 'finanzas') {
                    window.location.href = base + 'all_presiones.php';
                } else {
                    window.location.href = base + 'all_presiones.php';
                }
            });

            fetchNotifications();                   // inmediato al cargar
            setInterval(fetchNotifications, 60000); // cada 60 s
        })();
    });
})();

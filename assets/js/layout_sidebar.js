(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var body = document.body;
        if (!body || !body.classList.contains('app-layout')) {
            return;
        }

        var sidebar = document.getElementById('sidebar');
        var navbarContainer = document.querySelector('.app-navbar .container-fluid');
        if (!sidebar || !navbarContainer) {
            return;
        }

        var links = Array.from(sidebar.querySelectorAll('#sideBarItem a.nav-link[href], #sidebar > .dropdown > a[href]'));
        if (!links.length) {
            return;
        }

        var unique = {};
        var actions = links
            .map(function (a) {
                var href = a.getAttribute('href') || '#';
                var label = (a.textContent || '').trim();
                return { href: href, label: label || 'Accion' };
            })
            .filter(function (item) {
                var key = item.href + '|' + item.label;
                if (unique[key]) return false;
                unique[key] = true;
                return true;
            });

        if (!actions.length) {
            return;
        }

        var wrap = document.createElement('div');
        wrap.className = 'dropdown me-2';

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'sidebar-toggle-btn';
        btn.setAttribute('data-bs-toggle', 'dropdown');
        btn.setAttribute('aria-expanded', 'false');
        btn.setAttribute('aria-label', 'Abrir acciones');
        btn.innerHTML = '<span class="sidebar-toggle-icon" aria-hidden="true"><span class="sidebar-toggle-line"></span><span class="sidebar-toggle-line"></span><span class="sidebar-toggle-line"></span></span><span class="sidebar-toggle-label">Acciones</span>';

        var menu = document.createElement('div');
        menu.className = 'dropdown-menu shadow';
        menu.style.minWidth = '220px';

        actions.forEach(function (item) {
            var a = document.createElement('a');
            a.className = 'dropdown-item';
            a.href = item.href;
            a.textContent = item.label;
            menu.appendChild(a);
        });

        wrap.appendChild(btn);
        wrap.appendChild(menu);
        navbarContainer.insertBefore(wrap, navbarContainer.firstChild);
    });
})();

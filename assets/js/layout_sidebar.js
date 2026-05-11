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

        var storageKey = 'tf_sidebar_collapsed';
        var storedState = localStorage.getItem(storageKey);
        var isMobile = window.matchMedia('(max-width: 767px)').matches;
        var shouldCollapse = storedState === null ? isMobile : storedState === 'true';

        var toggleButton = document.createElement('button');
        toggleButton.type = 'button';
        toggleButton.className = 'sidebar-toggle-btn';
        toggleButton.setAttribute('aria-label', 'Mostrar u ocultar menu lateral');

        var burger = document.createElement('span');
        burger.className = 'sidebar-toggle-icon';
        burger.setAttribute('aria-hidden', 'true');

        for (var i = 0; i < 3; i++) {
            var line = document.createElement('span');
            line.className = 'sidebar-toggle-line';
            burger.appendChild(line);
        }

        var label = document.createElement('span');
        label.className = 'sidebar-toggle-label';
        label.textContent = 'Menu';

        toggleButton.appendChild(burger);
        toggleButton.appendChild(label);
        navbarContainer.insertBefore(toggleButton, navbarContainer.firstChild);

        var backdrop = document.createElement('button');
        backdrop.type = 'button';
        backdrop.className = 'sidebar-backdrop';
        backdrop.setAttribute('aria-label', 'Cerrar menu lateral');
        body.appendChild(backdrop);

        function isMobileViewport() {
            return window.matchMedia('(max-width: 767px)').matches;
        }

        function applyState(collapsed) {
            body.classList.toggle('sidebar-collapsed', collapsed);
            toggleButton.setAttribute('aria-expanded', (!collapsed).toString());
            toggleButton.classList.toggle('is-collapsed', collapsed);
            if (isMobileViewport()) {
                backdrop.classList.toggle('is-visible', !collapsed);
            } else {
                backdrop.classList.remove('is-visible');
            }
        }

        function setCollapsed(collapsed, persist) {
            if (persist) {
                localStorage.setItem(storageKey, collapsed ? 'true' : 'false');
            }
            applyState(collapsed);
        }

        applyState(shouldCollapse);

        toggleButton.addEventListener('click', function () {
            var collapsed = !body.classList.contains('sidebar-collapsed');
            setCollapsed(collapsed, true);
        });

        backdrop.addEventListener('click', function () {
            setCollapsed(true, true);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !body.classList.contains('sidebar-collapsed')) {
                setCollapsed(true, true);
            }
        });

        window.addEventListener('resize', function () {
            if (!isMobileViewport()) {
                backdrop.classList.remove('is-visible');
            } else {
                backdrop.classList.toggle('is-visible', !body.classList.contains('sidebar-collapsed'));
            }
        });
    });
})();

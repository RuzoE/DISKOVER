/**
 * Interacciones del layout: apertura/cierre de la barra lateral en móvil
 * y menú de usuario de la barra superior.
 */
export function initShell(root = document) {
    initSidebar(root);
    initUserMenu(root);
}

function initSidebar(root) {
    const sidebar = root.querySelector('#app-sidebar');
    const toggle = root.querySelector('[data-sidebar-toggle]');
    const backdrop = root.querySelector('[data-sidebar-backdrop]');
    if (!sidebar || !toggle) {
        return;
    }

    const open = () => {
        sidebar.classList.add('is-open');
        if (backdrop) backdrop.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
    };

    const close = () => {
        sidebar.classList.remove('is-open');
        if (backdrop) backdrop.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
    };

    toggle.addEventListener('click', () => {
        sidebar.classList.contains('is-open') ? close() : open();
    });

    backdrop?.addEventListener('click', close);
    root.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') close();
    });
    sidebar.querySelectorAll('a').forEach((link) => link.addEventListener('click', close));
}

function initUserMenu(root) {
    const menu = root.querySelector('[data-user-menu]');
    const trigger = menu?.querySelector('[data-user-menu-trigger]');
    const panel = menu?.querySelector('[data-user-menu-panel]');
    if (!menu || !trigger || !panel) {
        return;
    }

    const setOpen = (isOpen) => {
        panel.hidden = !isOpen;
        trigger.setAttribute('aria-expanded', String(isOpen));
    };

    trigger.addEventListener('click', (event) => {
        event.stopPropagation();
        setOpen(panel.hidden);
    });

    root.addEventListener('click', (event) => {
        if (!menu.contains(event.target)) setOpen(false);
    });

    root.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setOpen(false);
    });
}

(() => {
    const nav = document.getElementById('nav');
    const button = document.getElementById('mb');
    if (!nav || !button) return;
    const menus = [...nav.querySelectorAll('[data-site-menu]')];
    function closeMenus(except = null) {
        menus.forEach(menu => {
            if (menu !== except) {
                menu.open = false;
                menu.querySelector('summary').setAttribute('aria-expanded', 'false');
            }
        });
    }
    window.closeSiteNavigation = () => {
        closeMenus();
        nav.classList.remove('show');
        button.setAttribute('aria-expanded', 'false');
        button.setAttribute('aria-label', 'Open menu');
    };
    menus.forEach(menu => menu.addEventListener('toggle', () => {
        menu.querySelector('summary').setAttribute('aria-expanded', String(menu.open));
        if (menu.open) closeMenus(menu);
    }));
    button.addEventListener('click', () => {
        const open = nav.classList.toggle('show');
        button.setAttribute('aria-expanded', String(open));
        button.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        if (!open) closeMenus();
    });
    document.addEventListener('click', event => {
        if (!event.target.closest('header')) window.closeSiteNavigation();
        if (event.target.closest('#nav a')) window.closeSiteNavigation();
    });
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        const openMenu = menus.find(menu => menu.open);
        if (openMenu) {
            closeMenus();
            openMenu.querySelector('summary').focus();
        } else if (nav.classList.contains('show')) {
            window.closeSiteNavigation();
            button.focus();
        }
    });
    matchMedia('(max-width: 1100px)').addEventListener('change', () => window.closeSiteNavigation());
})();

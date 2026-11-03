// The only JavaScript on the site:
// 1. open and close the menu on a phone;
// 2. ask "are you sure?" before a form with data-confirm is sent.
// Everything else (searching, filtering, saving) happens in PHP.

const menuButton = document.querySelector('.nav-toggle');
const siteNav = document.querySelector('.site-nav');

if (menuButton && siteNav) {
    menuButton.addEventListener('click', function () {
        const isOpen = siteNav.classList.toggle('is-open');
        menuButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            siteNav.classList.remove('is-open');
            menuButton.setAttribute('aria-expanded', 'false');
        }
    });

    siteNav.addEventListener('click', function (event) {
        if (event.target.closest('a')) {
            siteNav.classList.remove('is-open');
            menuButton.setAttribute('aria-expanded', 'false');
        }
    });
}

document.addEventListener('submit', function (event) {
    const message = event.target.dataset.confirm;

    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

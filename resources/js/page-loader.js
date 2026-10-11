/**
 * Shows the spinning-logo loader while the browser navigates to another page.
 * Opt a link or form out with the `data-no-loader` attribute.
 */
const SHOW_DELAY_MS = 150;

let showTimer = null;

const loader = () => document.getElementById('page-loader');

function showLoader() {
    clearTimeout(showTimer);

    showTimer = setTimeout(() => {
        const element = loader();

        if (!element) {
            return;
        }

        element.hidden = false;
        requestAnimationFrame(() => element.classList.remove('opacity-0'));
    }, SHOW_DELAY_MS);
}

function hideLoader() {
    clearTimeout(showTimer);

    const element = loader();

    if (element) {
        element.classList.add('opacity-0');
        element.hidden = true;
    }
}

function isPageNavigation(link, event) {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return false;
    }

    if (link.hasAttribute('download') || link.hasAttribute('data-no-loader') || (link.target && link.target !== '_self')) {
        return false;
    }

    const url = new URL(link.href, window.location.href);

    if (url.origin !== window.location.origin || !['http:', 'https:'].includes(url.protocol)) {
        return false;
    }

    // Same-page anchors don't load a new page.
    return !(url.hash && url.pathname === window.location.pathname && url.search === window.location.search);
}

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');

    if (link && isPageNavigation(link, event)) {
        showLoader();
    }
});

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!event.defaultPrevented && !form.hasAttribute('data-no-loader') && !(form.target && form.target !== '_self')) {
        showLoader();
    }
});

// Hide again when the page is restored from the back/forward cache.
window.addEventListener('pageshow', hideLoader);

const states = new WeakMap();
const activeButtons = new Set();
let pendingButton = null;

function resolvePendingButton() {
    return pendingButton && document.contains(pendingButton) ? pendingButton : null;
}

function start(button) {
    if (!(button instanceof HTMLButtonElement)) return null;

    const current = states.get(button);
    if (current) {
        current.requests += 1;
        return button;
    }

    const indicator = document.createElement('span');
    indicator.className = 'button-loading-indicator';
    indicator.setAttribute('aria-hidden', 'true');
    indicator.style.color = getComputedStyle(button).color;
    indicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    states.set(button, {
        requests: 1,
        wasDisabled: button.disabled,
        previousAriaBusy: button.getAttribute('aria-busy'),
        indicator,
    });
    activeButtons.add(button);
    button.appendChild(indicator);
    button.classList.add('is-button-loading');
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');

    return button;
}

function stop(button, force = false) {
    const state = states.get(button);
    if (!state) return;

    state.requests -= 1;
    if (!force && state.requests > 0) return;

    state.indicator.remove();
    button.classList.remove('is-button-loading');
    button.disabled = state.wasDisabled;

    if (state.previousAriaBusy === null) button.removeAttribute('aria-busy');
    else button.setAttribute('aria-busy', state.previousAriaBusy);

    states.delete(button);
    activeButtons.delete(button);
}

function trackPendingAction() {
    document.addEventListener('click', event => {
        if (event.target.closest('.swal2-container')) return;

        const button = event.target.closest('button');
        const shouldIgnore = !button
            || button.matches('[data-loading-ignore]')
            || button.closest('.searchable-select');

        pendingButton = shouldIgnore ? null : button;
    }, true);

    document.addEventListener('submit', event => {
        pendingButton = event.submitter instanceof HTMLButtonElement
            ? event.submitter
            : event.target.querySelector('button[type="submit"]');
    }, true);

    document.addEventListener('submit', event => {
        const button = resolvePendingButton();
        queueMicrotask(() => {
            if (!event.defaultPrevented && button) start(button);
        });
    });
}

function interceptFetch() {
    const nativeFetch = window.fetch.bind(window);

    window.fetch = (...args) => {
        const button = start(resolvePendingButton());

        try {
            return nativeFetch(...args).finally(() => {
                if (button) stop(button);
                if (pendingButton === button) pendingButton = null;
            });
        } catch (error) {
            if (button) stop(button);
            if (pendingButton === button) pendingButton = null;
            throw error;
        }
    };
}

function interceptAxios() {
    if (!window.axios) return;

    window.axios.interceptors.request.use(config => {
        config.buttonLoadingTarget = start(resolvePendingButton());
        return config;
    });

    const stopRequest = config => {
        const button = config?.buttonLoadingTarget;
        if (button) stop(button);
        if (pendingButton === button) pendingButton = null;
    };

    window.axios.interceptors.response.use(response => {
        stopRequest(response.config);
        return response;
    }, error => {
        stopRequest(error.config);
        return Promise.reject(error);
    });
}

export function installButtonLoading() {
    window.ButtonLoading = {
        start,
        stop,
        current: () => pendingButton,
        pulseCurrent(duration = 1800) {
            const button = start(pendingButton);
            if (button) window.setTimeout(() => stop(button), duration);
        },
    };

    trackPendingAction();
    interceptFetch();
    interceptAxios();

    window.addEventListener('pageshow', () => {
        activeButtons.forEach(button => stop(button, true));
    });
}

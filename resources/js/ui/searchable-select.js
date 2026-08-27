const SELECTOR = 'select:not([multiple]):not([data-native-select="true"]):not(.swal2-select)';

function createElements(select) {
    const wrapper = document.createElement('div');
    wrapper.className = 'searchable-select';

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'searchable-select-trigger';
    trigger.innerHTML = '<span class="searchable-select-trigger-label"></span><i class="fas fa-chevron-down searchable-select-chevron" aria-hidden="true"></i>';

    const panel = document.createElement('div');
    panel.className = 'searchable-select-panel';
    panel.hidden = true;

    const searchWrap = document.createElement('div');
    searchWrap.className = 'searchable-select-search-wrap';
    searchWrap.innerHTML = '<i class="fas fa-search" aria-hidden="true"></i>';

    const search = document.createElement('input');
    search.type = 'search';
    search.autocomplete = 'off';
    search.className = 'searchable-select-input';
    search.placeholder = select.dataset.searchPlaceholder || 'Cari pilihan...';

    const results = document.createElement('div');
    results.className = 'searchable-select-results';

    searchWrap.appendChild(search);
    panel.append(searchWrap, results);
    select.parentNode.insertBefore(wrapper, select);
    wrapper.append(select, trigger, panel);

    return { wrapper, trigger, panel, search, results };
}

function enhanceSelect(select) {
    if (select.dataset.searchableReady === 'true' || select.closest('.swal2-popup')) return;

    select.dataset.searchableReady = 'true';
    select.required = false;
    select.tabIndex = -1;
    select.setAttribute('aria-hidden', 'true');
    select.style.setProperty('display', 'none', 'important');

    const { wrapper, trigger, panel, search, results } = createElements(select);
    let activeIndex = -1;

    const options = () => Array.from(select.options).filter(option => !option.disabled);
    const syncTrigger = () => {
        trigger.querySelector('.searchable-select-trigger-label').textContent =
            select.selectedOptions[0]?.textContent?.trim() || 'Pilih data';
    };
    const close = () => {
        panel.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
        trigger.querySelector('.searchable-select-chevron').classList.remove('rotate-180');
        activeIndex = -1;
    };
    const choose = option => {
        select.value = option.value;
        select.dispatchEvent(new Event('input', { bubbles: true }));
        select.dispatchEvent(new Event('change', { bubbles: true }));
        syncTrigger();
        close();
        trigger.focus();
    };
    const render = (query = '') => {
        const needle = query.toLocaleLowerCase('id-ID').trim();
        const matches = options().filter(option =>
            option.textContent.toLocaleLowerCase('id-ID').includes(needle));

        results.replaceChildren();
        activeIndex = -1;

        if (matches.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'searchable-select-empty';
            empty.textContent = 'Pilihan tidak ditemukan';
            results.appendChild(empty);
            return;
        }

        matches.forEach((option, index) => {
            const button = document.createElement('button');
            const preview = option.dataset.preview || '';
            button.type = 'button';
            button.className = 'searchable-select-option';
            button.dataset.optionIndex = String(index);
            button.innerHTML = '<span class="searchable-select-option-label"></span><span class="searchable-select-option-preview"></span>';
            button.querySelector('.searchable-select-option-label').textContent = option.textContent.trim();
            button.querySelector('.searchable-select-option-preview').textContent = preview;
            button.querySelector('.searchable-select-option-preview').hidden = preview === '';
            button.classList.toggle('is-selected', option.selected);
            button.addEventListener('click', () => choose(option));
            results.appendChild(button);
        });
    };
    const open = () => {
        search.value = '';
        render();
        panel.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
        trigger.querySelector('.searchable-select-chevron').classList.add('rotate-180');
        requestAnimationFrame(() => search.focus());
    };

    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');
    trigger.addEventListener('click', () => panel.hidden ? open() : close());
    search.addEventListener('input', () => render(search.value));
    search.addEventListener('keydown', event => {
        const items = Array.from(results.querySelectorAll('.searchable-select-option'));

        if (event.key === 'Escape') {
            close();
            trigger.focus();
            return;
        }
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            activeIndex = event.key === 'ArrowDown'
                ? Math.min(activeIndex + 1, items.length - 1)
                : Math.max(activeIndex - 1, 0);
            items.forEach((item, index) => item.classList.toggle('is-active', index === activeIndex));
            items[activeIndex]?.scrollIntoView({ block: 'nearest' });
        }
        if (event.key === 'Enter' && activeIndex >= 0) {
            event.preventDefault();
            items[activeIndex]?.click();
        }
    });

    select.addEventListener('input', syncTrigger);
    select.addEventListener('change', syncTrigger);
    select.addEventListener('searchable-select:sync', syncTrigger);
    document.addEventListener('click', event => {
        syncTrigger();
        if (!wrapper.contains(event.target)) close();
    });
    new MutationObserver(() => {
        syncTrigger();
        if (!panel.hidden) render(search.value);
    }).observe(select, { childList: true, subtree: true, attributes: true });

    syncTrigger();
}

function enhanceAll(root) {
    if (root.matches?.(SELECTOR)) enhanceSelect(root);
    root.querySelectorAll?.(SELECTOR).forEach(enhanceSelect);
}

export function installSearchableSelects() {
    enhanceAll(document);
    new MutationObserver(mutations => {
        mutations.forEach(mutation => mutation.addedNodes.forEach(node => {
            if (node.nodeType === Node.ELEMENT_NODE) enhanceAll(node);
        }));
    }).observe(document.body, { childList: true, subtree: true });
}

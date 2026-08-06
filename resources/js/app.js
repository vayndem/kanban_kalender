import './bootstrap';

import Alpine from 'alpinejs';
import Sortable from 'sortablejs';

window.Alpine = Alpine;
window.Sortable = Sortable;

Alpine.start();

function enhanceSelect(select) {
    if (select.dataset.searchableReady === 'true' || select.multiple || select.dataset.nativeSelect === 'true') return;
    select.dataset.searchableReady = 'true';

    const wrapper = document.createElement('div');
    wrapper.className = 'searchable-select';
    select.parentNode.insertBefore(wrapper, select);
    wrapper.appendChild(select);
    select.classList.add('searchable-select-native');

    const input = document.createElement('input');
    input.type = 'search';
    input.autocomplete = 'off';
    input.className = 'searchable-select-input';
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-expanded', 'false');
    input.placeholder = select.dataset.searchPlaceholder || 'Cari dan pilih...';

    const icon = document.createElement('span');
    icon.className = 'searchable-select-icon';
    icon.innerHTML = '<i class="fas fa-chevron-down" aria-hidden="true"></i>';

    const panel = document.createElement('div');
    panel.className = 'searchable-select-panel';
    panel.hidden = true;

    wrapper.append(input, icon, panel);
    let activeIndex = -1;

    const options = () => Array.from(select.options).filter(option => !option.disabled);
    const selectedLabel = () => select.selectedOptions[0]?.textContent?.trim() || '';

    const close = () => {
        panel.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        input.value = selectedLabel();
        activeIndex = -1;
    };

    const choose = option => {
        select.value = option.value;
        input.value = option.textContent.trim();
        select.dispatchEvent(new Event('input', { bubbles: true }));
        select.dispatchEvent(new Event('change', { bubbles: true }));
        close();
    };

    const render = (search = '') => {
        const needle = search.toLocaleLowerCase('id-ID').trim();
        const matches = options().filter(option => option.textContent.toLocaleLowerCase('id-ID').includes(needle));
        panel.replaceChildren();
        activeIndex = -1;

        if (!matches.length) {
            const empty = document.createElement('div');
            empty.className = 'searchable-select-empty';
            empty.textContent = 'Tidak ada pilihan yang cocok';
            panel.appendChild(empty);
            return;
        }

        matches.forEach((option, index) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'searchable-select-option';
            button.dataset.optionIndex = String(index);
            button.innerHTML = `<span class="searchable-select-option-label"></span><span class="searchable-select-option-preview"></span>`;
            button.querySelector('.searchable-select-option-label').textContent = option.textContent.trim();
            button.querySelector('.searchable-select-option-preview').textContent = option.value ? `Nilai: ${option.value}` : 'Pilihan umum';
            if (option.selected) button.classList.add('is-selected');
            button.addEventListener('mousedown', event => event.preventDefault());
            button.addEventListener('click', () => choose(option));
            panel.appendChild(button);
        });
    };

    const open = () => {
        input.value = '';
        render();
        panel.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    };

    input.value = selectedLabel();
    input.addEventListener('focus', open);
    input.addEventListener('click', () => panel.hidden && open());
    input.addEventListener('input', () => {
        if (panel.hidden) panel.hidden = false;
        input.setAttribute('aria-expanded', 'true');
        render(input.value);
    });
    input.addEventListener('keydown', event => {
        const items = Array.from(panel.querySelectorAll('.searchable-select-option'));
        if (event.key === 'Escape') return close();
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

    select.addEventListener('change', () => { input.value = selectedLabel(); });
    document.addEventListener('click', event => { if (!wrapper.contains(event.target)) close(); });

    new MutationObserver(() => {
        if (document.activeElement !== input) input.value = selectedLabel();
        if (!panel.hidden) render(input.value);
    }).observe(select, { childList: true, subtree: true, attributes: true });
}

function enhanceAllSelects(root = document) {
    if (root.matches?.('select')) enhanceSelect(root);
    root.querySelectorAll?.('select').forEach(enhanceSelect);
}

enhanceAllSelects();
new MutationObserver(mutations => {
    mutations.forEach(mutation => mutation.addedNodes.forEach(node => {
        if (node.nodeType === Node.ELEMENT_NODE) enhanceAllSelects(node);
    }));
}).observe(document.body, { childList: true, subtree: true });

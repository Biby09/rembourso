/**
 * Pagination : boutons « première page », fenêtre de pages autour de la page courante, « dernière page ».
 */
import { html, setHtml } from '../core/dom.js';

/**
 * @param {HTMLElement} container
 * @param {{ current: number, total: number, onChange: (page: number) => void, windowSize?: number }} options
 */
export function renderPagination(container, { current, total, onChange, windowSize = 5 }) {
    if (!container) return;

    if (total <= 1) {
        container.innerHTML = '';
        return;
    }

    let start = Math.max(1, current - Math.floor(windowSize / 2));
    const end = Math.min(total, start + windowSize - 1);
    start = Math.max(1, end - windowSize + 1);

    const pages = Array.from({ length: end - start + 1 }, (_, i) => start + i);
    const button = (page, label, { active = false, disabled = false, title = '' } = {}) => html`
        <button type="button" class="btn btn-outline-primary btn-sm${active ? ' active' : ''}"
            data-page="${page}" ${disabled ? html`disabled` : ''} ${title ? html`title="${title}"` : ''}>${label}</button>`;

    setHtml(container, html`
        ${button(1, html`<i class="bi bi-chevron-double-left"></i>`, { disabled: current === 1, title: 'Première page' })}
        ${pages.map(page => button(page, page, { active: page === current }))}
        ${button(total, html`<i class="bi bi-chevron-double-right"></i>`, { disabled: current === total, title: 'Dernière page' })}`);

    container.onclick = event => {
        const target = event.target.closest('button[data-page]');
        if (target && !target.disabled) onChange(Number(target.dataset.page));
    };
}

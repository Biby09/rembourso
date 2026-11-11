/**
 * Helpers DOM et génération de HTML sûre.
 *
 * Le tag `html` échappe automatiquement toutes les valeurs interpolées,
 * sauf celles marquées comme sûres (résultat d'un autre `html` ou de `raw`).
 *
 *   html`<p>${repayment.label}</p>`        -> label échappé
 *   html`<ul>${items.map(i => html`<li>${i}</li>`)}</ul>`
 */

class SafeHtml {
    constructor(value) {
        this.value = value;
    }

    toString() {
        return this.value;
    }
}

const HTML_ESCAPES = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };

export function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => HTML_ESCAPES[char]);
}

/** Marque une chaîne comme HTML de confiance (à n'utiliser qu'avec du contenu maîtrisé). */
export function raw(value) {
    return new SafeHtml(String(value ?? ''));
}

function renderValue(value) {
    if (value instanceof SafeHtml) return value.value;
    if (Array.isArray(value)) return value.map(renderValue).join('');
    if (value === null || value === undefined || value === false) return '';
    return escapeHtml(value);
}

export function html(strings, ...values) {
    return new SafeHtml(strings.reduce((out, str, i) => out + str + (i < values.length ? renderValue(values[i]) : ''), ''));
}

/** Convertit du HTML (sûr) en élément DOM (premier élément racine). */
export function toElement(markup) {
    const template = document.createElement('template');
    template.innerHTML = renderValue(markup).trim();
    return template.content.firstElementChild;
}

/** Remplace le contenu d'un élément par du HTML (sûr). */
export function setHtml(element, markup) {
    if (element) element.innerHTML = renderValue(markup);
}

export const $ = (selector, root = document) => root.querySelector(selector);
export const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

/**
 * Écouteur délégué : `handler(event, matchedElement)` est appelé quand un
 * élément correspondant à `selector` (dans `root`) déclenche l'événement.
 */
export function delegate(root, eventName, selector, handler) {
    root?.addEventListener(eventName, event => {
        const target = event.target.closest(selector);
        if (target && root.contains(target)) handler(event, target);
    });
}

/** Lit un attribut data-* numérique. */
export function dataNumber(element, key) {
    const value = Number(element?.dataset[key]);
    return Number.isFinite(value) ? value : null;
}

/** Affiche ou masque un élément (classe Bootstrap d-none). */
export function setVisible(element, visible) {
    element?.classList.toggle('d-none', !visible);
}

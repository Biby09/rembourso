/**
 * Composants d'interface génériques : alertes, état de chargement des boutons, modals Bootstrap.
 */
import { html, setHtml } from './dom.js';

const ALERT_TYPES = ['success', 'danger', 'info', 'warning'];

/**
 * Affiche une alerte Bootstrap dans `container`.
 * `message` est échappé, sauf s'il provient de `html`/`raw`.
 */
export function showAlert(container, type, message, { timeout = 0, dismissible = false } = {}) {
    if (!container) return;

    const safeType = ALERT_TYPES.includes(type) ? type : 'info';
    setHtml(container, html`
        <div class="alert alert-${safeType} py-2 mb-2${dismissible ? ' alert-dismissible fade show' : ''}" role="alert">
            ${message}
            ${dismissible ? html`<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>` : ''}
        </div>`);

    if (timeout > 0) {
        clearTimeout(container._alertTimer);
        container._alertTimer = setTimeout(() => clearAlert(container), timeout);
    }
}

export function clearAlert(container) {
    if (container) container.innerHTML = '';
}

/** Désactive un bouton et affiche un spinner pendant une action. */
export function setButtonLoading(button, isLoading, loadingText = 'Chargement...') {
    if (!button) return;

    if (isLoading) {
        button.dataset.originalHtml ??= button.innerHTML;
        button.disabled = true;
        setHtml(button, html`<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>${loadingText}`);
        return;
    }

    button.disabled = false;
    if (button.dataset.originalHtml !== undefined) {
        button.innerHTML = button.dataset.originalHtml;
        delete button.dataset.originalHtml;
    }
}

/** Exécute `action` en affichant l'état de chargement sur `button`. */
export async function withButtonLoading(button, loadingText, action) {
    setButtonLoading(button, true, loadingText);
    try {
        return await action();
    } finally {
        setButtonLoading(button, false);
    }
}

export function getModal(element) {
    return element && window.bootstrap ? window.bootstrap.Modal.getOrCreateInstance(element) : null;
}

export function showModal(element) {
    getModal(element)?.show();
}

export function hideModal(element) {
    getModal(element)?.hide();
}

/** Demande confirmation à l'utilisateur. */
export function confirmAction(message) {
    return window.confirm(message);
}

/** Message bloquant simple (erreurs ou confirmations ponctuelles). */
export function notify(message) {
    window.alert(message);
}

/**
 * Envoi de formulaires en AJAX avec affichage du résultat dans un conteneur de message.
 *
 * Utilisation déclarative (voir initAjaxForms) :
 *   <form action="submit-login.php" data-ajax-form
 *         data-message-target="#formMessage"
 *         data-loading="Connexion en cours..."
 *         data-success="Vous êtes connecté !"
 *         data-error="Erreur de connexion.">
 *     <input type="password" id="password">
 *     <input type="password" data-must-match="#password" data-mismatch="Les mots de passe ne correspondent pas">
 *   </form>
 */
import { postForm } from '../core/http.js';
import { $, $$ } from '../core/dom.js';
import { showAlert } from '../core/ui.js';

function resolveElement(target) {
    return typeof target === 'string' ? $(target) : target ?? null;
}

/**
 * Envoie `form` à `url`. Affiche le chargement, puis le succès ou l'erreur
 * (message du serveur en priorité). Suit `redirect` si le serveur en renvoie un.
 *
 * @returns {Promise<object>} réponse JSON du serveur
 */
export async function submitForm(form, url, {
    messageContainer = null,
    loadingMessage = 'Envoi en cours...',
    successMessage = 'Succès',
    errorMessage = 'Une erreur est survenue.',
    resetOnSuccess = true,
    formData = null,
} = {}) {
    const container = resolveElement(messageContainer);
    const submitButtons = $$('button[type="submit"]', form);

    showAlert(container, 'info', loadingMessage);
    submitButtons.forEach(button => { button.disabled = true; });

    const result = await postForm(url, formData ?? form);

    submitButtons.forEach(button => { button.disabled = false; });

    if (result.success) {
        showAlert(container, 'success', successMessage);
        if (resetOnSuccess) form.reset();
        if (result.redirect) window.location.href = result.redirect;
    } else {
        showAlert(container, 'danger', result.message || result.error || errorMessage);
    }

    return result;
}

/**
 * Vérifie les champs `data-must-match` (ex. confirmation de mot de passe).
 * Retourne le message d'erreur du premier champ invalide, ou null.
 */
export function checkMatchingFields(form) {
    for (const field of $$('[data-must-match]', form)) {
        const reference = $(field.dataset.mustMatch, form);
        if (reference && reference.value !== field.value) {
            return field.dataset.mismatch || 'Les valeurs ne correspondent pas.';
        }
    }
    return null;
}

/** Active l'envoi AJAX sur tous les formulaires `form[data-ajax-form]` de la page. */
export function initAjaxForms(root = document) {
    $$('form[data-ajax-form]', root).forEach(form => {
        const { messageTarget, loading, success, error } = form.dataset;

        form.addEventListener('submit', async event => {
            event.preventDefault();

            const mismatch = checkMatchingFields(form);
            if (mismatch) {
                showAlert(resolveElement(messageTarget), 'danger', mismatch);
                return;
            }

            await submitForm(form, form.getAttribute('action'), {
                messageContainer: messageTarget,
                loadingMessage: loading,
                successMessage: success,
                errorMessage: error,
            });
        });
    });
}

/**
 * Bouton « Envoyer l'invitation » d'une personne sans compte : e-mail avec le lien pour créer son compte
 * (ou rattacher ses demandes à un compte existant), api/accountless/send-claim.php.
 * Utilisé par la page « À rembourser » (accountless.js) et la page « Membres » (member-management.js).
 */
import { api } from '../../core/api.js';
import { html } from '../../core/dom.js';
import { errorMessage } from '../../core/http.js';
import { notify, withButtonLoading } from '../../core/ui.js';

export const CLAIM_LABEL = "Envoyer l'invitation";
export const CLAIM_AGAIN_LABEL = "Renvoyer l'invitation";
const NO_EMAIL_TITLE = "Renseignez l'adresse e-mail de la personne pour lui envoyer l'invitation";

/**
 * HTML du bouton (data-action="send-claim") ; désactivé si la personne n'a pas d'e-mail.
 * alreadySent : une invitation a déjà été envoyée (« Renvoyer »).
 */
export function claimButtonHtml({ email, alreadySent = false, classes = 'btn btn-sm btn-outline-primary' }) {
    if (!email) {
        return html`<button type="button" class="${classes}" disabled title="${NO_EMAIL_TITLE}">${CLAIM_LABEL}</button>`;
    }
    return html`<button type="button" class="${classes}" data-action="send-claim">${alreadySent ? CLAIM_AGAIN_LABEL : CLAIM_LABEL}</button>`;
}

/**
 * Envoie l'invitation et affiche le résultat. Renvoie true si elle est partie.
 */
export function sendClaimInvitation(organisationId, personId, email, button) {
    return withButtonLoading(button, 'Envoi...', async () => {
        const result = await api.sendAccountClaim(organisationId, personId);
        if (!result.success) {
            notify(errorMessage(result, "Impossible d'envoyer l'invitation."));
            return false;
        }
        notify(`Invitation envoyée à ${email}.`);
        return true;
    });
}
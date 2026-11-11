/**
 * Réponse à une invitation à rejoindre une organisation.
 */
import { api } from '../core/api.js';
import { errorMessage } from '../core/http.js';
import { notify } from '../core/ui.js';

/**
 * Accepte ou refuse une invitation (par token ou par identifiant), puis redirige :
 * vers l'organisation si elle est acceptée, sinon vers le tableau de bord.
 */
export async function respondToInvitation({ accept, token = null, id = null }) {
    const result = await api.respondToInvitation({ accept, token, id });

    if (!result.success) {
        notify(errorMessage(result, "Une erreur est survenue lors de l'envoi de la réponse."));
        return result;
    }

    window.location.href = accept && result.organisation_id
        ? `/dashboard/organisation/?id=${encodeURIComponent(result.organisation_id)}`
        : '/dashboard/';
    return result;
}

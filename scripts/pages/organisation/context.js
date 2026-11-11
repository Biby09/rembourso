/**
 * Contexte des pages d'une organisation : lu sur <main id="organisation-page" data-organisation-id data-organisation-name>.
 * Partagé entre les modules d'une page (ex. le renommage met à jour organisationName).
 */
import { $ } from '../../core/dom.js';

export function organisationContext() {
    const page = $('#organisation-page');
    return {
        organisationId: Number(page.dataset.organisationId),
        organisationName: page.dataset.organisationName,
    };
}

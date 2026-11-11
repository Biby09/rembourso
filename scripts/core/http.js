/**
 * Requêtes vers le serveur. Ajoute automatiquement l'en-tête CSRF
 * (jeton lu dans <meta name="csrf-token">), exigé par toutes les API.
 */

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const NETWORK_ERROR = { success: false, error: 'Impossible de contacter le serveur. Veuillez réessayer.' };

async function request(url, { method = 'POST', json, body } = {}) {
    const headers = new Headers({ Accept: 'application/json' });
    if (csrfToken) headers.set('X-CSRF-Token', csrfToken);

    if (json !== undefined) {
        headers.set('Content-Type', 'application/json');
        body = JSON.stringify(json);
    }

    return fetch(url, { method, headers, body, credentials: 'same-origin' });
}

async function readJson(response) {
    try {
        return await response.json();
    } catch {
        return { success: false, error: 'Réponse invalide du serveur.' };
    }
}

/**
 * Envoie un objet en JSON et retourne la réponse JSON.
 * Ne lève pas d'exception : en cas d'erreur réseau, retourne { success: false, error }.
 */
export async function postJson(url, payload = {}) {
    try {
        return await readJson(await request(url, { json: payload }));
    } catch {
        return NETWORK_ERROR;
    }
}

/**
 * Envoie un formulaire (FormData, fichiers compris) et retourne la réponse JSON.
 */
export async function postForm(url, formOrData) {
    const body = formOrData instanceof FormData ? formOrData : new FormData(formOrData);
    try {
        return await readJson(await request(url, { body }));
    } catch {
        return NETWORK_ERROR;
    }
}

/**
 * Envoie un objet en JSON et attend du HTML en retour.
 * Retourne { success: true, html } ou la réponse d'erreur JSON du serveur.
 */
export async function postJsonForHtml(url, payload = {}) {
    try {
        const response = await request(url, { json: payload });
        const contentType = response.headers.get('Content-Type') ?? '';
        if (contentType.includes('application/json')) {
            return readJson(response);
        }
        return { success: response.ok, html: await response.text() };
    } catch {
        return NETWORK_ERROR;
    }
}

/** Message d'erreur à afficher pour une réponse d'API en échec. */
export function errorMessage(result, fallback = 'Une erreur est survenue.') {
    return result?.error || result?.message || fallback;
}

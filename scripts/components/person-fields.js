/**
 * Champs « person[...] » d'une personne sans compte (includes/accountless-fields.php).
 */
import { $$ } from '../core/dom.js';

const PREFIX = 'person[';

const fieldKey = field => field.name.slice(PREFIX.length, -1);

/** Retourne { first_name, last_name, ... } à partir des champs person[...] de `root`. */
export function readPersonFields(root) {
    const person = {};
    $$(`[name^="${PREFIX}"]`, root).forEach(field => {
        person[fieldKey(field)] = field.value.trim();
    });
    return person;
}

/** Remplit les champs person[...] de `root` avec `person`. */
export function fillPersonFields(root, person) {
    $$(`[name^="${PREFIX}"]`, root).forEach(field => {
        field.value = person[fieldKey(field)] ?? '';
    });
}

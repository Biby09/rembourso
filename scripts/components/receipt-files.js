/**
 * Validation côté client des quittances.
 * - Au choix des fichiers : type, et taille de l'ORIGINAL (il sera réduit avant l'envoi, voir receipt-compressor.js).
 * - Avant l'envoi : taille des fichiers préparés, alignée avec la limite du serveur (config/receipts.php).
 */

export const RECEIPT_ACCEPTED_TYPES = ['image/png', 'image/jpeg', 'application/pdf'];
export const RECEIPT_MAX_ORIGINAL_SIZE = 25 * 1024 * 1024; // fichier choisi, avant réduction
export const RECEIPT_MAX_SIZE = 5 * 1024 * 1024;           // fichier envoyé (RECEIPT_MAX_SIZE de config/receipts.php)

/** Retourne un message d'erreur si un fichier choisi est refusé, sinon null. */
export function validateReceiptFiles(files) {
    const list = Array.from(files ?? []);

    if (list.some(file => !RECEIPT_ACCEPTED_TYPES.includes(file.type))) {
        return 'Type de fichier non autorisé. Formats acceptés : PNG, JPEG et PDF.';
    }
    if (list.some(file => file.size > RECEIPT_MAX_ORIGINAL_SIZE)) {
        return 'Chaque fichier est limité à 25 Mo.';
    }
    return null;
}

/** Retourne un message d'erreur si un fichier préparé dépasse la limite du serveur, sinon null. */
export function validatePreparedReceipts(files) {
    const tooLarge = files.find(file => file.size > RECEIPT_MAX_SIZE);
    return tooLarge
        ? `« ${tooLarge.name} » reste trop lourd après réduction (5 Mo au maximum). Essayez une image plus petite.`
        : null;
}

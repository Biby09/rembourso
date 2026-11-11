/**
 * Formatage des montants et des dates pour l'affichage (format suisse romand).
 */

/** 1234.5 -> "1 234,50 CHF" */
export function formatAmount(value, currency = '') {
    const amount = Number.parseFloat(value);
    const formatted = (Number.isFinite(amount) ? amount : 0)
        .toLocaleString('fr-CH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        .replace(/ | |’/g, ' ');
    return currency ? `${formatted} ${currency}` : formatted;
}

/**
 * "2026-11-05" -> "05/11/2026" (même format que les dates affichées côté PHP).
 * Les dates "AAAA-MM-JJ" sont lues en heure locale (new Date('AAAA-MM-JJ') les
 * interpréterait en UTC et pourrait décaler d'un jour).
 */
export function formatDate(value) {
    if (!value) return '';
    const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(value);
    const date = match ? new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3])) : new Date(value);
    return Number.isNaN(date.getTime()) ? '' : date.toLocaleDateString('fr-FR');
}

/** Date du jour au format "AAAA-MM-JJ" (heure locale), pour comparer avec un <input type="date">. */
export function todayIso() {
    const now = new Date();
    const pad = n => String(n).padStart(2, '0');
    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
}

/**
 * Préparation des quittances dans le navigateur, juste avant l'envoi :
 * - photos (JPEG, PNG) : réduites à MAX_DIMENSION px sur le grand côté et ré-encodées en JPEG ;
 * - PDF : chaque page est dessinée avec pdf.js puis envoyée comme une image JPEG.
 * Le serveur reçoit ainsi des images légères et n'a plus à convertir les PDF.
 *
 * Si la préparation d'un fichier échoue (navigateur trop ancien, PDF particulier…), le fichier original
 * est envoyé tel quel : le serveur sait toujours convertir un PDF (config/receipts.php, Imagick).
 *
 * pdf.js est servi par le site (/node_modules/pdfjs-dist, installé avec npm) car la CSP n'autorise que
 * les scripts du site ; il n'est téléchargé que lorsqu'un PDF est choisi.
 */

import { showAlert } from '../core/ui.js';
import { validatePreparedReceipts } from './receipt-files.js';

const MAX_DIMENSION = 2000;   // grand côté de l'image envoyée (px) : texte des quittances lisible
const JPEG_QUALITY = 0.85;
const PDFJS = '/node_modules/pdfjs-dist/';

let pdfjsPromise = null;

/** Charge pdf.js une seule fois (import dynamique : pas de téléchargement si aucun PDF). */
function loadPdfjs() {
    pdfjsPromise ??= import(`${PDFJS}build/pdf.min.mjs`).then(pdfjs => {
        pdfjs.GlobalWorkerOptions.workerSrc = `${PDFJS}build/pdf.worker.min.mjs`;
        return pdfjs;
    });
    return pdfjsPromise;
}

/** « Ticket caisse.pdf » → « Ticket caisse » */
const baseName = name => name.replace(/\.[^.]+$/, '') || 'quittance';

function canvasToJpeg(canvas) {
    return new Promise((resolve, reject) => canvas.toBlob(
        blob => (blob ? resolve(blob) : reject(new Error('Conversion JPEG impossible'))),
        'image/jpeg',
        JPEG_QUALITY,
    ));
}

/** Canvas de la taille demandée, fond blanc (les zones transparentes d'un PNG ne deviennent pas noires). */
function whiteCanvas(width, height) {
    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, Math.round(width));
    canvas.height = Math.max(1, Math.round(height));
    const context = canvas.getContext('2d');
    context.fillStyle = '#ffffff';
    context.fillRect(0, 0, canvas.width, canvas.height);
    return [canvas, context];
}

/**
 * Photo réduite et ré-encodée en JPEG. Une image déjà plus légère que le résultat est gardée telle quelle.
 */
async function compressImage(file) {
    // imageOrientation : une photo prise en portrait reste droite
    const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
    const ratio = Math.min(1, MAX_DIMENSION / Math.max(bitmap.width, bitmap.height));
    const [canvas, context] = whiteCanvas(bitmap.width * ratio, bitmap.height * ratio);
    context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
    bitmap.close();

    const blob = await canvasToJpeg(canvas);
    if (blob.size >= file.size) {
        return file;
    }
    return new File([blob], `${baseName(file.name)}.jpg`, { type: 'image/jpeg' });
}

/**
 * Une image JPEG par page du PDF : « Ticket.pdf » → « Ticket - page 1.jpg », « Ticket - page 2.jpg »…
 */
async function convertPdf(file) {
    const pdfjs = await loadPdfjs();
    const loadingTask = pdfjs.getDocument({
        data: new Uint8Array(await file.arrayBuffer()),
        // Polices standard et décodeurs d'images, servis par le site
        standardFontDataUrl: `${PDFJS}standard_fonts/`,
        cMapUrl: `${PDFJS}cmaps/`,
        wasmUrl: `${PDFJS}wasm/`,
    });
    const pdf = await loadingTask.promise;

    try {
        const images = [];
        for (let number = 1; number <= pdf.numPages; number++) {
            const page = await pdf.getPage(number);
            const size = page.getViewport({ scale: 1 });
            const viewport = page.getViewport({ scale: MAX_DIMENSION / Math.max(size.width, size.height) });
            const [canvas, context] = whiteCanvas(viewport.width, viewport.height);

            await page.render({ canvasContext: context, viewport, background: '#ffffff' }).promise;
            page.cleanup();

            const name = pdf.numPages > 1 ? `${baseName(file.name)} - page ${number}.jpg` : `${baseName(file.name)}.jpg`;
            images.push(new File([await canvasToJpeg(canvas)], name, { type: 'image/jpeg' }));
        }
        return images;
    } finally {
        await loadingTask.destroy(); // libère le document et le worker
    }
}

/**
 * Prépare les quittances choisies et renvoie les fichiers à envoyer (File[]).
 * onProgress(numéro, total) est appelé avant chaque fichier (message « Préparation… »).
 */
export async function prepareReceiptFiles(files, { onProgress = () => {} } = {}) {
    const list = Array.from(files ?? []);
    const prepared = [];

    for (const [index, file] of list.entries()) {
        onProgress(index + 1, list.length);
        try {
            if (file.type === 'application/pdf') {
                prepared.push(...await convertPdf(file));
            } else if (file.type === 'image/jpeg' || file.type === 'image/png') {
                prepared.push(await compressImage(file));
            } else {
                prepared.push(file);
            }
        } catch (error) {
            // Le serveur se chargera du fichier original
            console.warn(`Préparation de « ${file.name} » impossible, envoi du fichier original.`, error);
            prepared.push(file);
        }
    }

    return prepared;
}

/**
 * Remplace les quittances de `formData` (champ receipts[]) par les fichiers préparés, en affichant la progression
 * dans `messageContainer`. Les boutons `buttons` sont désactivés pendant la préparation.
 * Renvoie false (avec un message d'erreur affiché) si un fichier reste trop lourd.
 */
export async function appendPreparedReceipts(formData, files, { messageContainer, buttons = [] }) {
    formData.delete('receipts[]');
    const list = Array.from(files ?? []);
    if (!list.length) {
        return true;
    }

    buttons.forEach(button => { button.disabled = true; });
    try {
        const prepared = await prepareReceiptFiles(list, {
            onProgress: (number, total) => showAlert(messageContainer, 'info',
                total > 1 ? `Préparation des quittances… (${number}/${total})` : 'Préparation de la quittance…'),
        });

        const error = validatePreparedReceipts(prepared);
        if (error) {
            showAlert(messageContainer, 'danger', error);
            return false;
        }

        prepared.forEach(file => formData.append('receipts[]', file));
        return true;
    } finally {
        buttons.forEach(button => { button.disabled = false; });
    }
}

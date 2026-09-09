const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
const nativeFetch = window.fetch.bind(window);

window.fetch = (input, init = {}) => {
    const headers = new Headers(init.headers || (input instanceof Request ? input.headers : undefined));

    if (csrfToken) {
        headers.set('X-CSRF-Token', csrfToken);
    }

    return nativeFetch(input, {
        ...init,
        headers
    });
};

async function sendForm(form, url, options = {}) {
    const msg = options.messageContainer
        ? document.querySelector(options.messageContainer)
        : null;

    const formData = new FormData(form);
    const loadingMsg = options.loadingMessage || "Envoi en cours...";
    const successMsg = options.successMessage || "Succès";
    const errorMsg = options.errorMessage || "Erreur";
    const noReset = options.noReset || false;

    if (msg) {
        msg.innerHTML = `<div class="alert alert-info">${loadingMsg}</div>`;
    }

    try {
        const response = await fetch(url, {
            method: "POST",
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            if (msg) {
                msg.innerHTML = `<div class="alert alert-success">${successMsg}</div>`;

                if (!noReset) {
                    form.reset();
                }
            }

            // Redirection si une URL est fournie
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        } else {
            if (msg) {
                const displayMsg = data.message ? data.message : errorMsg;
                msg.innerHTML = `<div class="alert alert-danger">${displayMsg}</div>`;
            }
        }

        return data;

    } catch (error) {
        if (msg) {
            msg.innerHTML = `<div class="alert alert-danger">${errorMsg}</div>`;
        }

        return { success: false, error };
    }
}

function showAlert(container, type, message, timeout = 0) {
    if (!container) return;

    const allowedTypes = ['success', 'danger', 'info', 'warning'];
    const safeType = allowedTypes.includes(type) ? type : 'info';
    container.innerHTML = `<div class="alert alert-${safeType} py-2 mb-2">${message}</div>`;

    if (timeout > 0) {
        setTimeout(() => {
            container.innerHTML = '';
        }, timeout);
    }
}

function getBootstrapModal(element) {
    if (!element || typeof bootstrap === 'undefined') {
        return null;
    }

    return bootstrap.Modal.getOrCreateInstance(element);
}

function hideBootstrapModal(element) {
    const modal = getBootstrapModal(element);
    if (modal) {
        modal.hide();
    }
}

function setButtonLoadingState(button, isLoading, loadingText = 'Chargement...') {
    if (!button) return;

    if (isLoading) {
        button.dataset.originalText = button.dataset.originalText || button.textContent.trim();
        button.disabled = true;
        button.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>${loadingText}`;
        return;
    }

    button.disabled = false;
    const originalText = button.dataset.originalText || button.textContent.trim();
    button.textContent = originalText;
    delete button.dataset.originalText;
}

async function requestJson(url, payload = null, options = {}) {
    const method = options.method || (payload === null ? 'GET' : 'POST');
    const requestOptions = {
        ...options,
        method,
        headers: {
            ...(options.headers || {})
        }
    };

    if (payload !== null && requestOptions.body === undefined) {
        requestOptions.headers['Content-Type'] = requestOptions.headers['Content-Type'] || 'application/json';
        requestOptions.body = JSON.stringify(payload);
    }

    const response = await fetch(url, requestOptions);

    const contentType = response.headers.get('Content-Type') || '';
    if (contentType.includes('application/json')) {
        return response.json();
    }

    return response.text();
}

function receiptGalleryPreviewHtml(repayment, maxHeight = 400) {
    const receiptCount = repayment.receipt_count || 1;
    const receiptTypes = repayment.receipt_types || [repayment.receipt_type];
    const receiptUrl = `/api/get-receipt.php?repayment_id=${repayment.id}&receipt_index=0`;
    const receiptTypesData = encodeURIComponent(JSON.stringify(receiptTypes));
    const preview = `<img class="receipt_image mw-100" style="max-height: ${maxHeight}px; object-fit: contain;" src="${receiptUrl}" alt="Quittance">`;

    return `<div class="text-center">
        ${receiptCount > 1 ? `<button type="button" class="btn btn-link btn-sm p-0 mb-2 receipt-gallery-trigger" data-repayment-id="${repayment.id}" data-receipt-count="${receiptCount}" data-receipt-types="${receiptTypesData}">Voir les autres quittances (${receiptCount})</button>` : ''}
        <button type="button" class="receipt-gallery-trigger border-0 bg-transparent p-0" data-repayment-id="${repayment.id}" data-receipt-count="${receiptCount}" data-receipt-types="${receiptTypesData}">${preview}</button>
    </div>`;
}

function ensureReceiptGalleryModal() {
    let modal = document.getElementById('receipt-gallery-modal');
    if (modal) return modal;

    modal = document.createElement('div');
    modal.id = 'receipt-gallery-modal';
    modal.className = 'modal fade';
    modal.tabIndex = -1;
    modal.innerHTML = `<div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Quittance <span data-receipt-position></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
        <div class="modal-body text-center" data-receipt-content></div>
        <div class="modal-footer justify-content-between"><button type="button" class="btn btn-outline-secondary" data-receipt-previous>Précédente</button><button type="button" class="btn btn-outline-secondary" data-receipt-next>Suivante</button></div>
    </div></div>`;
    document.body.appendChild(modal);
    return modal;
}

document.addEventListener('click', event => {
    const trigger = event.target.closest('.receipt-gallery-trigger');
    if (!trigger || typeof bootstrap === 'undefined') return;

    const repaymentId = trigger.dataset.repaymentId;
    const receiptCount = Number(trigger.dataset.receiptCount) || 1;
    const receiptTypes = JSON.parse(decodeURIComponent(trigger.dataset.receiptTypes || '[]'));
    const modal = ensureReceiptGalleryModal();
    const content = modal.querySelector('[data-receipt-content]');
    const position = modal.querySelector('[data-receipt-position]');
    const previous = modal.querySelector('[data-receipt-previous]');
    const next = modal.querySelector('[data-receipt-next]');
    let currentIndex = Number(trigger.dataset.receiptIndex) || 0;

    const render = () => {
        const receiptUrl = `/api/get-receipt.php?repayment_id=${repaymentId}&receipt_index=${currentIndex}`;
        content.innerHTML = `<img src="${receiptUrl}" alt="Quittance ${currentIndex + 1}" class="img-fluid" style="max-height: 75vh;">`;
        position.textContent = receiptCount > 1 ? `${currentIndex + 1} / ${receiptCount}` : '';
        previous.disabled = currentIndex === 0;
        next.disabled = currentIndex === receiptCount - 1;
    };

    previous.onclick = () => {
        if (currentIndex > 0) {
            currentIndex--;
            render();
        }
    };
    next.onclick = () => {
        if (currentIndex < receiptCount - 1) {
            currentIndex++;
            render();
        }
    };
    render();
    bootstrap.Modal.getOrCreateInstance(modal).show();
});

function initCountrySelect(selectEl, selectedCountry = null) {
    if (!selectEl) return;

    fetch("/api/get-countries.php")
        .then(res => res.json())
        .then(data => {
            const priority = ["CH"];
            const priorityCountries = [];
            const otherCountries = [];

            data.forEach(country => {
                if (priority.includes(country.cca2)) {
                    priorityCountries.push(country);
                } else {
                    otherCountries.push(country);
                }
            });

            priorityCountries.sort((a, b) => a.name.common.localeCompare(b.name.common));
            otherCountries.sort((a, b) => a.name.common.localeCompare(b.name.common));

            // Récupérer la valeur par défaut si elle existe
            const defaultCountry = selectedCountry || selectEl.getAttribute('data-country') || '';

            // Ajouter les pays prioritaires
            priorityCountries.forEach(country => {
                const option = document.createElement("option");
                option.value = country.cca2;
                option.textContent = country.name.common;

                // Sélectionner si c'est le pays par défaut
                if (country.cca2 === defaultCountry) {
                    option.selected = true;
                }

                selectEl.appendChild(option);
            });

            // Ajouter un séparateur
            const separator = document.createElement("option");
            separator.disabled = true;
            separator.textContent = "──────────";
            selectEl.appendChild(separator);

            // Ajouter les autres pays
            otherCountries.forEach(country => {
                const option = document.createElement("option");
                option.value = country.cca2;
                option.textContent = country.name.common;

                // Sélectionner si c'est le pays par défaut
                if (country.cca2 === defaultCountry) {
                    option.selected = true;
                }

                selectEl.appendChild(option);
            });
        });
}
function toggleFormEdit(form, isEditMode) {
    const inputs = form.querySelectorAll('input, select');
    inputs.forEach(input => {
        input.disabled = !isEditMode;
    });

    const editButton = form.parentElement.querySelector('.btn-edit');
    const cancelButton = form.parentElement.querySelector('.btn-cancel');
    const submitButton = form.querySelector('button[type="submit"]');

    if (isEditMode) {
        editButton.style.display = 'none';
        cancelButton.style.display = 'inline-block';
        submitButton.style.display = 'inline-block';
    } else {
        editButton.style.display = 'inline-block';
        cancelButton.style.display = 'none';
        submitButton.style.display = 'none';
    }
}
async function respondToInvitation(accept, token = null, id = null, organisationId) {
    if (!token && !id) {
        alert('Token ou ID d\'invitation manquant.');
        return;
    }

    try {
        const response = await fetch('/api/respond-invitation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                token: token,
                accept: accept,
                id: id
            })
        });

        const result = await response.json();

        if (result.success) {
            if (accept) {
                if (organisationId) {
                    window.location.href = `/dashboard/organisation/?id=${organisationId}`;
                }
            } else {
                window.location.href = '/dashboard/';
            }
        } else {
            alert(result.error || 'Une erreur est survenue lors de l\'envoi de la réponse.');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Une erreur est survenue lors de l\'envoi de la réponse.');
    }
}
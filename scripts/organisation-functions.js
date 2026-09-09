async function generateAccordion(page, organisationId) {
    try {
        const response = await fetch('/api/get-repayments.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                organisation_id: organisationId,
                page: page
            })
        });

        const result = await response.json();

        // Créer l'accordéon
        const accordion = document.createElement('div');
        accordion.className = 'accordion';
        accordion.id = 'accordion-repayment';

        if (result.success && result.data && result.data.length > 0) {
            // Générer les items
            result.data.forEach(repayment => {
                accordion.appendChild(generateAccordionItem(repayment, result.currency));
            });
        } else {
            accordion.innerHTML = '<p class="text-center text-muted">Aucune demande trouvée.</p>';
        }

        return accordion;
    } catch (error) {
        console.error('Erreur lors de la requête:', error);
        const accordion = document.createElement('div');
        accordion.innerHTML = '<p class="text-center text-danger">Erreur lors du chargement des demandes.</p>';
        return accordion;
    }
}

// Fonction utilitaire pour créer un élément d'accordéon
function generateAccordionItem(repayment, currency, open = false, parentId = 'accordion-repayment') {
    // Déterminer le statut et la couleur du badge
    let statusText = '';
    let badgeColor = '';
    if (repayment.status == '0') {
        statusText = 'En attente';
        badgeColor = 'warning';
    } else if (repayment.status == '1') {
        statusText = 'Acceptée';
        badgeColor = 'success';
    } else {
        statusText = 'Refusée';
        badgeColor = 'danger';
    }

    // Créer le HTML de l'accordéon
    const accordionItem = document.createElement('div');
    accordionItem.className = 'accordion-item';
    accordionItem.id = `accordionItem${repayment.id}`;

    // Structure HTML
    accordionItem.innerHTML = `
                <h2 class="accordion-header">
                    <button class="accordion-button${open ? '' : ' collapsed'}" type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#accordionItem${repayment.id}Collapse"
                        aria-expanded="${open ? 'true' : 'false'}"
                        aria-controls="accordionItem${repayment.id}Collapse">
                        <div class="badge bg-${badgeColor} me-2">
                            ${statusText}
                        </div>
                        ${repayment.label}
                    </button>
                </h2>
                `;
    accordionItem.appendChild(generateAccordionItemContent(repayment, currency, open, parentId));


    return accordionItem;
}

function generateAccordionItemContent(repayment, currency, open = false, parentId = 'accordion-repayment') {
    const receiptHtml = receiptGalleryPreviewHtml(repayment);

    const accordionItemContentHTML = `
                    <div id="accordionItem${repayment.id}Collapse"
                    class="accordion-collapse collapse${open ? ' show' : ''}"
                    data-bs-parent="#${parentId}"
                    data-repayment-id="${repayment.id}"
                    data-receipt-html="${btoa(receiptHtml)}">
                        <div class="accordion-body container">
                            <div class="row mb-3">
                                <div class="col-lg-6 d-flex flex-column justify-content-start align-items-start mt-3">
                                    <h4>${repayment.label}</h4>
                                    <p><strong>Montant :</strong> ${parseInt(repayment.amount).toFixed(2).replace('.', ',')} ${currency}</p>
                                    ${repayment.category ? `<p><strong>Catégorie :</strong> ${repayment.category.name}</p>` : ''}
                                    ${repayment.subcategory ? `<p><strong>Sous-catégorie :</strong> ${repayment.subcategory.name}</p>` : ''}
                                    <p><strong>Date de l'achat :</strong> ${new Date(repayment.transaction_date.date).toLocaleDateString('fr-FR')}</p>
                                    <p><strong>Date de dépôt de la demande :</strong> ${new Date(repayment.requested_at.date).toLocaleDateString('fr-FR')}</p>
                                    ${repayment.batch_date ? `<p><strong>Date de traitement :</strong> ${new Date(repayment.batch_date).toLocaleDateString('fr-FR')}</p>` : ''}
                                </div>
                                <div class="col-lg-6">
                                    <!-- Le contenu de l'image sera chargé ici à l'ouverture de l'accordéon -->
                                    <div class="d-flex justify-content-center receipt-container"></div>
                                </div>
                            </div>
                            <div class="row button-row">
                                <div class="col d-flex justify-content-start gap-2">
                                </div>
                            </div>
                        </div>
                    </div>
            `;

    // Convertir la chaîne HTML en élément DOM
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = accordionItemContentHTML;
    const accordionItemContent = tempDiv.firstElementChild;

    //Bouton pour modifier et supprimer la demande (visible seulement le remboursement est editable)
    if (repayment.editable) {
        const editButton = document.createElement('button');
        editButton.className = 'btn btn-primary btn-edit';
        editButton.textContent = 'Modifier';
        accordionItemContent.querySelector('.accordion-body .button-row>div').appendChild(editButton);

        const deleteButton = document.createElement('button');
        deleteButton.className = 'btn btn-danger btn-delete ms-2';
        deleteButton.textContent = 'Supprimer';
        accordionItemContent.querySelector('.accordion-body .button-row>div').appendChild(deleteButton);

        initDialogOpenButtons([editButton]);
        initDeleteButtons([deleteButton]);
    }
    // Ajouter un event listener pour charger l'image quand l'accordéon s'ouvre
    accordionItemContent.addEventListener('show.bs.collapse', () => {
        const receiptContainer = accordionItemContent.querySelector('.receipt-container');
        if (receiptContainer.innerHTML === '') {
            const receiptHtmlDecoded = atob(accordionItemContent.getAttribute('data-receipt-html'));
            receiptContainer.innerHTML = receiptHtmlDecoded;
        }
    });

    return accordionItemContent;
}
//Script de gestion de l'affichage du formulaire de modification d'une demande de remboursement

function initDialogOpenButtons(editButtons) {

    for (let button of editButtons) {
        button.addEventListener('click', () => {
            fetch('/api/get-repayment-edit-dialog.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    repayment_id: parseInt(button.closest('.accordion-item').getAttribute('id').replace('accordionItem', ''))
                })
            }).then(response => {
                if (response.headers.get('Content-Type') && response.headers.get('Content-Type').includes('application/json')) {
                    alert('Erreur lors du chargement du formulaire de modification. Veuillez réessayer.');
                    return null;
                } else if (response.headers.get('Content-Type') && response.headers.get('Content-Type').includes('text/html')) {
                    return response.text();
                } else {
                    throw new Error('Unexpected content type: ' + response.headers.get('Content-Type'));
                }
            }).then(data => {
                const dialog = document.getElementById('repaymentEditDialog');
                dialog.innerHTML = data;

                // Créer une instance de Bootstrap Modal sur le .modal
                const modalElement = dialog.querySelector('.modal');
                if (modalElement) {
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();
                } else {
                    console.error('Modal element not found');
                }

                // Gérer les boutons Annuler et fermer
                const cancelBtns = dialog.querySelectorAll('#cancel-repayment-btn, #close-modal-btn');
                cancelBtns.forEach(btn => {
                    btn.addEventListener('click', () => {
                        const modalElement = dialog.querySelector('.modal');
                        const modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) modal.hide();
                    });
                });

                //Gérér les sous-catégories dynamiques

                const categorySelect = modalElement.querySelector('select[name="category"]');
                const subcategorySelect = modalElement.querySelector('select[name="subcategory"]');
                const subcategoryContainer = modalElement.querySelector('#subcategory-container');

                if (categorySelect && subcategorySelect) {
                    categorySelect.addEventListener('change', () => {
                        updateSubcategories(categorySelect, subcategorySelect, subcategoryContainer);
                    });
                }

                const receiptInput = modalElement.querySelector('#receipts');
                const receiptList = modalElement.querySelector('#receipt-list');
                const newReceipts = [];
                const syncNewReceipts = () => {
                    const transfer = new DataTransfer();
                    newReceipts.forEach(file => transfer.items.add(file));
                    receiptInput.files = transfer.files;
                };
                const renderNewReceipts = () => {
                    receiptList.querySelectorAll('[data-new-receipt]').forEach(item => item.remove());
                    newReceipts.forEach((file, index) => {
                        const item = document.createElement('li');
                        item.className = 'list-group-item d-flex justify-content-between align-items-center';
                        item.dataset.newReceipt = index;
                        item.innerHTML = `<span>${file.name}</span><button type="button" class="btn-close receipt-remove" aria-label="Supprimer"></button>`;
                        receiptList.appendChild(item);
                    });
                };
                modalElement.querySelector('#add-receipts-btn').addEventListener('click', () => receiptInput.click());
                receiptInput.addEventListener('change', () => {
                    Array.from(receiptInput.files).forEach(file => newReceipts.push(file));
                    syncNewReceipts();
                    renderNewReceipts();
                });
                receiptList.addEventListener('click', event => {
                    const removeButton = event.target.closest('.receipt-remove');
                    if (!removeButton) return;
                    const item = removeButton.closest('li');
                    if (item.dataset.newReceipt !== undefined) {
                        newReceipts.splice(Number(item.dataset.newReceipt), 1);
                        syncNewReceipts();
                        renderNewReceipts();
                    } else {
                        item.remove();
                    }
                });

                //Gérer la soumission du formulaire de modification
                const saveButton = modalElement.querySelector('#save-repayment-btn');
                const editForm = modalElement.querySelector('#edit-repayment-form');
                saveButton.addEventListener('click', async (e) => {
                    e.preventDefault();
                    //Controler le formulaire avant l'envoi
                    if (!editForm.checkValidity()) {
                        editForm.reportValidity();
                        return;
                    }


                    const result = await sendForm(editForm, '/api/edit-repayment.php', {
                        messageContainer: '#messageContainer',
                    });
                    if (result.success) {
                        // Si la modification est réussie, fermer le modal et recharger les données de l'accordéon
                        const modalElement = dialog.querySelector('.modal');
                        const modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) modal.hide();

                        // Recharger les données de l'accordéon
                        const accordionItem = button.closest('.accordion-item');
                        const repaymentId = accordionItem.getAttribute('id').replace('accordionItem', '');
                        const updatedAccordionItem = await generateAccordionItem(result.updated_repayment, result.currency, true);
                        accordionItem.replaceWith(updatedAccordionItem);

                        //Recharger l'image du reçu dans le modal de l'accordéon
                        const collapseElement = updatedAccordionItem.querySelector(`#accordionItem${repaymentId}Collapse`);
                        const receiptContainer = collapseElement.querySelector('.receipt-container');
                        const receiptHtmlDecoded = atob(collapseElement.getAttribute('data-receipt-html'));
                        receiptContainer.innerHTML = receiptHtmlDecoded;



                    }
                });



            });
        });
    }
}

function initDeleteButtons(buttons) {
    for (let button of buttons) {

        button.addEventListener('click', () => {
            const repaymentId = parseInt(button.closest('.accordion-item').getAttribute('id').replace('accordionItem', ''));

            if (confirm('Êtes-vous sûr de vouloir supprimer cette demande de remboursement ?')) {

                fetch('/api/delete-repayment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        repayment_id: repaymentId
                    })
                })
                    .then(response => response.json())

                    .then(result => {
                        if (result.success) {
                            // Supprimer l'item de l'accordéon
                            const accordionItem = button.closest('.accordion-item');
                            accordionItem.remove();

                        } else {
                            alert('Erreur lors de la suppression de la demande. Veuillez réessayer.');
                        }
                    })
                    .catch(error => {
                        console.error('Erreur lors de la requête:', error);
                        alert('Erreur lors de la suppression de la demande. Veuillez réessayer.');
                    });

            }
        });
    }
}

async function reloadCashierTable(organisationId) {
    const table = await generateCashierTable(organisationId);
    const oldTable = document.getElementById('cashier-table');

    if (oldTable) {
        oldTable.replaceWith(table);
    } else {
        document.getElementById('cashier-table-container').appendChild(table);
    }
}

async function generateCashierTable(organisationId) {
    try {
        const response = await fetch('/api/get-repayments-user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                organisation_id: organisationId
            })
        });

        const result = await response.json();

        if (result.success && result.data && result.data.length > 0) {
            // Générer un tableau pour afficher les demandes de remboursement regroupé par utilisateur
            const table = document.createElement('table');
            table.className = 'table table-striped';
            table.id = 'cashier-table';

            const thead = document.createElement('thead');
            thead.innerHTML = `
                <tr>
                    <th class="text-start">Utilisateur</th>
                    <th class="text-center">Nombre de demandes</th>
                    <th class="text-end">Montant total</th>
                </tr>
            `;
            table.appendChild(thead);

            const tbody = document.createElement('tbody');

            result.data.forEach(userData => {
                const tr = document.createElement('tr');

                const userCell = document.createElement('td');
                const userButton = document.createElement('button');
                userButton.type = 'button';
                userButton.className = 'btn btn-link p-0';
                userButton.dataset.userId = String(userData.id);
                userButton.textContent = userData.full_name;
                userCell.appendChild(userButton);

                const requestCountCell = document.createElement('td');
                requestCountCell.className = 'text-center';
                requestCountCell.textContent = String(userData.request_count);

                const totalPendingCell = document.createElement('td');
                totalPendingCell.className = 'text-end';
                totalPendingCell.textContent = `${parseInt(userData.total_pending, 10).toFixed(2).replace('.', ',')} ${result.currency}`;

                tr.append(userCell, requestCountCell, totalPendingCell);
                tbody.appendChild(tr);
            });

            table.appendChild(tbody);

            table.querySelectorAll('button[data-user-id]').forEach(button => {
                button.addEventListener('click', async () => {
                    const modalContainer = document.getElementById('cashier-tab-dialog-container');
                    const userId = button.getAttribute('data-user-id');

                    const modalDialog = await generateCashierRepaymentsModal(userId, result.currency);

                    modalContainer.innerHTML = '';
                    modalContainer.appendChild(modalDialog); // injecte .modal-dialog dans .modal

                    const modal = bootstrap.Modal.getInstance(modalContainer)
                        ?? new bootstrap.Modal(modalContainer);
                    modal.show();

                    // Ajouter un écouteur pour le bouton d'enregistrement des statuts
                    // Gérer le clic sur le bouton d'enregistrement des statuts
                    const saveButton = modalContainer.querySelector('#save-status-btn');
                    const radioButtons = modalContainer.querySelectorAll('input[type="radio"]');
                    saveButton.addEventListener('click', async () => {
                        const updates = [];
                        radioButtons.forEach(radio => {
                            if (radio.checked && !radio.id.includes('pending')) {
                                const repaymentId = parseInt(radio.name.replace('status', ''));
                                let newStatus = '';
                                if (radio.id.includes('accept')) {
                                    newStatus = 'accepted';
                                } else if (radio.id.includes('reject')) {
                                    newStatus = 'rejected';
                                } else {
                                    return;
                                }
                                updates.push({ repayment_id: repaymentId, status: newStatus });
                            }
                        });

                        if (updates.length === 0) {
                            alert('Aucun changement à enregistrer.');
                            modal.hide();
                            return;
                        } else {

                            if (updates.some(update => update.status === 'accepted')) {

                                fetch('/api/repayment-process.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        updates: updates,
                                        action: 'get_qr',
                                        organisation_id: new URLSearchParams(window.location.search).get('id'),
                                    })
                                }).then(response => response.json())
                                    .then(result => {
                                        if (result.success) {
                                            modalContainer.querySelector('.modal-body').innerHTML = result.modalContent;
                                            modalContainer.querySelector('#save-status-btn').innerHTML = 'Valider les remboursements';

                                            modalContainer.querySelector('#save-status-btn').addEventListener('click', async () => {
                                                fetch('/api/repayment-process.php', {
                                                    method: 'POST',
                                                    headers: {
                                                        'Content-Type': 'application/json'
                                                    },
                                                    body: JSON.stringify({
                                                        updates: updates,
                                                        action: 'confirm_payment',
                                                        organisation_id: new URLSearchParams(window.location.search).get('id'),
                                                    })
                                                }).then(response => response.json())
                                                    .then(result => {
                                                        if (result.success) {
                                                            modal.hide();
                                                            reloadCashierTable(new URLSearchParams(window.location.search).get('id'));
                                                        }
                                                    });
                                            });
                                        } else {
                                            alert('Erreur lors de la mise à jour des statuts. Veuillez réessayer.');
                                        }
                                    })
                            } else {
                                // Si aucune demande n'est acceptée, envoyer les mises à jour directement sans passer par le QR code
                                fetch('/api/repayment-process.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        updates: updates,
                                        action: 'confirm_payment',
                                        organisation_id: new URLSearchParams(window.location.search).get('id'),
                                    })
                                }).then(response => response.json())
                                    .then(result => {
                                        if (result.success) {
                                            modal.hide();
                                            reloadCashierTable(new URLSearchParams(window.location.search).get('id'));
                                        }
                                    });
                            }
                        }
                    });
                });
            });

            return table;


        } else {
            const content = document.createElement('table');
            content.id = 'cashier-table';
            content.className = 'table';

            content.innerHTML = `
            <tbody>
                <tr>
                    <td class="text-center text-muted">
                        Aucune demande trouvée.
                    </td>
                </tr>
            </tbody>
`;

            return content;
        }




    } catch (error) {
        const content = document.createElement('table');
        content.id = 'cashier-table';
        content.className = 'table';

        content.innerHTML = `
            <tbody>
                <tr>
                    <td class="text-center text-danger">
                        Erreur lors du chargement des demandes.
                    </td>
                </tr>
            </tbody>
`;

        return content;
    }
}

async function generateCashierRepaymentsModal(userId, currency) {
    try {
        const response = await fetch('/api/get-repayments.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                organisation_id: new URLSearchParams(window.location.search).get('id'),
                memberId: userId
            })
        });

        const result = await response.json();

        // Construire la structure complète du modal
        const modalDialog = document.createElement('div');
        modalDialog.className = 'modal-dialog modal-lg modal-dialog-scrollable modal-xl';

        const modalContent = document.createElement('div');
        modalContent.className = 'modal-content';

        // Header
        const modalHeader = document.createElement('div');
        modalHeader.className = 'modal-header';
        modalHeader.innerHTML = `
            <h5 class="modal-title">Demandes de remboursement</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
        `;

        // Body
        const modalBody = document.createElement('div');
        modalBody.className = 'modal-body';

        if (result.success && result.data && result.data.length > 0) {
            // Conteneur des cartes, utilisé pour le filtrage par catégorie
            const cardsContainer = document.createElement('div');
            cardsContainer.id = 'repayment-cards-container';

            result.data.forEach(repayment => {
                const repaymentDiv = document.createElement('div');
                repaymentDiv.className = 'repayment-card-wrapper';
                repaymentDiv.dataset.categoryId = repayment.category ? repayment.category.id : '';
                repaymentDiv.dataset.subcategoryId = repayment.subcategory ? repayment.subcategory.id : '';
                repaymentDiv.innerHTML = `
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title mb-3">${repayment.label}</h5>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <p><strong>Date de l'achat :</strong> ${new Date(repayment.transaction_date.date).toLocaleDateString('fr-FR')}</p>
                                    <p><strong>Date de dépôt de la demande :</strong> ${new Date(repayment.requested_at.date).toLocaleDateString('fr-FR')}</p>
                                    ${repayment.batch_date ? `<p><strong>Date de traitement :</strong> ${new Date(repayment.batch_date).toLocaleDateString('fr-FR')}</p>` : ''}
                                </div>
                                <div class="col-md-4">
                                    ${repayment.category ? `<p><strong>Catégorie&nbsp;:</strong> ${repayment.category.name}</p>` : ''}
                                    ${repayment.subcategory ? `<p><strong>Sous-catégorie&nbsp;:</strong> ${repayment.subcategory.name}</p>` : ''}
                                    <h6>${parseInt(repayment.amount).toFixed(2).replace('.', ',')} ${currency}</h6>
                                </div>
                                <div class="col-md-4 d-flex justify-content-center align-items-center">
                                    ${receiptGalleryPreviewHtml(repayment, 200)}
                                </div>
                            </div>

                            <!-- Boutons alignés à droite -->
                            <div class="d-flex justify-content-end gap-2 mt-3">
                                <input type="radio" class="btn-check" name="status${repayment.id}" id="accept${repayment.id}" autocomplete="off">
                                <label class="btn btn-outline-success" for="accept${repayment.id}">Accepter</label>

                                <input type="radio" class="btn-check" name="status${repayment.id}" id="reject${repayment.id}" autocomplete="off">
                                <label class="btn btn-outline-danger" for="reject${repayment.id}">Refuser</label>

                                <input type="radio" class="btn-check" name="status${repayment.id}" id="pending${repayment.id}" autocomplete="off" checked>
                                <label class="btn btn-outline-warning" for="pending${repayment.id}">En attente</label>
                            </div>

                        </div>
                    </div>
                `;
                cardsContainer.appendChild(repaymentDiv);

            });

            // Collecter les catégories et sous-catégories présentes dans les demandes
            const categories = new Map();
            const subcategories = new Map(); // clé: subcategory id, valeur: { name, categoryId }
            let hasUncategorized = false;
            let hasNoSubcategory = false;
            result.data.forEach(repayment => {
                if (repayment.category) {
                    categories.set(repayment.category.id, repayment.category.name);
                } else {
                    hasUncategorized = true;
                }
                if (repayment.subcategory) {
                    subcategories.set(repayment.subcategory.id, {
                        name: repayment.subcategory.name,
                        categoryId: repayment.category ? repayment.category.id : ''
                    });
                } else {
                    hasNoSubcategory = true;
                }
            });

            const showCategoryFilter = categories.size > 1 || (categories.size === 1 && hasUncategorized);
            const showSubcategoryFilter = subcategories.size > 1 || (subcategories.size >= 1 && hasNoSubcategory);

            if (showCategoryFilter || showSubcategoryFilter) {
                const filterBar = document.createElement('div');
                filterBar.className = 'd-flex align-items-center flex-wrap gap-3 mb-3';

                let categorySelectHtml = '';
                if (showCategoryFilter) {
                    categorySelectHtml = `
                        <div class="d-flex align-items-center gap-2 flex-nowrap">
                            <label for="category-filter" class="form-label mb-0 fw-600 text-nowrap">Catégorie&nbsp;:</label>
                            <select id="category-filter" class="form-select form-select-sm w-auto">
                                <option value="all">Toutes les catégories</option>
                                ${[...categories.entries()].map(([id, name]) => `<option value="${id}">${name}</option>`).join('')}
                                ${hasUncategorized ? '<option value="">Sans catégorie</option>' : ''}
                            </select>
                        </div>
                    `;
                }

                let subcategorySelectHtml = '';
                if (showSubcategoryFilter) {
                    subcategorySelectHtml = `
                        <div class="d-flex align-items-center gap-2 flex-nowrap">
                            <label for="subcategory-filter" class="form-label mb-0 fw-600 text-nowrap">Sous-catégorie&nbsp;:</label>
                            <select id="subcategory-filter" class="form-select form-select-sm w-auto">
                                <option value="all">Toutes les sous-catégories</option>
                                ${[...subcategories.entries()].map(([id, s]) => `<option value="${id}">${s.name}</option>`).join('')}
                                ${hasNoSubcategory ? '<option value="">Sans sous-catégorie</option>' : ''}
                            </select>
                        </div>
                    `;
                }

                filterBar.innerHTML = categorySelectHtml + subcategorySelectHtml;

                const categorySelect = filterBar.querySelector('#category-filter');
                const subcategorySelect = filterBar.querySelector('#subcategory-filter');

                // Applique les deux filtres combinés sur les cartes
                const applyFilters = () => {
                    const selectedCat = categorySelect ? categorySelect.value : 'all';
                    const selectedSub = subcategorySelect ? subcategorySelect.value : 'all';
                    cardsContainer.querySelectorAll('.repayment-card-wrapper').forEach(card => {
                        const matchCat = selectedCat === 'all' || card.dataset.categoryId === selectedCat;
                        const matchSub = selectedSub === 'all' || card.dataset.subcategoryId === selectedSub;
                        card.classList.toggle('d-none', !(matchCat && matchSub));
                    });
                };

                if (subcategorySelect) {
                    // IDs des sous-catégories ayant au moins un remboursement en attente dans ce modal
                    const pendingSubIds = new Set([...subcategories.keys()].map(String));
                    // Catégories ayant au moins une demande en attente sans sous-catégorie
                    const catsWithNoSub = new Set();
                    result.data.forEach(repayment => {
                        if (!repayment.subcategory && repayment.category) {
                            catsWithNoSub.add(String(repayment.category.id));
                        }
                    });

                    // Reconstruit les options du select des sous-catégories
                    const rebuildSubcategoryOptions = (list, showNoSubcategory) => {
                        subcategorySelect.innerHTML = '<option value="all">Toutes les sous-catégories</option>';
                        list.forEach(subcat => {
                            const opt = document.createElement('option');
                            opt.value = subcat.id;
                            opt.textContent = subcat.name;
                            subcategorySelect.appendChild(opt);
                        });
                        if (showNoSubcategory) {
                            const opt = document.createElement('option');
                            opt.value = '';
                            opt.textContent = 'Sans sous-catégorie';
                            subcategorySelect.appendChild(opt);
                        }
                        subcategorySelect.value = 'all';
                    };

                    subcategorySelect.addEventListener('change', applyFilters);

                    if (categorySelect) {
                        categorySelect.addEventListener('change', async () => {
                            const selectedCat = categorySelect.value;

                            if (selectedCat === 'all') {
                                // Toutes les sous-catégories ayant des remboursements en attente
                                rebuildSubcategoryOptions(
                                    [...subcategories.entries()].map(([id, s]) => ({ id, name: s.name })),
                                    hasNoSubcategory
                                );
                            } else if (selectedCat === '') {
                                // "Sans catégorie" : aucune sous-catégorie possible
                                rebuildSubcategoryOptions([], false);
                            } else {
                                // Charger les sous-catégories de la catégorie via l'API,
                                // puis ne garder que celles ayant des remboursements en attente
                                try {
                                    const response = await fetch('/api/get-subcategories.php', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json' },
                                        body: JSON.stringify({ categoryId: selectedCat })
                                    });
                                    const data = await response.json();
                                    const list = (data.success && data.subcategories ? data.subcategories : [])
                                        .filter(subcat => pendingSubIds.has(String(subcat.id)));
                                    rebuildSubcategoryOptions(list, catsWithNoSub.has(selectedCat));
                                } catch (error) {
                                    console.error('Erreur lors du chargement des sous-catégories:', error);
                                }
                            }

                            applyFilters();
                        });
                    }
                } else if (categorySelect) {
                    categorySelect.addEventListener('change', applyFilters);
                }

                modalBody.appendChild(filterBar);
            }

            modalBody.appendChild(cardsContainer);
        } else {
            modalBody.innerHTML = '<p class="text-center text-muted">Aucune demande trouvée.</p>';
        }

        // Footer
        const modalFooter = document.createElement('div');
        modalFooter.className = 'modal-footer d-flex justify-content-between align-items-center';
        modalFooter.innerHTML = `
            <h6>Total à rembourser : <span id="totalToRefund">0.00</span> ${currency}</h6>
            <div>
            <button type="button" class="btn btn-primary" id="save-status-btn" disabled>Enregistrer</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        `;

        // Assembler
        modalContent.appendChild(modalHeader);
        modalContent.appendChild(modalBody);
        modalContent.appendChild(modalFooter);
        modalDialog.appendChild(modalContent);
        //Ajouter les écouteur d'événement pour les boutons d'action (accepter, refuser, en attente)
        const radioButtons = modalBody.querySelectorAll('input[type="radio"]');
        const saveButton = modalFooter.querySelector('#save-status-btn');
        radioButtons.forEach(radio => {
            radio.addEventListener('change', () => {
                let allPending = true;
                let totalToRefund = 0;

                radioButtons.forEach(r => {
                    if (!(r.id.includes('pending')) && r.checked) {
                        allPending = false;
                    }

                    if (r.id.includes('accept') && r.checked) {
                        totalToRefund += parseFloat(r.closest('.card-body').querySelector('h6').textContent.replace(/[^0-9,.-]+/g, '').replace(',', '.'));
                    }
                });

                if (!allPending) {
                    saveButton.disabled = false;
                } else {
                    saveButton.disabled = true;
                }
                document.getElementById('totalToRefund').textContent = totalToRefund.toFixed(2);

                if (totalToRefund > 0) {
                    saveButton.textContent = 'Suivant';
                } else {
                    saveButton.textContent = 'Enregistrer';
                }
            });
        });


        return modalDialog; // ✅ Retourne .modal-dialog

    } catch (error) {
        console.error('Erreur lors de la requête:', error);
        const modalDialog = document.createElement('div');
        modalDialog.className = 'modal-dialog';
        modalDialog.innerHTML = `
            <div class="modal-content">
                <div class="modal-body">
                    <p class="text-center text-danger">Erreur lors du chargement des demandes.</p>
                </div>
            </div>
        `;
        return modalDialog;
    }
}
async function generateMemberInviationsTable(organisationId) {
    try {
        const response = await fetch('/api/get-invitations.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                organisation_id: organisationId
            })
        });

        const result = await response.json();

        const table = document.createElement('table');
        table.className = 'table table-striped';

        const thead = document.createElement('thead');
        thead.innerHTML = `
            <tr>
                <th style="width: 30%;" class="text-center">Nom</th>
                <th style="width: 40%;" class="text-center">Rôles</th>
                <th style="width: 30%;" class="text-center">Action</th>
            </tr>
        `;
        table.appendChild(thead);

        const tbody = document.createElement('tbody');

        if (result.success && result.data && result.data.length > 0) {
            result.data.forEach(invitation => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="text-center">${invitation.name}</td>
                    <td class="text-center">${invitation.role}</td>
                    <td class="d-flex justify-content-center gap-2">
                        <button class="btn btn-sm btn-outline-secondary resend-invitation-btn" data-invitation-id="${invitation.id}">Renvoyer le mail</button>
                        <button class="btn btn-sm btn-outline-danger delete-invitation-btn" data-invitation-id="${invitation.id}">Supprimer</button>
                    </td>
                `;
                tbody.appendChild(tr);

                // Ajouter les event listeners
                const resendBtn = tr.querySelector('.resend-invitation-btn');
                const deleteBtn = tr.querySelector('.delete-invitation-btn');

                resendBtn.addEventListener('click', async () => {
                    try {
                        const response = await fetch('/api/resend-invitation.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                invitation_id: invitation.id
                            })
                        });
                        const result = await response.json();
                        if (result.success) {
                            alert('Email renvoyé avec succès !');
                        } else {
                            alert((result.error || 'Impossible de renvoyer l\'email'));
                        }
                    } catch (error) {
                        console.error('Erreur:', error);
                        alert('Erreur lors de l\'envoi de l\'email');
                    }
                });

                deleteBtn.addEventListener('click', async () => {
                    if (!confirm('Êtes-vous sûr de vouloir supprimer cette invitation ?')) return;
                    try {
                        const response = await fetch('/api/delete-invitation.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                invitation_id: invitation.id
                            })
                        });
                        const result = await response.json();
                        if (result.success) {
                            alert('Invitation supprimée avec succès !');
                            tr.remove();
                        } else {
                            alert((result.error || 'Impossible de supprimer l\'invitation'));
                        }
                    } catch (error) {
                        console.error('Erreur:', error);
                        alert('Erreur lors de la suppression');
                    }
                });
            });
        } else {
            const noInvitationsMessage = document.createElement('p');
            noInvitationsMessage.textContent = 'Aucune invitation en cours.';
            return noInvitationsMessage;
        }

        table.appendChild(tbody);
        return table;

    } catch (error) {
        console.error('Erreur lors de la requête:', error);
        const errorMessage = document.createElement('p');
        errorMessage.className = 'text-center text-danger';
        errorMessage.textContent = 'Erreur lors du chargement des invitations.';
        return errorMessage;
    }
}
async function generateCategoriesManagement(organisationId) {
    try {
        const response = await fetch('/api/get-categories.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                organisation_id: organisationId
            })
        });

        const result = await response.json();

        if (result.success && result.data) {
            const categoriesEnabled = !!document.getElementById('use-cat')?.checked;
            if (!categoriesEnabled) {
                const disabledMessage = document.createElement('div');
                disabledMessage.className = 'alert alert-light border text-secondary mb-0';
                disabledMessage.textContent = 'Vous devez activer les catégories pour pouvoir les modifier.';
                return disabledMessage;
            }

            const subcategoriesEnabled = !!document.getElementById('use-subcat')?.checked;
            const categoriesTable = document.createElement('table');
            categoriesTable.className = 'table table-striped';

            const thead = document.createElement('thead');
            thead.innerHTML = `
                <tr>
                    <th class="text-center" style="width: 30%;">Catégorie</th>
                    <th class="text-center ${subcategoriesEnabled ? '' : 'text-secondary'}" style="width: 40%; ${subcategoriesEnabled ? '' : 'background-color: #f8f9fa; color: #6c757d;'}">Sous-catégories</th>
                    <th class="text-center" style="width: 30%;">Actions</th>
                </tr>
            `;
            categoriesTable.appendChild(thead);

            const tbody = document.createElement('tbody');

            result.data.forEach(category => {
                const tr = document.createElement('tr');
                const subcategoryText = subcategoriesEnabled
                    ? (category.subcategories.length ? category.subcategories.map(subcat => subcat.name).join(', ') : 'Aucune sous-catégorie')
                    : 'Sous-catégories désactivées';
                const subcategoryCellClass = subcategoriesEnabled ? 'text-muted' : 'text-secondary bg-light fst-italic';

                tr.innerHTML = `
                    <td class="text-center">${category.name}</td>
                    <td class="${subcategoryCellClass} text-truncate" style="max-width: 0; overflow: hidden; ${subcategoriesEnabled ? '' : 'background-color: #f8f9fa; color: #6c757d;'}">${subcategoryText}</td>
                    <td class="d-flex justify-content-center gap-2">
                        <button class="btn btn-sm btn-outline-primary edit-category-btn" data-bs-toggle="modal" data-bs-target="#editCategoryDialog" data-category-id="${category.id}">Modifier</button>
                        <button class="btn btn-sm btn-outline-danger delete-category-btn" data-category-id="${category.id}">Supprimer</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            categoriesTable.appendChild(tbody);

            categoriesTable.querySelectorAll('.delete-category-btn').forEach(button => {
                button.addEventListener('click', async () => {
                    const categoryId = button.getAttribute('data-category-id');
                    if (confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')) {
                        const response = await fetch('/api/edit-category.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: 'delete',
                                category_id: categoryId
                            })
                        });

                        const result = await response.json();
                        if (result.success) {
                            button.closest('tr').remove();
                        } else {
                            alert((result.error || 'Impossible de supprimer la catégorie'));
                        }
                    }
                });
            });


            return categoriesTable;
        } else {
            const errorMessage = document.createElement('p');
            errorMessage.className = 'text-center text-danger';
            errorMessage.textContent = 'Erreur lors du chargement des catégories.';
            return errorMessage;
        }
    } catch (error) {
        console.error('Erreur lors de la requête:', error);
        const errorMessage = document.createElement('p');
        errorMessage.className = 'text-center text-danger';
        errorMessage.textContent = 'Erreur lors du chargement des catégories.';
        return errorMessage;
    }
}

async function loadRepaymentHistory(organisationId, page = 1) {
    const batchHistoryTable = document.getElementById('repayment-history-table');
    if (!batchHistoryTable) return;

    try {
        const response = await fetch('/api/get-repayment-batches.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ organisation_id: organisationId, page })
        });

        const result = await response.json();

        if (result.success && result.data) {
            // Vider le tableau existant
            batchHistoryTable.querySelector('tbody').innerHTML = '';

            result.data.repaymentBatches.forEach(batch => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${batch.user}</td>
                    <td>${batch.cashier}</td>
                    <td class="text-center">${batch.date}</td>
                    <td class="text-end">${batch.totalAmount.toFixed(2).replace('.', ',')} ${result.data.currency}</td>
                    <td class="text-center"><a href="/api/get-batch-confirmation.php?batch_id=${batch.id}" class="btn btn-sm btn-outline-info" target="_blank">Confirmation</a></td>
                `;
                batchHistoryTable.querySelector('tbody').appendChild(tr);
            });

            // Gestion de la pagination
            const paginationContainer = document.getElementById('repayment-history-pagination');
            if (paginationContainer) {
                paginationContainer.innerHTML = '';
                for (let i = 1; i <= result.total_pages; i++) {
                    const pageButton = document.createElement('button');
                    pageButton.className = 'btn btn-sm btn-outline-primary mx-1';
                    pageButton.textContent = i;
                    if (i === page) {
                        pageButton.disabled = true;
                    }
                    pageButton.addEventListener('click', () => loadRepaymentHistory(organisationId, i));
                    paginationContainer.appendChild(pageButton);
                }
            }
        } else {
            batchHistoryTable.querySelector('tbody').innerHTML = '<tr><td colspan="4" class="text-center text-muted">Aucun remboursement trouvé.</td></tr>';
        }
    } catch (error) {
        console.error('Erreur lors du chargement de l\'historique des remboursements:', error);
        batchHistoryTable.querySelector('tbody').innerHTML = '<tr><td colspan="4" class="text-center text-danger">Erreur lors du chargement de l\'historique.</td></tr>';
    }
}
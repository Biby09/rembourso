/**
 * Gestion des catégories et sous-catégories (admins) :
 * activation, liste, ajout, modification (nom + sous-catégories) et suppression.
 */
import { api } from '../../core/api.js';
import { $, $$, dataNumber, delegate, html, setHtml, setVisible, toElement } from '../../core/dom.js';
import { errorMessage } from '../../core/http.js';
import { confirmAction, hideModal, notify, showModal, withButtonLoading } from '../../core/ui.js';

const DISABLED_CELL_STYLE = 'background-color: #f8f9fa; color: #6c757d;';

function categoriesTableHtml(categories, subcategoriesEnabled) {
    const subcategoryText = category => {
        if (!subcategoriesEnabled) return 'Sous-catégories désactivées';
        return category.subcategories.length
            ? category.subcategories.map(subcategory => subcategory.name).join(', ')
            : 'Aucune sous-catégorie';
    };

    return html`
        <table class="table table-striped">
            <thead>
                <tr>
                    <th class="text-center" style="width: 30%;">Catégorie</th>
                    <th class="text-center${subcategoriesEnabled ? '' : ' text-secondary'}" style="width: 40%; ${subcategoriesEnabled ? '' : DISABLED_CELL_STYLE}">Sous-catégories</th>
                    <th class="text-center" style="width: 30%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                ${categories.map(category => html`
                    <tr data-category-id="${category.id}">
                        <td class="text-center">${category.name}</td>
                        <td class="${subcategoriesEnabled ? 'text-muted' : 'text-secondary bg-light fst-italic'} text-truncate"
                            style="max-width: 0; overflow: hidden; ${subcategoriesEnabled ? '' : DISABLED_CELL_STYLE}">${subcategoryText(category)}</td>
                        <td class="d-flex justify-content-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-action="edit-category">Modifier</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-action="delete-category">Supprimer</button>
                        </td>
                    </tr>`)}
            </tbody>
        </table>`;
}

// ---------------------------------------------------------------------------
// Modal de modification d'une catégorie
// ---------------------------------------------------------------------------

function subcategoryRow({ id = '', name = '' } = {}) {
    return toElement(html`
        <div class="d-flex flex-row align-items-center gap-2 w-100 justify-content-center"
            data-subcategory-row data-subcategory-id="${id}" data-original-name="${name}">
            <input type="text" class="form-control" value="${name}" placeholder="${id ? '' : 'Nouvelle sous-catégorie'}" maxlength="100" required>
            <button type="button" class="btn btn-sm btn-outline-danger" data-action="remove-subcategory">Supprimer</button>
        </div>`);
}

/**
 * Modifications de sous-catégories à envoyer au serveur, calculées à partir du formulaire :
 * nouvelles lignes -> add, noms modifiés -> update, lignes existantes retirées -> delete.
 */
function collectSubcategoryChanges(editor, deletedIds) {
    const changes = [...deletedIds].map(id => ({ action: 'delete', id }));

    $$('[data-subcategory-row]', editor).forEach(row => {
        const name = $('input', row).value.trim();
        const id = dataNumber(row, 'subcategoryId');
        if (!row.dataset.subcategoryId) {
            changes.push({ action: 'add', name });
        } else if (name !== row.dataset.originalName) {
            changes.push({ action: 'update', id, name });
        }
    });

    return changes;
}

function initEditCategoryDialog(onSaved, areSubcategoriesEnabled) {
    const dialog = $('#editCategoryDialog');
    const form = $('#edit-category-form', dialog);
    const nameInput = $('#category-name', dialog);
    const editor = $('[data-subcategories-editor]', dialog);
    const rows = $('[data-subcategory-rows]', dialog);
    const disabledNotice = $('[data-subcategories-disabled]', dialog);

    let state = null; // { categoryId, originalName, deletedIds }

    $('[data-action="add-subcategory"]', dialog).addEventListener('click', () => {
        const row = subcategoryRow();
        rows.append(row);
        $('input', row).focus();
    });

    delegate(rows, 'click', '[data-action="remove-subcategory"]', (event, button) => {
        const row = button.closest('[data-subcategory-row]');
        if (row.dataset.subcategoryId) state.deletedIds.add(dataNumber(row, 'subcategoryId'));
        row.remove();
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();

        const name = nameInput.value.trim();
        const changes = areSubcategoriesEnabled() ? collectSubcategoryChanges(rows, state.deletedIds) : [];

        if (!changes.length && name === state.originalName) {
            hideModal(dialog);
            return;
        }

        await withButtonLoading($('button[type="submit"]', dialog), 'Enregistrement...', async () => {
            const result = await api.updateCategory(state.categoryId, name, changes);
            if (!result.success) {
                notify(errorMessage(result, 'Une erreur est survenue lors de la mise à jour de la catégorie.'));
                return;
            }
            hideModal(dialog);
            onSaved();
        });
    });

    return async function open(categoryId) {
        const result = await api.getSubcategories(categoryId);
        if (!result.success) {
            notify(errorMessage(result, 'Une erreur est survenue lors du chargement des sous-catégories.'));
            return;
        }

        state = { categoryId, originalName: result.categoryName, deletedIds: new Set() };
        nameInput.value = result.categoryName;

        const enabled = areSubcategoriesEnabled();
        setVisible(editor, enabled);
        setVisible(disabledNotice, !enabled);
        rows.replaceChildren(...(enabled ? result.subcategories.map(subcategory => subcategoryRow(subcategory)) : []));

        showModal(dialog);
    };
}

// ---------------------------------------------------------------------------
// Modal d'ajout d'une catégorie
// ---------------------------------------------------------------------------

function initAddCategoryDialog(organisationId, onAdded) {
    const dialog = $('#addCategoryDialog');
    const form = $('#add-category-form', dialog);
    const nameInput = $('#new-category-name', dialog);

    dialog.addEventListener('show.bs.modal', () => form.reset());
    dialog.addEventListener('shown.bs.modal', () => nameInput.focus());

    form.addEventListener('submit', async event => {
        event.preventDefault();

        await withButtonLoading($('button[type="submit"]', dialog), 'Ajout...', async () => {
            const result = await api.addCategory(organisationId, nameInput.value.trim());
            if (!result.success) {
                notify(errorMessage(result, "Une erreur est survenue lors de l'ajout de la catégorie."));
                return;
            }
            hideModal(dialog);
            onAdded();
        });
    });

    return () => showModal(dialog);
}

// ---------------------------------------------------------------------------

export function initCategories({ organisationId }) {
    const categoriesCheckbox = $('#use-cat');
    const subcategoriesCheckbox = $('#use-subcat');
    const container = $('#categories-management-container');
    const addButton = $('#add-category-button');
    if (!categoriesCheckbox || !subcategoriesCheckbox || !container) return; // Pas admin

    const subcategoriesOption = subcategoriesCheckbox.closest('.form-check');

    function updateControls() {
        setVisible(subcategoriesOption, categoriesCheckbox.checked);
        addButton.disabled = !categoriesCheckbox.checked;
    }

    async function reload() {
        if (!categoriesCheckbox.checked) {
            setHtml(container, html`
                <div class="alert alert-light border text-secondary mb-0">Vous devez activer les catégories pour pouvoir les modifier.</div>`);
            return;
        }

        const result = await api.getCategories(organisationId);
        setHtml(container, result.success
            ? categoriesTableHtml(result.data, subcategoriesCheckbox.checked)
            : html`<p class="text-center text-danger">${errorMessage(result, 'Erreur lors du chargement des catégories.')}</p>`);
    }

    /** Enregistre l'activation des catégories / sous-catégories ; annule le changement en cas d'erreur. */
    async function saveSetting(checkbox, setting) {
        const result = await api.updateOrganisationSetting(organisationId, setting, checkbox.checked);

        if (!result.success) {
            checkbox.checked = !checkbox.checked;
            notify(errorMessage(result, "Une erreur est survenue lors de la mise à jour des paramètres de l'organisation."));
            return;
        }

        // Le serveur renvoie l'état réel (désactiver les catégories désactive aussi les sous-catégories)
        categoriesCheckbox.checked = result.categories;
        subcategoriesCheckbox.checked = result.subcategories;
        updateControls();
        reload();
    }

    categoriesCheckbox.addEventListener('change', () => saveSetting(categoriesCheckbox, 'use_cat'));
    subcategoriesCheckbox.addEventListener('change', () => saveSetting(subcategoriesCheckbox, 'use_subcat'));

    const openEditDialog = initEditCategoryDialog(reload, () => subcategoriesCheckbox.checked);
    const openAddDialog = initAddCategoryDialog(organisationId, reload);

    addButton.addEventListener('click', () => {
        if (categoriesCheckbox.checked) openAddDialog();
    });

    delegate(container, 'click', '[data-action="edit-category"]', (event, button) => {
        openEditDialog(dataNumber(button.closest('tr'), 'categoryId'));
    });

    delegate(container, 'click', '[data-action="delete-category"]', async (event, button) => {
        if (!confirmAction('Êtes-vous sûr de vouloir supprimer cette catégorie ?')) return;

        const row = button.closest('tr');
        const result = await api.deleteCategory(dataNumber(row, 'categoryId'));
        if (result.success) {
            row.remove();
        } else {
            notify(errorMessage(result, 'Impossible de supprimer la catégorie.'));
        }
    });

    updateControls();
    reload();
    reload();
}

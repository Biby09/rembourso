/**
 * Liste déroulante des sous-catégories dépendant de la catégorie choisie.
 */
import { api } from '../core/api.js';
import { setVisible } from '../core/dom.js';

/**
 * Recharge les options de `subcategorySelect` pour la catégorie sélectionnée.
 * Si la catégorie n'a pas de sous-catégorie, le select est désactivé
 * (et `container`, s'il est fourni, est masqué).
 */
export async function refreshSubcategories(categorySelect, subcategorySelect, container = null) {
    const categoryId = categorySelect.value;
    const result = categoryId ? await api.getSubcategories(categoryId) : { subcategories: [] };
    const subcategories = result.subcategories ?? [];
    const hasSubcategories = subcategories.length > 0;

    subcategorySelect.replaceChildren();
    subcategorySelect.disabled = !hasSubcategories;
    subcategorySelect.required = hasSubcategories;
    setVisible(container, hasSubcategories);

    if (!hasSubcategories) {
        subcategorySelect.append(new Option('Aucune sous-catégorie', '', true, true));
        return;
    }

    const placeholder = new Option('Choisir une sous-catégorie', '', true, true);
    placeholder.disabled = true;
    placeholder.hidden = true;
    subcategorySelect.append(placeholder, ...subcategories.map(sub => new Option(sub.name, sub.id)));
}

/**
 * Relie un select de catégorie à son select de sous-catégorie.
 * Avec `refreshNow`, charge immédiatement les sous-catégories de la catégorie déjà sélectionnée.
 */
export function bindSubcategorySelect(categorySelect, subcategorySelect, { container = null, refreshNow = false } = {}) {
    if (!categorySelect || !subcategorySelect) return;

    categorySelect.addEventListener('change', () => refreshSubcategories(categorySelect, subcategorySelect, container));

    if (refreshNow && categorySelect.value) {
        refreshSubcategories(categorySelect, subcategorySelect, container);
    }
}

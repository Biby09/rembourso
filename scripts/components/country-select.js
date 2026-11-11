/**
 * Remplit les <select class="country-select"> avec la liste des pays.
 * Le pays présélectionné est lu dans l'attribut data-country.
 */
import { api } from '../core/api.js';
import { $$ } from '../core/dom.js';

const PRIORITY_COUNTRIES = ['CH'];
const SEPARATOR = '──────────';

let countriesPromise = null;

function loadCountries() {
    countriesPromise ??= api.getCountries().then(countries =>
        [...countries].sort((a, b) => a.name.common.localeCompare(b.name.common, 'fr')));
    return countriesPromise;
}

function createOption(country, selectedCode) {
    // Sélectionnée par défaut : conservée lors d'un form.reset()
    const selected = country.cca2 === selectedCode;
    return new Option(country.name.common, country.cca2, selected, selected);
}

export async function fillCountrySelect(select, selectedCode = select.dataset.country ?? '') {
    const countries = await loadCountries();
    const priority = countries.filter(country => PRIORITY_COUNTRIES.includes(country.cca2));
    const others = countries.filter(country => !PRIORITY_COUNTRIES.includes(country.cca2));

    const separator = new Option(SEPARATOR);
    separator.disabled = true;

    select.append(
        ...priority.map(country => createOption(country, selectedCode)),
        separator,
        ...others.map(country => createOption(country, selectedCode)),
    );
}

export function initCountrySelects(root = document) {
    return Promise.all($$('.country-select', root).map(select => fillCountrySelect(select)));
}

/**
 * Point d'entrée de l'assistant de création d'une organisation (/dashboard/organisation/init-orga.php).
 * Chaque étape n'affiche que ses propres éléments : les modules ne s'activent que si leurs éléments sont présents
 * (étape 1 : devise ; étape 2 : catégories ; étape 3 : invitations).
 */
import { initCategories } from './categories.js';
import { organisationContext } from './context.js';
import { initCurrency } from './currency.js';
import { initMembers } from './member-management.js';

const context = organisationContext();
initCurrency(context);
initCategories(context);
initMembers(context);

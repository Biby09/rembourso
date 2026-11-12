/**
 * Point d'entrée de la page « Membres » (/dashboard/organisation/members/).
 */
import { organisationContext } from './context.js';
import { initMembers } from './member-management.js';

initMembers(organisationContext());

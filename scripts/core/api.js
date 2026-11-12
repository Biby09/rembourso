/**
 * Appels aux endpoints de /api, regroupés en un seul endroit.
 * Les endpoints sont rangés par domaine, comme les classes PHP : api/<domaine>/<action>.php
 * Chaque fonction retourne la réponse JSON du serveur ({ success, ... }).
 */
import { postForm, postJson, postJsonForHtml } from './http.js';

/** Adresse d'un endpoint : endpoint('repayment/submit-request') → /api/repayment/submit-request.php */
export const endpoint = name => `/api/${name}.php`;

export const api = {
    // --- Compte (les formulaires de « Mon compte » utilisent endpoint('account/…') avec submitForm)
    checkEmail: email => postJson(endpoint('account/check-email'), { email }),

    // --- Organisation
    createOrganisation: form => postForm(endpoint('organisation/create-organisation'), form),
    renameOrganisation: (organisationId, name) =>
        postJson(endpoint('organisation/update-organisation'), { organisation_id: organisationId, action: 'rename', name }),
    deleteOrganisation: organisationId =>
        postJson(endpoint('organisation/update-organisation'), { organisation_id: organisationId, action: 'delete' }),
    updateOrganisationSetting: (organisationId, setting, value) =>
        postJson(endpoint('organisation/update-settings'), { organisation_id: organisationId, setting, value }),

    // --- Demandes de remboursement
    getMemberPendingRepayments: (organisationId, memberId) =>
        postJson(endpoint('repayment/get-requests'), { organisation_id: organisationId, memberId }),
    getRepaymentEditDialog: repaymentId => postJsonForHtml(endpoint('repayment/edit-dialog'), { repayment_id: repaymentId }),
    deleteRepayment: repaymentId => postJson(endpoint('repayment/delete-request'), { repayment_id: repaymentId }),
    receiptUrl: (repaymentId, index = 0) =>
        `${endpoint('repayment/get-receipt')}?repayment_id=${encodeURIComponent(repaymentId)}&receipt_index=${index}`,

    // --- Caisse
    getMembersWithPendingRepayments: organisationId =>
        postJson(endpoint('repayment/get-pending-members'), { organisation_id: organisationId }),
    processRepayments: (organisationId, action, updates) =>
        postJson(endpoint('repayment/process'), { organisation_id: organisationId, action, updates }),
    getRepaymentBatches: (organisationId, page) =>
        postJson(endpoint('batch/get-batches'), { organisation_id: organisationId, page }),
    batchConfirmationUrl: batchId => `${endpoint('batch/confirmation')}?batch_id=${encodeURIComponent(batchId)}`,

    // --- Membres et invitations
    inviteMember: (organisationId, email, role) =>
        postJson(endpoint('member/invite-member'), { organisation_id: organisationId, email, role }),
    updateMemberRole: (organisationId, memberId, role) =>
        postJson(endpoint('member/edit-member'), { organisation_id: organisationId, member_id: memberId, action: 'update', new_role: role }),
    removeMember: (organisationId, memberId) =>
        postJson(endpoint('member/edit-member'), { organisation_id: organisationId, member_id: memberId, action: 'delete' }),
    getInvitations: organisationId => postJson(endpoint('invitation/get-invitations'), { organisation_id: organisationId }),
    resendInvitation: invitationId => postJson(endpoint('invitation/resend-invitation'), { invitation_id: invitationId }),
    deleteInvitation: invitationId => postJson(endpoint('invitation/cancel-invitation'), { invitation_id: invitationId }),
    respondToInvitation: ({ accept, token = null, id = null }) =>
        postJson(endpoint('invitation/respond-invitation'), { accept, token, id }),

    // --- Personnes sans compte (bénéficiaires saisis par un caissier)
    getAccountlessMembers: organisationId =>
        postJson(endpoint('accountless/get-people'), { organisation_id: organisationId }),
    updateAccountlessMember: (organisationId, personId, person) =>
        postJson(endpoint('accountless/update-person'), { organisation_id: organisationId, person_id: personId, person }),
    sendAccountClaim: (organisationId, personId) =>
        postJson(endpoint('accountless/send-claim'), { organisation_id: organisationId, person_id: personId }),

    // --- Catégories
    getCategories: organisationId => postJson(endpoint('category/get-categories'), { organisation_id: organisationId }),
    getSubcategories: categoryId => postJson(endpoint('category/get-subcategories'), { categoryId }),
    addCategory: (organisationId, name) =>
        postJson(endpoint('category/edit-category'), { organisation_id: organisationId, action: 'add', name }),
    deleteCategory: categoryId => postJson(endpoint('category/edit-category'), { category_id: categoryId, action: 'delete' }),
    updateCategory: (categoryId, name, subcategoryChanges) =>
        postJson(endpoint('category/update-category'), { category_id: categoryId, name, subcategories: subcategoryChanges }),

    // --- Divers
    getCountries: async () => {
        try {
            const response = await fetch(endpoint('country/get-countries'));
            return await response.json();
        } catch {
            return [];
        }
    },
};

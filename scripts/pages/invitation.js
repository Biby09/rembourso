/**
 * Page /invitation : accepter ou refuser une invitation reçue par e-mail.
 */
import { respondToInvitation } from '../components/invitation-response.js';
import { $, delegate } from '../core/dom.js';
import { setButtonLoading } from '../core/ui.js';

const container = $('[data-invitation-token]');

delegate(container, 'click', '[data-invitation-response]', async (event, button) => {
    setButtonLoading(button, true, 'Envoi...');
    const result = await respondToInvitation({
        accept: button.dataset.invitationResponse === 'accept',
        token: container.dataset.invitationToken,
    });
    if (!result.success) setButtonLoading(button, false);
});

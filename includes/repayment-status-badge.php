<?php
/*
    Badge du statut d'une demande (En attente / Acceptée / Refusée), suivi du badge « Urgent » si la demande
    est urgente et encore en attente.
    Même rendu que repaymentStatus() de scripts/components/repayment-accordion.js.
    Prérequis : $request (RepaymentRequest).
*/
$statusColor = [RepaymentRequest::STATUS_PENDING => 'warning', RepaymentRequest::STATUS_ACCEPTED => 'success'][$request->getStatus()] ?? 'danger';
?>
<span class="badge bg-<?= $statusColor ?> me-2"><?= e($request->getStatusLabel()) ?></span>
<?php if ($request->isUrgent() && $request->isPending()): ?>
    <span class="badge bg-danger me-2"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i> Urgent</span>
<?php endif; ?>

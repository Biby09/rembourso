<?php
/* Assistant de configuration, étape 2 : catégories (inclus par dashboard/organisation/init-orga.php). */
?>
<h1 class="fs-3">Classer les dépenses</h1>
<p>Les catégories regroupent les dépenses du même type (matériel, transport, repas…) ; les sous-catégories les précisent
    (par exemple « Train » et « Essence » dans « Transport »). Vos membres choisiront la catégorie en déposant une demande,
    et vos comptes seront plus faciles à tenir.</p>
<p class="text-muted">Facultatif : sans catégories, les demandes ne sont simplement pas classées.</p>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/category-setup.php'; ?>

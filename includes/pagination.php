<?php
/*
    Pagination par liens (?page=N) : page précédente, numéros de page, page suivante.
    Rien n'est affiché s'il n'y a qu'une page.

    Prérequis : $page (page affichée), $pageCount, $pageUrl (fn(int $page): string, adresse d'une page).
*/
if ($pageCount <= 1) {
    return;
}
?>
<nav class="mt-4" aria-label="Pagination">
    <ul class="pagination justify-content-center flex-wrap">
        <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
            <a class="page-link" href="<?= e($pageUrl(max(1, $page - 1))) ?>" aria-label="Page précédente">&laquo;</a>
        </li>
        <?php for ($number = 1; $number <= $pageCount; $number++): ?>
            <li class="page-item<?= $number === $page ? ' active' : '' ?>">
                <a class="page-link" href="<?= e($pageUrl($number)) ?>" <?= $number === $page ? 'aria-current="page"' : '' ?>><?= $number ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item<?= $page >= $pageCount ? ' disabled' : '' ?>">
            <a class="page-link" href="<?= e($pageUrl(min($pageCount, $page + 1))) ?>" aria-label="Page suivante">&raquo;</a>
        </li>
    </ul>
</nav>

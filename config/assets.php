<?php

/**
 * Adresse d'un fichier statique du site (CSS, JS) suivie de ?v=<date de modification> :
 * quand le fichier change, son adresse change et les navigateurs ne réutilisent pas l'ancienne copie en cache.
 * Les modules importés par un autre module n'ont pas ce paramètre : .htaccess leur impose une revalidation
 * (Cache-Control: no-cache).
 */
function asset(string $path): string
{
    $modified = @filemtime($_SERVER['DOCUMENT_ROOT'] . $path);
    return $modified ? $path . '?v=' . $modified : $path;
}

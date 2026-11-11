<?php

/**
 * Transactions « tout ou rien » pour les Managers (trait : code partagé, inclus avec « use Transactional; »).
 * La classe qui l'utilise doit avoir une propriété $pdo.
 *
 * Si une transaction est déjà ouverte (ex. un endpoint qui crée une personne sans compte PUIS sa demande),
 * le code s'exécute simplement dedans : c'est la transaction englobante qui valide ou annule le tout.
 */
trait Transactional
{
    /**
     * Exécute $work dans une transaction et renvoie son résultat.
     * En cas d'exception, tout est annulé et l'exception est relancée.
     */
    protected function transaction(callable $work): mixed
    {
        if ($this->pdo->inTransaction()) {
            return $work();
        }

        $this->pdo->beginTransaction();
        try {
            $result = $work();
            $this->pdo->commit();
            return $result;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }
}

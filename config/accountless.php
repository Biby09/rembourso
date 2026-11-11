<?php
/**
 * Personnes « sans compte » : bénéficiaires saisis par un caissier ou un administrateur
 * (validation de la saisie et contrôle des doublons d'e-mail). À inclure après config/api-auth.php.
 */

/** Champs saisis par le caissier : nom du champ => [libellé, longueur max]. */
const ACCOUNTLESS_FIELDS = [
    'first_name' => ['Prénom', 64],
    'last_name' => ['Nom', 64],
    'address' => ['Adresse', 256],
    'postal_code' => ['Code postal', 16],
    'city' => ['Ville', 64],
    'country' => ['Pays', 64],
    'iban' => ['IBAN', 64],
    'email' => ['E-mail', 128],
];

/**
 * Valide les informations d'une personne sans compte, ou répond 400.
 * L'IBAN est renvoyé au format machine, l'e-mail vaut null s'il est vide.
 *
 * @return array{first_name: string, last_name: string, address: string, postal_code: string, city: string, country: string, iban: string, email: ?string}
 */
function accountless_person_input(mixed $input): array
{
    $input = is_array($input) ? $input : [];
    $person = [];

    foreach (ACCOUNTLESS_FIELDS as $field => [$label, $maxLength]) {
        $value = trim((string) ($input[$field] ?? ''));
        if ($value === '' && $field !== 'email') {
            json_error("Le champ « $label » de la personne est obligatoire.");
        }
        if (mb_strlen($value) > $maxLength) {
            json_error("Le champ « $label » est trop long ($maxLength caractères maximum).");
        }
        $person[$field] = $value;
    }

    $person['iban'] = iban_to_machine_format($person['iban']);
    if (!verify_iban($person['iban'])) {
        json_error('IBAN invalide.');
    }

    if ($person['email'] === '') {
        $person['email'] = null;
    } elseif (filter_var($person['email'], FILTER_VALIDATE_EMAIL) === false) {
        json_error('Adresse e-mail invalide.');
    }

    return $person;
}

/**
 * Refuse un e-mail déjà associé à un compte (la personne doit être invitée dans l'organisation)
 * ou à une autre personne sans compte de la même organisation.
 */
function accountless_assert_email_usable(?string $email, Organisation $organisation, OrganisationManager $organisationManager, UserManager $userManager, ?int $exceptUserId = null): void
{
    if ($email === null) {
        return;
    }

    if (!$userManager->isEmailAvailable($email)) {
        json_error("Cette personne a déjà un compte Rembourso. Demandez à un administrateur de l'inviter dans l'organisation, puis sélectionnez-la dans la liste.");
    }

    foreach ($organisationManager->getAccountlessMembers($organisation) as ['user' => $member]) {
        if ($member->getId() !== $exceptUserId && $member->getEmail() !== null && strcasecmp($member->getEmail(), $email) === 0) {
            json_error("Une personne sans compte avec cette adresse e-mail existe déjà dans l'organisation : sélectionnez-la dans la liste.");
        }
    }
}

/** Données d'une personne sans compte envoyées aux caissiers (JSON). */
function accountless_person_data(User $person, ?string $lastClaimSentAt = null): array
{
    return [
        'id' => $person->getId(),
        'first_name' => $person->getFirstName(),
        'last_name' => $person->getLastName(),
        'name' => $person->getFullName(),
        'address' => $person->getAddress(),
        'postal_code' => $person->getPostalCode(),
        'city' => $person->getCity(),
        'country' => $person->getCountry(),
        'iban' => iban_to_human_format($person->getIban()),
        'email' => $person->getEmail(),
        'last_claim_sent_at' => $lastClaimSentAt ? substr($lastClaimSentAt, 0, 10) : null,
    ];
}

/** Personne sans compte, active, membre de l'organisation ; sinon 404. */
function accountless_load(OrganisationManager $organisationManager, UserManager $userManager, Organisation $organisation, mixed $id): User
{
    $person = api_find(fn() => $userManager->getUserById(api_id($id, 'Personne introuvable.')), 'Personne introuvable.');
    if ($person->isRegistered() || !$person->isActive() || !$organisationManager->isMember($organisation, $person->getId())) {
        json_error('Personne introuvable.', 404);
    }
    return $person;
}

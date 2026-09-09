<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use Symfony\Component\Intl\Countries;

header('Content-Type: application/json; charset=utf-8');

$names = Countries::getNames('fr');
asort($names, SORT_LOCALE_STRING);

$countries = [];
foreach ($names as $code => $name) {
    $countries[] = ['cca2' => $code, 'name' => ['common' => $name]];
}

echo json_encode($countries);

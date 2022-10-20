<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

use function Symfony\Component\String\s;

$companies = App\Generator::generate(100);
$users = App\Generator::generate(100);


$term = 'alex';

function getUsers($users, $term)
{
    $result = collect($users)->filter(
        fn($user) => empty($term) ? true : s($user['firstName'])->ignoreCase()->startsWith($term)
    );
    return $result;

}

function getQuestions(string $text)
{
    $result = collect(s($text)->split("\n"))
        ->map(fn($line) => $line->trim())
        ->filter(fn ($line) => $line->endsWith('?'))
        ->toArray();
    return implode("\n", $result);
}


$r1 = getUsers($users, $term);
foreach ($r1 as $user) {
    $r3[] = $user;
}
$r2 = '';
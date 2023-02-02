<?php

$file = file_get_contents(__DIR__ . '/../public/users.txt');
$file = json_decode($file, true);
$result = [];

foreach ($file as ['name' => $name, 'email' => $email]) {
    $k = key($file);
    $result[$k] = ['name' => $name, 'email' => $email, 'id' => $k];
    next($file);
}
file_put_contents(__DIR__ . '/../public/users.txt', json_encode($result));

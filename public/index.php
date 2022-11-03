<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;

require __DIR__ . '/../vendor/autoload.php';

session_save_path(__DIR__ . '/../src/session');
session_start()
$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);
$app->add(MethodOverrideMiddleware::class);

$users = [
    ['name' => 'admin', 'passwordDigest' => hash('sha256', 'secret')],
    ['name' => 'mike', 'passwordDigest' => hash('sha256', 'superpass')],
    ['name' => 'kate', 'passwordDigest' => hash('sha256', 'strongpass')]
];

// BEGIN (write your solution here)
$app->get('/', function ($request, $response) {
    $flash = $this->get('flash')->getMessages();
        $params = [
        'flash' => $flash,
        'currentUser' => $_SESSION['user'] ?? null
        ];
        return $this->get('renderer')->render($response, 'index.phtml', $params);
});

$app->post('/session', function ($request, $response) use ($users) {
    $user = $request->getParsedBodyParam('user');
    $pwd = hash('sha256', $user['password']);

    $goodUser = collect($users)->firstWhere('name', $user['name']);
    if (is_null($goodUser)) {
        $this->get('flash')->addMessage('success', 'Wrong password or name');
        return $response->withRedirect('/');
    } elseif ($pwd !== $goodUser['passwordDigest']) {
        $this->get('flash')->addMessage('success', 'Wrong password or name');
        return $response->withRedirect('/');
    }
    $_SESSION['user'] = $user['name'];
    return $response->withRedirect('/');
});
$app->delete('/session', function ($request, $response) {
    $_SESSION = [];
    session_destroy();

    return $response->withRedirect('/');
});
// END

$app->run();

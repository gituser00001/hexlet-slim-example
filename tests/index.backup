<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

use function Symfony\Component\String\s;

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$companies = App\Generator::generate(100);
$users = App\Generator::generate(100);


$app->get('/', function ($request, $response) {
    return $response->write('go to the /companies');
});


$app->get('/companies', function ($request, $response) use ($companies) {
    $page = $request->getQueryParam('page', 1);
    $per = $request->getQueryParam('per', 5);
    $offset = $page === 1 ? 0 : ($page * $per) - 1;
    $length = $per;
    $list = json_encode(array_slice($companies, $offset, $length));

    return $response->write($list);
});

$app->get('/companies/{id}', function ($request, $response, array $args) use ($companies) {
    $id = $args['id'];
    $username = collect($companies)->firstWhere('id', $id);
    if (!$username) {
        return $response->withStatus(404)->write('Page not found');
    } else {
        return $response->write(json_encode($username));
    }
});
/*
$app->get('/users', function ($request, $response) use ($users) {

    $term = $request->getQueryParam('term') ?? '';
    if (!empty($term)) {
        $users = collect($users)
        ->filter(fn($user) =>
            s($user['firstName'])->lower()->startsWith($term))
            ->toArray();
    }
    $params = ['users' => $users, 'term' => $term];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
});
*/


/*
$app->get('/users/{id}', function ($request, $response, $args) use ($companies) {
    $id = $args['id'];
    $username = collect($companies)->firstWhere('id', $id);
    if (!$username) {
        return $response->withStatus(404)->write('Page not found');
    } else {
        $params = ['id' => $args['id'], 'username' => $username];
        return $this->get('renderer')->render($response, 'users/show.phtml', $params);
    }
});
*/

$app->post('/users', function ($request, $response) {
    $user = $request->getParsedBodyParam('user');
    if ($user) {
        $user['id'] = uniqid();
        $fileName = __DIR__ . '/../src/users.txt';
        $file = json_decode(file_get_contents($fileName)) ?? [];
        $file[] = $user;
        file_put_contents($fileName, json_encode($file));
        return $response->withRedirect('/users', 302);
    }
    $params = [
        'user' => $user,
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['name' => '', 'email' => '']
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});

$app->get('/users', function ($request, $response) {
    $params = [
        'user' => ['name' => '', 'email' => '']
    ];

    return $this->get('renderer')->render($response, "users/index.phtml", $params);
});
$app->run();

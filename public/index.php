<?php

use Slim\Factory\AppFactory;
use DI\Container;

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

// Устанавливаем путь к файлам сессий
$sessionPath = __DIR__ . '/../var/sessions/';
session_save_path($sessionPath);
// Старт PHP сессии
session_start();

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
    // Благодаря пакету slim/http этот же код можно записать короче
    // return $response->write('Welcome to Slim!');
});

$app->get('/users', function ($request, $response) {
    //$t = $request->getHeaders();
    //$s = $request->getUri();
    //$l = $request->getHeaderLine('Host');
    //$v = $request->getServerParams();
    $users = file_get_contents('users.txt');
    $users = json_decode($users, true);
    $term = $request->getQueryParam('term');
    if (!empty($term)) {
        $filteredUsers = array_filter($users, fn($u) => str_contains($u['name'], $term));
        $users = $filteredUsers;
    }
    $messages = $this->get('flash')->getMessages();

    $params = [
        'users' => $users,
        'messages' => $messages
    ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');

$app->get('/user/{id}', function ($request, $response, $args) {
    // Собираем юзеров из файла в массив
    $users = json_decode(file_get_contents('users.txt'), true);
    // Фильтруем юзеров по входящему id
    $user = $users[$args['id']];
    if (is_null($user)) {
        return $response->write('No user!!!')->withStatus(404);
    }


    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('user');

// Получаем роутер – объект отвечающий за хранение и обработку маршрутов
$router = $app->getRouteCollector()->getRouteParser();

$app->post('/users', function ($request, $response) use ($router) {
    $user = $request->getParsedBodyParam('user');
    // пслевдорандом с привязкой к имени и емайлу
    srand(crc32($user['name'] . $user['email']));
    $id = rand();
    $users = file_get_contents('users.txt', true) ?: "{}";
    $users = json_decode($users, true);
    $users[$id] = $user;
    $params = ['user' => $user];
    //пишем данные формы в файл
    file_put_contents('users.txt', json_encode($users));
    $this->get('flash')->addMessage('success', 'user added successfully');
    return $response->withRedirect($router->urlFor('users'), 302);
});

$app->get('/users/new', function ($request, $response) {

    return $this->get('renderer')->render($response, 'users/new.phtml');
});


$app->run();

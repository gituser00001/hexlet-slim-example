<?php

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;

require __DIR__ . '/../vendor/autoload.php';

session_save_path(__DIR__ . '/../src/session');
session_start();

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);

  $app->get('/', function ($request, $response) {
    $flash = $this->get('flash')->getMessages();
        $params = [
        'flash' => $flash,
        'currentUser' => $_SESSION['user'] ?? null
        ];
        return $this->get('renderer')->render($response, 'index.phtml', $params);
  });

  $app->get('/users', function ($request, $response) {
    $fileName = __DIR__ . '/../src/users.txt';
    $file = json_decode(file_get_contents($fileName), true) ?? [];
    $flash = $this->get('flash')->getMessages();
    $params = [
      'users' => $file,
      'flash' => $flash
    ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
  })->setName('users');

  $app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['name' => '', 'email' => ''],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
  });

  $app->post('/users', function ($request, $response) {
    $user = $request->getParsedBodyParam('user');
    $validator = new App\Validator();
    $errors = $validator->validate($user);

    if (count($errors) === 0) {
        $user['id'] = uniqid();
        $fileName = __DIR__ . '/../src/users.txt';
        $file = json_decode(file_get_contents($fileName)) ?? [];
        $file[] = $user;
        file_put_contents($fileName, json_encode($file));
        $this->get('flash')->addMessage('success', 'User Added');
        return $response->withRedirect('/', 302);
    }

    $params = [
      'user' => $user,
      'errors' => $errors
    ];
    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
  });


  $app->get('/users/{id}', function ($request, $response, $args) {
    $id = $args['id'];
    $fileName = __DIR__ . '/../src/users.txt';
    $file = json_decode(file_get_contents($fileName), true) ?? [];
    $user = array_values(array_filter($file, fn ($v) => $id === $v['id']));
    if (!$user) {
        return $response->withStatus(404)->write('Page not found');
    } else {
        $params = [
          'username' => $user[0],
        ];
        return $this->get('renderer')->render($response, 'users/show.phtml', $params);
    }
  });



  $app->get('/users/{id}/edit', function ($request, $response, array $args) {
    $id = $args['id'];
    $fileName = __DIR__ . '/../src/users.txt';
    $file = json_decode(file_get_contents($fileName), true) ?? [];
    $user = array_values(array_filter($file, fn ($v) => $id === $v['id']));
    $flash = $this->get('flash')->getMessages();
    $params = [
        'user' => $user[0],
        'errors' => [],
        'flash' => $flash
    ];
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
  })->setName('editUser');

  $router = $app->getRouteCollector()->getRouteParser();

  $app->patch('/users/{id}', function ($request, $response, array $args) use ($router) {
    $id = $args['id'];
    $fileName = __DIR__ . '/../src/users.txt';
    $file = json_decode(file_get_contents($fileName), true) ?? [];
    $user = array_values(array_filter($file, fn ($v) => $id === $v['id']));
    $user = $user[0];
    $data = $request->getParsedBodyParam('user');

    $validator = new App\Validator();
    $errors = $validator->validate($data);

    if (count($errors) === 0) {
        // Ручное копирование данных из формы в нашу сущность
        $user['name'] = $data['name'];

        $this->get('flash')->addMessage('success', 'User has been updated');
        $newFile = array_map(function ($value) use ($user) {
            if ($value['id'] === $user['id']) {
                $value['name'] = $user['name'];
            }
            return $value;
        }, $file);
        file_put_contents($fileName, json_encode($newFile));
        $url = $router->urlFor('editUser', ['id' => $user['id']]);
        return $response->withRedirect($url);
    }

    $params = [
      'user' => $user,
      'errors' => $errors
    ];

    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
  });

  $app->delete('/users/{id}', function ($request, $response, array $args) use ($router) {
    $id = $args['id'];
    $fileName = __DIR__ . '/../src/users.txt';
    $file = json_decode(file_get_contents($fileName), true) ?? [];
    $user = array_values(array_filter($file, fn ($v) => $id === $v['id']));
    $user = $user[0];
    $newFile = array_filter($file, fn ($v) => $v['id'] !== $user['id']);
    file_put_contents($fileName, json_encode($newFile));
    $this->get('flash')->addMessage('success', 'User has been deleted');
    return $response->withRedirect($router->urlFor('users'));
  });

  $app->post('/session', function ($request, $response) {
    $user = $request->getParsedBodyParam('user');
    $fileName = __DIR__ . '/../src/users.txt';
    $file = json_decode(file_get_contents($fileName), true) ?? [];

    $goodUser = collect($file)->first(fn ($v) => $v['email'] === $user['email']);
    if (is_null($goodUser)) {
        $this->get('flash')->addMessage('success', 'Wrong email');
        return $response->withRedirect('/');
    }
    $_SESSION['user'] = $user['email'];
    return $response->withRedirect('/');
  });
  $app->delete('/session', function ($request, $response) {
    $_SESSION = [];
    session_destroy();

    return $response->withRedirect('/');
  });


    $app->run();

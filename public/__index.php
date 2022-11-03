<?php

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;

require __DIR__ . '/../vendor/autoload.php';

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
    $params = ['flash' => $flash];
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
    /*
    if (count($errors) === 0) {
        $user['id'] = uniqid();
        $fileName = __DIR__ . '/../src/users.txt';
        $file = json_decode(file_get_contents($fileName)) ?? [];
        $file[] = $user;
        file_put_contents($fileName, json_encode($file));
        $this->get('flash')->addMessage('success', 'User Added');
        return $response->withRedirect('/', 302);
    }
    */
    if (count($errors) === 0) {
        $user['id'] = uniqid();
        $users = json_decode($request->getCookieParam('users', json_encode([])));
        $users[] = $user;
        $encodedUsers = json_encode($users);
        $this->get('flash')->addMessage('success', 'User Added');
        return $response->withHeader('Set-Cookie', "users={$encodedUsers}")
        ->withRedirect('/');
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
    $this->get('flash')->addMessage('success', 'School has been deleted');
    return $response->withRedirect($router->urlFor('users'));
  });

// Templates/posts
  $repo = new App\PostRepository();
  $router = $app->getRouteCollector()->getRouteParser();

  $app->get('/posts', function ($request, $response) use ($repo) {
    /*
    $page = $request->getQueryParam('page', 1);
    $allPosts = $repo->all();

    $offset =  $page * 5 - 5;
    $listPages = array_slice($allPosts, $offset, 5);

    $prevPage = $page === 1 ? '' : $page - 1;
    $nextPage = $page + 1;

    $params = ['posts' => $listPages, 'prev' => $prevPage, 'next' => $nextPage];
    */
    $flash = $this->get('flash')->getMessages();

    $params = [
        'flash' => $flash,
        'posts' => $repo->all()
    ];
    return $this->get('renderer')->render($response, 'posts/index.phtml', $params);
  })->setName('posts');

  $app->get('/posts/new', function ($request, $response) {
    $params = [
      'postData' => [],
      'errors' => []
    ];
    return $this->get('renderer')->render($response, 'posts/new.phtml', $params);
  })->setName('newPost');

  $app->post('/posts', function ($request, $response) use ($router, $repo) {
    // Извлекаем данные формы
    $postData = $request->getParsedBodyParam('post');

    $validator = new App\PostValidator();
    // Проверяем корректность данных
    $errors = $validator->validate($postData);

    if (count($errors) === 0) {
        // Если данные корректны, то сохраняем, добавляем флеш и выполняем редирект
        $repo->save($postData);
        $this->get('flash')->addMessage('success', 'School has been created');
        // Обратите внимание на использование именованного роутинга
        $url = $router->urlFor('posts');
        return $response->withRedirect($url);
    }

    $params = [
        'postData' => $postData,
        'errors' => $errors
    ];

    // Если возникли ошибки, то устанавливаем код ответа в 422 и рендерим форму с указанием ошибок
    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'posts/new.phtml', $params);
  });





  $app->get('/posts/{id}', function ($request, $response, $args) use ($repo) {
    $id = $args['id'];
    $allPosts = $repo->all();
    $pageId = collect($allPosts)->firstWhere('id', $id);
    if (!$pageId) {
        return $response->withStatus(404)->write('Page not found');
    } else {
        $params = ['post' => $pageId];
        return $this->get('renderer')->render($response, "posts/show.phtml", $params);
    }
  });

    $app->run();

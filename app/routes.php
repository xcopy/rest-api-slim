<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\UserController;
use App\Http\JsonResponse;
use App\Middlewares\AuthenticationMiddleware;
use App\Middlewares\LowerFilterMiddleware;
use App\Middlewares\RateLimitMiddleware;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    /** @var Container $container */
    $container = $app->getContainer();

    $auth = AuthenticationMiddleware::class;
    $lowerFilter = new LowerFilterMiddleware('email');
    $rateLimit = fn(string $bucket) => $container->make(RateLimitMiddleware::class, compact('bucket'));

    // health
    $app->get('/health', function (Request $request, Response $response) {
        return JsonResponse::write($response, ['status' => 'ok']);
    });

    // auth
    $app->group('/auth', function (RouteCollectorProxy $group) use ($auth, $lowerFilter, $rateLimit) {
        $controllerClass = AuthController::class;

        $group
            ->post('/register', [$controllerClass, 'register'])
            ->add($rateLimit('auth.register'))
            ->add($lowerFilter);
        $group
            ->post('/login', [$controllerClass, 'login'])
            ->add($rateLimit('auth.login.email'))
            ->add($rateLimit('auth.login.ip'))
            ->add($lowerFilter);
        $group
            ->post('/logout', [$controllerClass, 'logout'])
            ->add($auth);
        $group
            ->post('/logout-all', [$controllerClass, 'logoutAll'])
            ->add($auth);
    });

    // email-verification
    $app->group('/email-verification', function (RouteCollectorProxy $group) use ($auth, $rateLimit) {
        $controllerClass = EmailVerificationController::class;

        $group
            ->post('/send', [$controllerClass, 'send'])
            ->add($rateLimit('email-verification.send'))
            ->add($auth);
        $group->post('/verify', [$controllerClass, 'verify'])
            ->add($rateLimit('email-verification.verify'))
            ->setName('email-verification.verify');
    });

    // password
    $app->group('/password', function (RouteCollectorProxy $group) use ($auth, $lowerFilter, $rateLimit) {
        $controllerClass = PasswordController::class;

        $group
            ->post('/change', [$controllerClass, 'change'])
            ->add($rateLimit('password.change'))
            ->add($auth);
        $group
            ->post('/forgot', [$controllerClass, 'forgot'])
            ->add($rateLimit('password.forgot'))
            ->add($lowerFilter);
        $group->post('/reset', [$controllerClass, 'reset'])
            ->add($rateLimit('password.reset'))
            ->setName('password.reset');
    });

    // users
    $app
        ->group('/user', function (RouteCollectorProxy $group) {
            $controllerClass = UserController::class;

            $group->get('', [$controllerClass, 'show']);
            $group->map(['POST', 'PATCH'], '', [$controllerClass, 'update']);
            $group->delete('', [$controllerClass, 'delete']);
        })
        ->add($auth);
};

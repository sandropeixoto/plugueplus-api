<?php
declare(strict_types=1);

use Dotenv\Dotenv;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;

require __DIR__ . '/../vendor/autoload.php';

class SimpleContainer implements ContainerInterface
{
    private array $entries = [];

    public function __construct()
    {
        // Register controllers that need container resolution.
        $this->entries['App\\Controllers\\ServiceController'] = fn() => new App\Controllers\ServiceController();
    }

    public function get(string $id)
    {
        if ($this->has($id)) {
            $entry = $this->entries[$id];
            return is_callable($entry) ? $entry() : $entry;
        }

        throw new RuntimeException("Entry {$id} not found in container.");
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->entries);
    }
}

$container = new SimpleContainer();
AppFactory::setContainer($container);

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$app = AppFactory::create();
$basePath = $_ENV['APP_BASE_PATH'] ?? '';
if ($basePath === '') {
    // Auto-detect base path from script name (e.g., /plugueplus-api/public/index.php).
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $directory = rtrim(str_replace('/index.php', '', $scriptName), '/');
    $basePath = $directory !== '' ? $directory : '';
}
$app->setBasePath($basePath);
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(
    filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL),
    true,
    true
);

$app->get('/api/v1/ping', function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
    $payload = [
        'success' => true,
        'data' => ['pong' => true],
        'message' => 'API online'
    ];

    $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_SLASHES));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->group('/api/v1', function (RouteCollectorProxy $group): void {
    // Auth
    $group->post('/auth/register', 'App\\Controllers\\AuthController:register');
    $group->post('/auth/login', 'App\\Controllers\\AuthController:login');
    $group->get('/auth/me', 'App\\Controllers\\AuthController:me');

    // Users
    $group->get('/users', 'App\\Controllers\\UserController:index');
    $group->get('/users/{id}', 'App\\Controllers\\UserController:show');
    $group->put('/users/{id}', 'App\\Controllers\\UserController:update');

    // Categories
    $group->get('/categories', 'App\\Controllers\\CategoryController:index');
    $group->post('/categories', 'App\\Controllers\\CategoryController:store');
    $group->put('/categories/{id}', 'App\\Controllers\\CategoryController:update');
    $group->delete('/categories/{id}', 'App\\Controllers\\CategoryController:destroy');

    // Services
    $group->get('/services', [App\Controllers\ServiceController::class, 'index']);
    $group->get('/services/{id}', [App\Controllers\ServiceController::class, 'show']);
    $group->post('/services', [App\Controllers\ServiceController::class, 'store']);
    $group->put('/services/{id}', [App\Controllers\ServiceController::class, 'update']);
    $group->delete('/services/{id}', [App\Controllers\ServiceController::class, 'destroy']);

    // Charging Points
    $group->get('/charging-points', 'App\\Controllers\\ChargingPointController:index');
    $group->get('/charging-points/{id}', 'App\\Controllers\\ChargingPointController:show');
    $group->post('/charging-points', 'App\\Controllers\\ChargingPointController:store');
    $group->put('/charging-points/{id}', 'App\\Controllers\\ChargingPointController:update');
    $group->delete('/charging-points/{id}', 'App\\Controllers\\ChargingPointController:destroy');

    // Reviews
    $group->get('/reviews', 'App\\Controllers\\ReviewController:index');
    $group->post('/reviews', 'App\\Controllers\\ReviewController:store');

    // Posts (social feed)
    $group->get('/posts', 'App\\Controllers\\PostController:index');
    $group->get('/posts/{id}', 'App\\Controllers\\PostController:show');
    $group->post('/posts', 'App\\Controllers\\PostController:store');
    $group->delete('/posts/{id}', 'App\\Controllers\\PostController:destroy');
    $group->post('/posts/{id}/like', 'App\\Controllers\\PostController:like');
    $group->delete('/posts/{id}/like', 'App\\Controllers\\PostController:unlike');
    $group->post('/posts/{id}/comment', 'App\\Controllers\\PostController:comment');
    $group->get('/posts/{id}/comments', 'App\\Controllers\\PostController:comments');

    // Classifieds
    $group->get('/classifieds', 'App\\Controllers\\ClassifiedController:index');
    $group->get('/classifieds/{id}', 'App\\Controllers\\ClassifiedController:show');
    $group->post('/classifieds', 'App\\Controllers\\ClassifiedController:store');
    $group->put('/classifieds/{id}', 'App\\Controllers\\ClassifiedController:update');
    $group->delete('/classifieds/{id}', 'App\\Controllers\\ClassifiedController:destroy');
    $group->post('/classifieds/{id}/favorite', 'App\\Controllers\\ClassifiedController:favorite');
    $group->delete('/classifieds/{id}/favorite', 'App\\Controllers\\ClassifiedController:unfavorite');
});

$app->run();

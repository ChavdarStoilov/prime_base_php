<?php
use App\Shared\Database\Database;
use Slim\App;
use App\Modules\Auth\Routes as AuthRoutes;
use App\Modules\Users\Routes as UsersRoutes;
use App\Middleware\ErrorMiddleware;
use App\Middleware\JsonMiddleware;
use App\Middleware\JwtMiddleware;
use App\Shared\Jwt\JwtService;

require __DIR__ . '/../../vendor/autoload.php';

// Mock DB - SQLite in-memory
$db = new Database([
    'driver' => 'sqlite',
    'database' => ':memory:'
]);

// Създай Users таблица
$db->execute("
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid TEXT,
    username TEXT UNIQUE,
    password TEXT,
    is_active INTEGER,
    created_at TEXT,
    updated_at TEXT
)");

// Добави mock user
$uuid = '11111111-1111-1111-1111-111111111111';
$db->insert('users', [
    'uuid' => $uuid,
    'username' => 'testuser',
    'password' => password_hash('secret', PASSWORD_ARGON2ID),
    'is_active' => 1,
    'created_at' => date('Y-m-d H:i:s')
]);

// Mock JWT
$jwtService = new JwtService();

// Slim App
$app = new App();

// Зареди routes
$group = $app->group('/api/v1', function ($group) {
    AuthRoutes::register($group);
    UsersRoutes::register($group);
});

// Middleware
$app->add(ErrorMiddleware::class);
$app->add(JsonMiddleware::class);

return [
    'app' => $app,
    'db' => $db,
    'jwt' => $jwtService,
    'uuid' => $uuid
];

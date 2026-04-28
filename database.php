<?php
declare(strict_types=1);

const AGRI_DB_HOST = 'AGRI_DB_HOST';
const AGRI_DB_PORT = 'AGRI_DB_PORT';
const AGRI_DB_NAME = 'AGRI_DB_NAME';
const AGRI_DB_USER = 'AGRI_DB_USER';
const AGRI_DB_PASSWORD = 'AGRI_DB_PASSWORD';
const DOTENV_PATH = __DIR__ . '/.env';

loadDotEnv(DOTENV_PATH);

function loadDotEnv(string $path): void
{
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        $value = trim($value, '"\'');

        if ($name === '' || getenv($name) !== false) {
            continue;
        }

        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

function getDatabaseConfig(): array
{
    return [
        'host' => getenv(AGRI_DB_HOST) ?: '127.0.0.1',
        'port' => getenv(AGRI_DB_PORT) ?: '3306',
        'name' => getenv(AGRI_DB_NAME) ?: 'agricongo',
        'user' => getenv(AGRI_DB_USER) ?: 'root',
        'password' => getenv(AGRI_DB_PASSWORD) ?: '',
    ];
}

function getDatabaseConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = getDatabaseConfig();
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $config['host'],
        $config['port'],
        $config['name']
    );

    try {
        $pdo = new PDO($dsn, $config['user'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        throw new RuntimeException('Connexion a la base de donnees impossible.', 0, $exception);
    }

    return $pdo;
}

function isDatabaseAvailable(): bool
{
    try {
        getDatabaseConnection();
        return true;
    } catch (Throwable $exception) {
        return false;
    }
}

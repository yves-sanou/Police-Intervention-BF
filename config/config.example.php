<?php
declare(strict_types=1);

/**
 * Copier ce fichier en config.php puis renseigner les paramètres locaux.
 * Ne jamais committer config.php ni des identifiants de production.
 */
session_start();
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Africa/Ouagadougou');

if (!defined('BASE_URL')) {
    $projectRoot = realpath(dirname(__DIR__));
    $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $relativePath = '';
    if ($projectRoot && $documentRoot) {
        $projectNormalized = str_replace('\\', '/', $projectRoot);
        $documentNormalized = rtrim(str_replace('\\', '/', $documentRoot), '/');
        if (stripos($projectNormalized, $documentNormalized . '/') === 0) {
            $relativePath = substr($projectNormalized, strlen($documentNormalized));
        } elseif (strcasecmp($projectNormalized, $documentNormalized) !== 0) {
            $relativePath = '/' . basename($projectNormalized);
        }
    }
    define('BASE_URL', rtrim('/' . trim(str_replace('\\', '/', $relativePath), '/'), '/'));
}

function url(string $path = ''): string {
    return BASE_URL . ($path !== '' ? '/' . ltrim($path, '/') : '/');
}
function asset(string $path): string {
    return url('assets/' . ltrim($path, '/'));
}

const DB_HOST = '127.0.0.1';
const DB_NAME = 'police_intervention_bf';
const DB_USER = 'CHANGE_ME';
const DB_PASS = 'CHANGE_ME';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $pdo;
}
function e(?string $v): string {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
function require_login(): void {
    if (empty($_SESSION['user'])) {
        header('Location: ' . url('login.php'));
        exit;
    }
    if (function_exists('app_current_user') && !app_current_user()) {
        header('Location: ' . url('login.php'));
        exit;
    }
}
function user(): array { return $_SESSION['user'] ?? []; }
function csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function check_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('Jeton CSRF invalide');
    }
}
function json_response(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
require_once __DIR__ . '/../includes/permissions.php';

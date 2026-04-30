<?php
// =============================================
// config.php - Configuracion central
// Usa variables de entorno para Railway
// =============================================

define('DB_HOST', getenv('MYSQLHOST')     ?: getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'mi_store_db');
define('DB_USER', getenv('MYSQLUSER')     ?: getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '');
define('DB_PORT', getenv('MYSQLPORT')     ?: '3306');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'MI STORE');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/mi-store');

// Zona horaria
date_default_timezone_set('America/Argentina/Buenos_Aires');

// ---- Conexion PDO ----
function getDB(): PDO {
          static $pdo = null;
          if ($pdo === null) {
                        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
                        $options = [
                                          PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                                          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                                          PDO::ATTR_EMULATE_PREPARES   => false,
                                      ];
                        try {
                                          $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                        } catch (PDOException $e) {
                                          http_response_code(500);
                                          die(json_encode(['ok' => false, 'msg' => 'Error de conexion a la base de datos.']));
                        }
          }
          return $pdo;
}

// ---- Helpers de sesion ----
function startSession(): void {
          if (session_status() === PHP_SESSION_NONE) {
                        session_start();
          }
}

function isLoggedIn(): bool {
          startSession();
          return isset($_SESSION['user_id']);
}

function requireLogin(): void {
          if (!isLoggedIn()) {
                        header('Location: ' . APP_URL . '/login.php');
                        exit;
          }
}

function currentUser(): array {
          startSession();
          return $_SESSION['user'] ?? [];
}

// ---- Helpers de respuesta JSON ----
function jsonOk(array $data = [], string $msg = 'OK'): void {
          header('Content-Type: application/json');
          echo json_encode(['ok' => true, 'msg' => $msg, 'data' => $data]);
          exit;
}

function jsonError(string $msg, int $code = 400): void {
          http_response_code($code);
          header('Content-Type: application/json');
          echo json_encode(['ok' => false, 'msg' => $msg]);
          exit;
}

// ---- Sanitizacion ----
function clean(string $v): string {
          return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8');
}

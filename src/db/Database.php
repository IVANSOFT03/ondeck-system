<?php

declare(strict_types=1);

class Database
{
  private static ?Database $instance = null;
  private PDO $pdo;

  private function __construct()
  {
    require_once dirname(__DIR__, 2) . '/config/config.php';
    $this->connect();
  }

  private function connect(): void
  {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);

    $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ]);
  }

  /**
   * Devuelve un PDO vivo; si el servidor cerró la conexión (p. ej. tras operaciones largas), reconecta.
   */
  public function ensureFreshPdo(): PDO
  {
    try {
      $this->pdo->query('SELECT 1');
    } catch (PDOException $e) {
      $this->connect();
    }

    return $this->pdo;
  }

  public static function getInstance(): Database
  {
    if (self::$instance === null) {
      self::$instance = new Database();
    }
    return self::$instance;
  }

  public function pdo(): PDO
  {
    return $this->pdo;
  }
}

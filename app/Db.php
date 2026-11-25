<?php
declare(strict_types=1);

/** PDO wrapper. Creates the schema (and demo data) automatically on first start. */
final class Db
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = self::connect();
            self::install(self::$pdo);
        }
        return self::$pdo;
    }

    /** @return array<int, array<string, mixed>> */
    public static function all(string $sql, array $args = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetchAll();
    }

    public static function one(string $sql, array $args = []): ?array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($args);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $args = []): mixed
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetchColumn();
    }

    public static function run(string $sql, array $args = []): void
    {
        self::pdo()->prepare($sql)->execute($args);
    }

    private static function connect(): PDO
    {
        $c = config('db');
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        if ($c['driver'] === 'mysql') {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $c['host'], $c['port'], $c['name']);
            return new PDO($dsn, $c['user'], $c['pass'], $options);
        }

        $dir = dirname($c['sqlite_path']);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $pdo = new PDO('sqlite:' . $c['sqlite_path'], null, null, $options);
        $pdo->exec('PRAGMA foreign_keys = ON');
        return $pdo;
    }

    private static function install(PDO $pdo): void
    {
        try {
            $pdo->query('SELECT 1 FROM users LIMIT 1');
            return;
        } catch (PDOException) {
            // tables do not exist yet
        }

        $driver = config('db.driver') === 'mysql' ? 'mysql' : 'sqlite';
        $sql = (string) file_get_contents(ROOT . "/database/schema.$driver.sql");
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            $pdo->exec($statement);
        }

        if (config('demo')) {
            require_once ROOT . '/database/seed.php';
            seed_demo($pdo);
        }
    }
}

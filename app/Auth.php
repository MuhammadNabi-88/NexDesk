<?php
declare(strict_types=1);

final class Auth
{
    private static ?array $user = null;
    private static ?string $error = null;

    public static function user(): ?array
    {
        if (self::$user === null && isset($_SESSION['uid'])) {
            self::$user = Db::one('SELECT id, name, email, role FROM users WHERE id = ?', [$_SESSION['uid']]);
            if (self::$user === null) {
                unset($_SESSION['uid']);
            }
        }
        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function is(string ...$roles): bool
    {
        $user = self::user();
        return $user !== null && in_array($user['role'], $roles, true);
    }

    /** Redirects visitors who are not signed in. */
    public static function require(string ...$roles): array
    {
        $user = self::user();
        if ($user === null) {
            redirect('/login');
        }
        if ($roles !== [] && !in_array($user['role'], $roles, true)) {
            abort(403, 'Your role does not allow this action.');
        }
        return $user;
    }

    public static function error(): ?string
    {
        return self::$error;
    }

    public static function attempt(string $email, string $password): bool
    {
        $attempts = $_SESSION['attempts'] ?? ['n' => 0, 't' => time()];
        if (time() - $attempts['t'] > 300) {
            $attempts = ['n' => 0, 't' => time()];
        }
        if ($attempts['n'] >= 5) {
            self::$error = 'Too many attempts. Please wait a few minutes and try again.';
            return false;
        }

        $user = Db::one('SELECT * FROM users WHERE email = ?', [$email]);
        if ($user !== null && password_verify($password, $user['password_hash'])) {
            unset($_SESSION['attempts']);
            self::start($user);
            return true;
        }

        $attempts['n']++;
        $_SESSION['attempts'] = $attempts;
        return false;
    }

    public static function start(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int) $user['id'];
        self::$user = null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
        self::$user = null;
    }
}

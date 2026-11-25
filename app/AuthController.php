<?php
declare(strict_types=1);

final class AuthController
{
    private const DEMO = [
        'admin' => 'admin@nexdesk.test',
        'agent' => 'agent@nexdesk.test',
        'customer' => 'customer@nexdesk.test',
    ];

    public static function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/dashboard');
        }
        view('auth/login', ['title' => 'Sign in', 'demoMode' => (bool) config('demo')], 'guest');
    }

    public static function login(): void
    {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        if (Auth::attempt($email, $password)) {
            redirect('/dashboard');
        }
        flash('error', Auth::error() ?? 'Email or password is not correct.');
        $_SESSION['old'] = ['email' => $email];
        redirect('/login');
    }

    public static function demo(): void
    {
        $role = (string) ($_POST['role'] ?? '');
        if (!config('demo') || !isset(self::DEMO[$role])) {
            abort(404);
        }
        $user = Db::one('SELECT * FROM users WHERE email = ?', [self::DEMO[$role]]);
        if ($user === null) {
            abort(404, 'Demo user not found.');
        }
        Auth::start($user);
        redirect('/dashboard');
    }

    public static function showRegister(): void
    {
        if (Auth::check()) {
            redirect('/dashboard');
        }
        view('auth/register', ['title' => 'Create account'], 'guest');
    }

    public static function register(): void
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $_SESSION['old'] = ['name' => $name, 'email' => $email];

        $error = match (true) {
            mb_strlen($name) < 2 || mb_strlen($name) > 80 => 'Please enter your name (2 to 80 characters).',
            !filter_var($email, FILTER_VALIDATE_EMAIL) => 'Please enter a valid email address.',
            strlen($password) < 8 => 'The password needs at least 8 characters.',
            Db::one('SELECT id FROM users WHERE email = ?', [$email]) !== null => 'An account with this email already exists.',
            default => null,
        };
        if ($error !== null) {
            flash('error', $error);
            redirect('/register');
        }

        Db::run(
            "INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?, ?, ?, 'customer', ?)",
            [$name, $email, password_hash($password, PASSWORD_DEFAULT), now()]
        );
        $user = Db::one('SELECT * FROM users WHERE email = ?', [$email]);
        Auth::start($user);
        flash('success', 'Welcome to Nexdesk. You can open your first ticket now.');
        redirect('/dashboard');
    }

    public static function logout(): void
    {
        Auth::logout();
        flash('success', 'You have been signed out.');
        redirect('/login');
    }
}

<?php
declare(strict_types=1);

final class PageController
{
    public static function home(): void
    {
        redirect(Auth::check() ? '/dashboard' : '/login');
    }

    public static function dashboard(): void
    {
        $user = Auth::require();
        $recent = Tickets::search($user, [], 1, 5)['rows'];
        view('dashboard', [
            'title' => 'Dashboard',
            'active' => 'dashboard',
            'stats' => Tickets::stats($user),
            'recent' => $recent,
        ]);
    }

    public static function users(): void
    {
        Auth::require('admin');
        $users = Db::all(
            'SELECT u.id, u.name, u.email, u.role, u.created_at,
                    (SELECT COUNT(*) FROM tickets t WHERE t.requester_id = u.id) AS ticket_count
             FROM users u ORDER BY u.id'
        );
        view('users', ['title' => 'Users', 'active' => 'users', 'users' => $users]);
    }

    public static function setRole(int $id): void
    {
        $admin = Auth::require('admin');
        $role = (string) ($_POST['role'] ?? '');
        if (!in_array($role, ['customer', 'agent', 'admin'], true)) {
            abort(404);
        }
        if ($id === (int) $admin['id']) {
            flash('error', 'You cannot change your own role.');
        } else {
            Db::run('UPDATE users SET role = ? WHERE id = ?', [$role, $id]);
            flash('success', 'Role updated.');
        }
        redirect('/users');
    }
}

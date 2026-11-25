<?php
declare(strict_types=1);

final class TicketController
{
    private const PER_PAGE = 8;

    public static function index(): void
    {
        $user = Auth::require();
        $filters = [
            'status' => (string) ($_GET['status'] ?? ''),
            'priority' => (string) ($_GET['priority'] ?? ''),
            'q' => trim((string) ($_GET['q'] ?? '')),
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = Tickets::search($user, $filters, $page, self::PER_PAGE);

        view('tickets/index', [
            'title' => $user['role'] === 'customer' ? 'My tickets' : 'All tickets',
            'active' => 'tickets',
            'filters' => $filters,
            'rows' => $result['rows'],
            'total' => $result['total'],
            'page' => $page,
            'pages' => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
        ]);
    }

    public static function create(): void
    {
        Auth::require();
        view('tickets/new', ['title' => 'New ticket', 'active' => 'new']);
    }

    public static function store(): void
    {
        $user = Auth::require();
        $data = [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'category' => (string) ($_POST['category'] ?? ''),
            'priority' => (string) ($_POST['priority'] ?? ''),
        ];
        $_SESSION['old'] = $data;

        $error = match (true) {
            mb_strlen($data['title']) < 5 || mb_strlen($data['title']) > 160 => 'The title needs 5 to 160 characters.',
            mb_strlen($data['description']) < 10 || mb_strlen($data['description']) > 5000 => 'Please describe the problem (10 to 5000 characters).',
            !isset(Tickets::CATEGORIES[$data['category']]) => 'Please choose a category.',
            !isset(Tickets::PRIORITIES[$data['priority']]) => 'Please choose a priority.',
            default => null,
        };
        if ($error !== null) {
            flash('error', $error);
            redirect('/tickets/new');
        }

        $id = Tickets::create($data, (int) $user['id']);
        flash('success', "Ticket #$id was created. We will respond within the SLA time.");
        redirect("/tickets/$id");
    }

    public static function show(int $id): void
    {
        $user = Auth::require();
        $ticket = Tickets::find($id);
        if ($ticket === null || !Tickets::canView($ticket, $user)) {
            abort(404, 'This ticket does not exist or you have no access to it.');
        }
        $isStaff = $user['role'] !== 'customer';

        view('tickets/show', [
            'title' => 'Ticket #' . $ticket['id'],
            'active' => 'tickets',
            'ticket' => $ticket,
            'events' => Tickets::events($id, $isStaff),
            'isStaff' => $isStaff,
            'agents' => $isStaff ? Db::all("SELECT id, name FROM users WHERE role IN ('agent', 'admin') ORDER BY name") : [],
        ]);
    }

    public static function comment(int $id): void
    {
        $user = Auth::require();
        $ticket = Tickets::find($id);
        if ($ticket === null || !Tickets::canView($ticket, $user)) {
            abort(404);
        }
        $body = trim((string) ($_POST['body'] ?? ''));
        if ($ticket['status'] === 'closed') {
            flash('error', 'This ticket is closed. Open a new ticket for further questions.');
        } elseif (mb_strlen($body) < 2 || mb_strlen($body) > 3000) {
            flash('error', 'Please write a message (up to 3000 characters).');
        } else {
            $internal = $user['role'] !== 'customer' && !empty($_POST['internal']);
            Tickets::addEvent($id, (int) $user['id'], $internal ? 'internal' : 'comment', $body);
            flash('success', $internal ? 'Internal note saved.' : 'Reply sent.');
        }
        redirect("/tickets/$id");
    }

    public static function update(int $id): void
    {
        $user = Auth::require('agent', 'admin');
        $ticket = Tickets::find($id);
        if ($ticket === null) {
            abort(404);
        }
        $changes = Tickets::applyUpdate($ticket, $_POST, $user);
        flash('success', $changes > 0 ? 'Ticket updated.' : 'Nothing to change.');
        redirect("/tickets/$id");
    }
}

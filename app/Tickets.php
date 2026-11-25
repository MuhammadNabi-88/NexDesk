<?php
declare(strict_types=1);

/** All ticket queries and business rules. */
final class Tickets
{
    public const STATUSES = ['open' => 'Open', 'in_progress' => 'In progress', 'waiting' => 'Waiting', 'resolved' => 'Resolved', 'closed' => 'Closed'];
    public const PRIORITIES = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'];
    public const CATEGORIES = ['hardware' => 'Hardware', 'software' => 'Software', 'network' => 'Network', 'access' => 'Access & accounts', 'security' => 'Security', 'other' => 'Other'];
    /** Hours until a ticket is due, by priority (the SLA). */
    public const SLA_HOURS = ['critical' => 4, 'high' => 8, 'medium' => 24, 'low' => 72];
    public const ACTIVE = ['open', 'in_progress', 'waiting'];

    /** Customers only ever see their own tickets; agents and admins see all. */
    private static function scope(array $user): array
    {
        return $user['role'] === 'customer' ? ['t.requester_id = ?', [$user['id']]] : ['1 = 1', []];
    }

    public static function canView(array $ticket, array $user): bool
    {
        return $user['role'] !== 'customer' || (int) $ticket['requester_id'] === (int) $user['id'];
    }

    public static function search(array $user, array $filters, int $page, int $perPage): array
    {
        [$scopeSql, $args] = self::scope($user);
        $where = [$scopeSql];

        $status = $filters['status'] ?? '';
        if ($status === 'active') {
            $where[] = "t.status IN ('open', 'in_progress', 'waiting')";
        } elseif (isset(self::STATUSES[$status])) {
            $where[] = 't.status = ?';
            $args[] = $status;
        }
        if (isset(self::PRIORITIES[$filters['priority'] ?? ''])) {
            $where[] = 't.priority = ?';
            $args[] = $filters['priority'];
        }
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . $q . '%';
            if (ctype_digit($q)) {
                $where[] = '(t.id = ? OR t.title LIKE ?)';
                $args[] = (int) $q;
            } else {
                $where[] = 't.title LIKE ?';
            }
            $args[] = $like;
        }

        $cond = implode(' AND ', $where);
        $total = (int) Db::value("SELECT COUNT(*) FROM tickets t WHERE $cond", $args);
        $limit = max(1, $perPage);
        $offset = max(0, ($page - 1) * $limit);

        $rows = Db::all(
            "SELECT t.*, r.name AS requester_name, a.name AS assignee_name
             FROM tickets t
             JOIN users r ON r.id = t.requester_id
             LEFT JOIN users a ON a.id = t.assignee_id
             WHERE $cond
             ORDER BY t.updated_at DESC, t.id DESC
             LIMIT $limit OFFSET $offset",
            $args
        );
        return ['rows' => $rows, 'total' => $total];
    }

    public static function find(int $id): ?array
    {
        return Db::one(
            'SELECT t.*, r.name AS requester_name, r.email AS requester_email, a.name AS assignee_name
             FROM tickets t
             JOIN users r ON r.id = t.requester_id
             LEFT JOIN users a ON a.id = t.assignee_id
             WHERE t.id = ?',
            [$id]
        );
    }

    public static function events(int $ticketId, bool $includeInternal): array
    {
        $extra = $includeInternal ? '' : " AND e.type <> 'internal'";
        return Db::all(
            "SELECT e.*, u.name AS user_name, u.role AS user_role
             FROM ticket_events e JOIN users u ON u.id = e.user_id
             WHERE e.ticket_id = ?$extra ORDER BY e.id ASC",
            [$ticketId]
        );
    }

    public static function create(array $data, int $requesterId): int
    {
        $due = date('Y-m-d H:i:s', time() + self::SLA_HOURS[$data['priority']] * 3600);
        Db::run(
            "INSERT INTO tickets (title, description, category, priority, status, requester_id, due_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, 'open', ?, ?, ?, ?)",
            [$data['title'], $data['description'], $data['category'], $data['priority'], $requesterId, $due, now(), now()]
        );
        return (int) Db::pdo()->lastInsertId();
    }

    public static function addEvent(int $ticketId, int $userId, string $type, string $body): void
    {
        Db::run(
            'INSERT INTO ticket_events (ticket_id, user_id, type, body, created_at) VALUES (?, ?, ?, ?, ?)',
            [$ticketId, $userId, $type, $body, now()]
        );
        Db::run('UPDATE tickets SET updated_at = ? WHERE id = ?', [now(), $ticketId]);
    }

    /** Agent changes to status, priority and assignee. Every change is written to the timeline. */
    public static function applyUpdate(array $ticket, array $input, array $agent): int
    {
        $id = (int) $ticket['id'];
        $changes = 0;

        $status = (string) ($input['status'] ?? '');
        if (isset(self::STATUSES[$status]) && $status !== $ticket['status']) {
            Db::run('UPDATE tickets SET status = ? WHERE id = ?', [$status, $id]);
            self::addEvent($id, (int) $agent['id'], 'status', 'Status changed from ' . self::STATUSES[$ticket['status']] . ' to ' . self::STATUSES[$status]);
            $changes++;
        }

        $priority = (string) ($input['priority'] ?? '');
        if (isset(self::PRIORITIES[$priority]) && $priority !== $ticket['priority']) {
            $due = date('Y-m-d H:i:s', strtotime($ticket['created_at']) + self::SLA_HOURS[$priority] * 3600);
            Db::run('UPDATE tickets SET priority = ?, due_at = ? WHERE id = ?', [$priority, $due, $id]);
            self::addEvent($id, (int) $agent['id'], 'priority', 'Priority changed from ' . self::PRIORITIES[$ticket['priority']] . ' to ' . self::PRIORITIES[$priority] . ' (SLA recalculated)');
            $changes++;
        }

        $assigneeId = (string) ($input['assignee_id'] ?? '');
        $newAssignee = $assigneeId === '' ? null : (int) $assigneeId;
        $oldAssignee = $ticket['assignee_id'] === null ? null : (int) $ticket['assignee_id'];
        if ($newAssignee !== $oldAssignee) {
            $name = null;
            if ($newAssignee !== null) {
                $person = Db::one("SELECT name FROM users WHERE id = ? AND role IN ('agent', 'admin')", [$newAssignee]);
                if ($person === null) {
                    return $changes;
                }
                $name = $person['name'];
            }
            Db::run('UPDATE tickets SET assignee_id = ? WHERE id = ?', [$newAssignee, $id]);
            self::addEvent($id, (int) $agent['id'], 'assign', $name === null ? 'Ticket unassigned' : 'Assigned to ' . $name);
            $changes++;
        }
        return $changes;
    }

    /** Numbers for the dashboard, limited to what the user may see. */
    public static function stats(array $user): array
    {
        [$scopeSql, $scopeArgs] = self::scope($user);

        $byStatus = array_fill_keys(array_keys(self::STATUSES), 0);
        foreach (Db::all("SELECT t.status, COUNT(*) AS c FROM tickets t WHERE $scopeSql GROUP BY t.status", $scopeArgs) as $row) {
            $byStatus[$row['status']] = (int) $row['c'];
        }

        $activeList = "'" . implode("', '", self::ACTIVE) . "'";
        $overdue = (int) Db::value("SELECT COUNT(*) FROM tickets t WHERE $scopeSql AND t.status IN ($activeList) AND t.due_at < ?", [...$scopeArgs, now()]);

        $byPriority = array_fill_keys(array_keys(self::PRIORITIES), 0);
        foreach (Db::all("SELECT t.priority, COUNT(*) AS c FROM tickets t WHERE $scopeSql AND t.status IN ($activeList) GROUP BY t.priority", $scopeArgs) as $row) {
            $byPriority[$row['priority']] = (int) $row['c'];
        }

        $since = date('Y-m-d 00:00:00', strtotime('-6 days'));
        $perDay = [];
        foreach (Db::all("SELECT SUBSTR(t.created_at, 1, 10) AS d, COUNT(*) AS c FROM tickets t WHERE $scopeSql AND t.created_at >= ? GROUP BY SUBSTR(t.created_at, 1, 10)", [...$scopeArgs, $since]) as $row) {
            $perDay[$row['d']] = (int) $row['c'];
        }
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $days[] = ['label' => date('D', strtotime($date)), 'count' => $perDay[$date] ?? 0];
        }

        $mine = $user['role'] === 'customer' ? null : (int) Db::value("SELECT COUNT(*) FROM tickets t WHERE t.assignee_id = ? AND t.status IN ($activeList)", [$user['id']]);

        return [
            'by_status' => $byStatus,
            'active' => array_sum(array_intersect_key($byStatus, array_flip(self::ACTIVE))),
            'done' => $byStatus['resolved'] + $byStatus['closed'],
            'total' => array_sum($byStatus),
            'overdue' => $overdue,
            'by_priority' => $byPriority,
            'days' => $days,
            'mine' => $mine,
        ];
    }
}

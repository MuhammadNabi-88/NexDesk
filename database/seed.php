<?php
declare(strict_types=1);

/** Sample data for demo mode. All demo accounts use the password "demo1234". */
function seed_demo(PDO $pdo): void
{
    $now = time();
    $fmt = static fn(int $t): string => date('Y-m-d H:i:s', $t);
    $hash = password_hash('demo1234', PASSWORD_DEFAULT);

    // ids follow the insertion order: 1 admin, 2 and 3 agents, 4 and 5 customers
    $users = [
        ['Alex Morgan', 'admin@nexdesk.test', 'admin'],
        ['Sam Keller', 'agent@nexdesk.test', 'agent'],
        ['Jordan Lee', 'jordan@nexdesk.test', 'agent'],
        ['Chris Weber', 'customer@nexdesk.test', 'customer'],
        ['Mia Schmidt', 'mia@nexdesk.test', 'customer'],
    ];
    $insUser = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, ?)');
    foreach ($users as $u) {
        $insUser->execute([$u[0], $u[1], $hash, $u[2], $fmt($now - 30 * 86400)]);
    }
    $names = array_column($users, 0);

    // title, description, category, priority, status, requester, assignee, hours ago
    $tickets = [
        ['VPN disconnects every 10 minutes', 'Since Monday the VPN drops about every ten minutes while I work from home. Reconnecting works but interrupts video calls.', 'network', 'high', 'in_progress', 4, 2, 5],
        ['Laptop battery drains within an hour', 'The battery goes from 100% to 10% in under an hour, even with only a browser open.', 'hardware', 'medium', 'open', 4, null, 20],
        ['Cannot access the shared finance folder', 'I get "access denied" on the finance share since my team change last week.', 'access', 'high', 'open', 5, null, 3],
        ['Outlook crashes on startup', 'Outlook closes right after the splash screen. Safe mode does not help and I cannot read any email.', 'software', 'critical', 'in_progress', 5, 3, 9],
        ['Request: install Docker Desktop', 'I need Docker Desktop on my workstation for the new development project.', 'software', 'low', 'open', 4, null, 80],
        ['Phishing email reported by the team', 'Three colleagues received an email pretending to be HR asking for credentials. Screenshot attached to the mailbox.', 'security', 'critical', 'resolved', 5, 2, 26],
        ['New hire needs account setup', 'A new developer starts on Monday and needs a mailbox, VPN and access to the code repositories.', 'access', 'medium', 'resolved', 4, 3, 48],
        ['Printer on floor 2 is offline', 'The shared printer shows as offline for everyone on the second floor.', 'hardware', 'low', 'closed', 5, 3, 120],
        ['Wi-Fi is slow in meeting room B', 'Video calls in room B freeze regularly. Speed tests show less than 2 Mbit/s.', 'network', 'medium', 'waiting', 4, 2, 18],
        ['Password reset email never arrives', 'I requested a password reset three times but no email arrives, not even in spam.', 'access', 'high', 'open', 5, null, 2],
        ['External monitor flickers', 'The second monitor flickers every few minutes. Replaced the cable already.', 'hardware', 'low', 'open', 4, null, 10],
        ['Teams has no audio in calls', 'I can see participants but hear nothing. The headset works in other apps.', 'software', 'medium', 'in_progress', 5, 3, 7],
    ];

    $insTicket = $pdo->prepare(
        'INSERT INTO tickets (title, description, category, priority, status, requester_id, assignee_id, due_at, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $insEvent = $pdo->prepare('INSERT INTO ticket_events (ticket_id, user_id, type, body, created_at) VALUES (?, ?, ?, ?, ?)');

    foreach ($tickets as $i => [$title, $desc, $cat, $prio, $status, $req, $assignee, $hoursAgo]) {
        $created = $now - $hoursAgo * 3600;
        $due = $created + Tickets::SLA_HOURS[$prio] * 3600;
        $updated = $created + (int) (($now - $created) / 2);
        $insTicket->execute([$title, $desc, $cat, $prio, $status, $req, $assignee, $fmt($due), $fmt($created), $fmt($updated)]);
        $id = (int) $pdo->lastInsertId();

        if ($assignee !== null) {
            $insEvent->execute([$id, 2, 'assign', 'Assigned to ' . $names[$assignee - 1], $fmt($created + 600)]);
        }
        if ($status !== 'open') {
            $insEvent->execute([$id, $assignee ?? 2, 'status', 'Status changed from Open to ' . Tickets::STATUSES[$status], $fmt($created + 1800)]);
        }
        if ($assignee !== null && $i % 3 === 0) {
            $insEvent->execute([$id, $assignee, 'comment', 'Thanks for reporting this. We are looking into it and will update you shortly.', $fmt($created + 3000)]);
        }
        if ($assignee !== null && $i % 4 === 0) {
            $insEvent->execute([$id, $assignee, 'internal', 'Checked the logs. Looks related to last week\'s configuration change. Escalating if it repeats.', $fmt($created + 3600)]);
        }
    }
}

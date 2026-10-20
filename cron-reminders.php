<?php
// Daily task: sends the automatic reminders set on events. Open as cron-reminders.php?key=<secret> (key shown in admin → Schedule → Email & SMS settings).
require_once __DIR__ . "/includes/schedule.php";
header('Content-Type: text/plain; charset=utf-8');
sch_install($conn);
$key = sch_settings($conn)['sch_cron_key'];
if (!hash_equals($key, (string)($_GET['key'] ?? ''))) { http_response_code(403); exit("Forbidden\n"); }
$t = sch_run_auto($conn);
echo "OK events={$t['events']} email={$t['email']} sms={$t['sms']}\n";

<?php
// Daily task: sends automatic event reminders and holiday / special-day greetings to registered members.
// Open as cron-reminders.php?key=<secret> once a day, early morning (key shown in admin → Schedule → Email & SMS settings).
require_once __DIR__ . "/includes/schedule.php";
header('Content-Type: text/plain; charset=utf-8');
sch_install($conn);
$key = sch_settings($conn)['sch_cron_key'];
if (!hash_equals($key, (string)($_GET['key'] ?? ''))) { http_response_code(403); exit("Forbidden\n"); }
$t = sch_run_auto($conn);
$g = sch_run_greetings($conn);
echo "OK reminders: events={$t['events']} email={$t['email']} sms={$t['sms']} | greetings: events={$g['events']} sent={$g['sent']} failed={$g['failed']} waiting={$g['remaining']}\n";

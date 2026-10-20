<?php
// Receives a table from the page the admin is looking at and returns it as a PDF / Excel / Word / CSV file.
require __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/export.php";
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: index.php"); exit; }
$fmt = in_array($_POST['format'] ?? '', ['pdf', 'xlsx', 'docx', 'csv'], true) ? $_POST['format'] : 'csv';
$data = json_decode((string)($_POST['data'] ?? ''), true);
if (!is_array($data) || empty($data['headers']) || !is_array($data['headers'])) { http_response_code(400); exit('Nothing to export.'); }
$headers = array_slice(array_map('strval', $data['headers']), 0, 40);
$rows = [];
foreach (array_slice((array)($data['rows'] ?? []), 0, 20000) as $r) if (is_array($r)) $rows[] = array_map(fn($v) => mb_substr((string)$v, 0, 2000), array_slice($r, 0, count($headers)));
$title = mb_substr(trim((string)($_POST['title'] ?? 'Export')), 0, 120) ?: 'Export';
nufa_export_send($fmt, $title, $headers, $rows, $title, 'Exported by ' . ($_SESSION['admin_name'] ?? 'admin') . ' · ' . count($rows) . ' row' . (count($rows) === 1 ? '' : 's'));

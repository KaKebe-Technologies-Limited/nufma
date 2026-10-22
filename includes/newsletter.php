<?php
/**
 * Newsletters, managed in admin → Newsletters.
 *  - `newsletters`: issues written in the admin ('written') or uploaded as a PDF ('pdf'); published online and optionally e-mailed
 *  - `subscribers`: the mailing list (shared with the older subscribers table)
 *  - `newsletter_sends`: who an issue has already been sent to (so a send can be continued safely)
 */
require_once __DIR__ . "/membership.php";

const NL_PDF_DIR = __DIR__ . '/../assets/newsletters/';
const NL_COVER_DIR = __DIR__ . '/../assets/images/uploads/newsletters/';

function nl_install($conn) {
    static $done = false;
    if ($done) return; $done = true;
    $conn->query("CREATE TABLE IF NOT EXISTS newsletters (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(200) NOT NULL,
        issue_label VARCHAR(120) NOT NULL DEFAULT '',
        kind VARCHAR(10) NOT NULL DEFAULT 'written',
        summary TEXT,
        body MEDIUMTEXT,
        cover_image VARCHAR(255) NOT NULL DEFAULT '',
        pdf_file VARCHAR(255) NOT NULL DEFAULT '',
        post_ids VARCHAR(255) NOT NULL DEFAULT '',
        status VARCHAR(12) NOT NULL DEFAULT 'draft',
        published_at DATETIME NULL,
        sent_at DATETIME NULL,
        sent_total INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_slug (slug), KEY idx_status (status, published_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS newsletter_sends (
        id INT AUTO_INCREMENT PRIMARY KEY,
        newsletter_id INT NOT NULL,
        email VARCHAR(190) NOT NULL,
        status VARCHAR(10) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_send (newsletter_id, email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // the mailing list reuses the original subscribers table
    $conn->query("CREATE TABLE IF NOT EXISTS subscribers (id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(255) NOT NULL, source VARCHAR(100) NOT NULL DEFAULT 'website', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY email (email)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    foreach (['name' => "VARCHAR(150) NOT NULL DEFAULT ''", 'token' => "VARCHAR(32) NOT NULL DEFAULT ''", 'unsubscribed' => 'TINYINT(1) NOT NULL DEFAULT 0', 'ip' => "VARCHAR(45) NOT NULL DEFAULT ''"] as $c => $ddl)
        if ($conn->query("SHOW COLUMNS FROM subscribers LIKE '$c'")->num_rows === 0) $conn->query("ALTER TABLE subscribers ADD COLUMN `$c` $ddl");
    $r = $conn->query("SELECT id FROM subscribers WHERE token=''");
    while ($r && ($x = $r->fetch_assoc())) $conn->query("UPDATE subscribers SET token='" . bin2hex(random_bytes(16)) . "' WHERE id=" . (int)$x['id']);
    // post extras for the improved story editor
    foreach (['attachment' => "VARCHAR(255) NOT NULL DEFAULT ''", 'attachment_title' => "VARCHAR(190) NOT NULL DEFAULT ''", 'video_url' => "VARCHAR(500) NOT NULL DEFAULT ''"] as $c => $ddl)
        if ($conn->query("SHOW COLUMNS FROM posts LIKE '$c'")->num_rows === 0) $conn->query("ALTER TABLE posts ADD COLUMN `$c` $ddl");
}

function nl_slug($conn, $title, $ignoreId = 0) {
    $base = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-')) ?: 'newsletter'; $slug = $base; $n = 1;
    while ($conn->query("SELECT id FROM newsletters WHERE slug='" . $conn->real_escape_string($slug) . "' AND id<>" . (int)$ignoreId)->num_rows) $slug = $base . '-' . (++$n);
    return $slug;
}

/** Adds (or re-activates) a subscriber. Returns [ok, message]. */
function nl_subscribe($conn, $email, $name = '', $source = 'website') {
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return [false, 'Please enter a valid email address.'];
    $ip = substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45); $tok = bin2hex(random_bytes(16)); $name = mb_substr(trim($name), 0, 150);
    $s = $conn->prepare("INSERT INTO subscribers (email, name, source, token, ip) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE unsubscribed=0, name=IF(VALUES(name)<>'', VALUES(name), name)");
    $s->bind_param("sssss", $email, $name, $source, $tok, $ip);
    return $s->execute() ? [true, ''] : [false, 'We could not save your subscription just now. Please try again.'];
}

function nl_issue_url($n) { return mem_site_url() . '/newsletter.php?slug=' . urlencode($n['slug']); }
function nl_pdf_url($n) { return $n['pdf_file'] !== '' ? mem_site_url() . '/assets/newsletters/' . rawurlencode($n['pdf_file']) : ''; }

function nl_posts($conn, $ids) {
    $ids = array_values(array_filter(array_map('intval', explode(',', (string)$ids))));
    if (!$ids) return [];
    $rows = $conn->query("SELECT id, title, slug, category, excerpt, image, published_at FROM posts WHERE id IN (" . implode(',', $ids) . ") AND is_published=1")->fetch_all(MYSQLI_ASSOC);
    usort($rows, fn($a, $b) => array_search($a['id'], $ids) <=> array_search($b['id'], $ids));
    return $rows;
}

/** Full HTML e-mail for an issue. */
function nl_email_html($conn, $n, $sub) {
    $site = mem_site_url(); $e = fn($v) => htmlspecialchars((string)$v);
    $unsub = $site . '/newsletters.php?unsub=' . ($sub['token'] ?? '');
    $cover = $n['cover_image'] !== '' ? '<img src="' . $site . '/assets/images/' . $e($n['cover_image']) . '" alt="" style="width:100%;display:block;border:0">' : '';
    $body = strip_tags((string)$n['body'], '<p><br><b><strong><i><em><u><h2><h3><h4><ul><ol><li><a><blockquote><img><hr>');
    $body = preg_replace_callback('#(<img[^>]*src=")(?!https?://)/?([^"]+)#i', fn($m) => $m[1] . $site . '/' . ltrim($m[2], '/'), $body);
    $posts = '';
    foreach (nl_posts($conn, $n['post_ids']) as $p) {
        $posts .= '<tr><td style="padding:14px 0;border-top:1px solid #e8e4da"><a href="' . $site . '/post.php?slug=' . urlencode($p['slug']) . '" style="color:#161616;text-decoration:none"><b style="font-size:16px">' . $e($p['title']) . '</b></a>'
               . '<div style="color:#666666;font-size:13px;margin-top:4px">' . $e(mb_strimwidth((string)$p['excerpt'], 0, 160, '…')) . '</div>'
               . '<a href="' . $site . '/post.php?slug=' . urlencode($p['slug']) . '" style="color:#B04A00;font-size:13px;font-weight:bold;text-decoration:none">Read the story →</a></td></tr>';
    }
    $btn = fn($url, $label, $bg = '#F57A0A', $fg = '#161616') => '<a href="' . $e($url) . '" style="display:inline-block;background:' . $bg . ';color:' . $fg . ';text-decoration:none;padding:12px 24px;border-radius:30px;font-weight:bold;font-size:14px;margin:4px 6px 4px 0">' . $e($label) . '</a>';
    $pdf = nl_pdf_url($n);
    return '<!doctype html><html><body style="margin:0;background:#f2f1ec;font-family:Arial,Helvetica,sans-serif;color:#1F1F1F">'
        . '<div style="display:none;max-height:0;overflow:hidden">' . $e(mb_strimwidth(strip_tags((string)$n['summary']), 0, 120, '…')) . '</div>'
        . '<div style="max-width:620px;margin:0 auto;padding:22px 12px"><div style="background:#161616;color:#fff;padding:20px 26px;border-bottom:4px solid #F57A0A"><div style="font-size:20px;font-weight:bold;letter-spacing:.04em">NUFA <span style="color:#F57A0A">Newsletter</span></div>'
        . ($n['issue_label'] !== '' ? '<div style="font-size:12px;color:#D9D9D9;margin-top:4px">' . $e($n['issue_label']) . '</div>' : '') . '</div>'
        . '<div style="background:#fff">' . $cover . '<div style="padding:26px;line-height:1.65;font-size:15px"><h1 style="margin:0 0 10px;font-size:24px;line-height:1.25;color:#161616">' . $e($n['title']) . '</h1>'
        . ($n['summary'] !== '' ? '<p style="color:#4A4A4A;font-size:16px;margin:0 0 14px">' . nl2br($e($n['summary'])) . '</p>' : '') . $body
        . ($posts !== '' ? '<h2 style="font-size:17px;margin:26px 0 4px;color:#B04A00;text-transform:uppercase;letter-spacing:.06em">In this issue</h2><table width="100%" cellpadding="0" cellspacing="0">' . $posts . '</table>' : '')
        . '<p style="margin:24px 0 0">' . $btn(nl_issue_url($n), 'Read online') . ($pdf !== '' ? $btn($pdf, 'Download the PDF', '#161616', '#ffffff') : '') . '</p></div></div>'
        . '<div style="text-align:center;font-size:12px;color:#666666;padding:16px">You are receiving this because you subscribed to NUFA news.<br><a href="' . $e($unsub) . '" style="color:#666666">Unsubscribe</a> &middot; Northern Uganda Filmmakers Association, Gulu</div></div></body></html>';
}

function nl_email_text($conn, $n, $sub) {
    $t = $n['title'] . ($n['issue_label'] !== '' ? " — " . $n['issue_label'] : '') . "\n\n" . trim(strip_tags((string)$n['summary'])) . "\n\nRead online: " . nl_issue_url($n);
    if ($pdf = nl_pdf_url($n)) $t .= "\nDownload the PDF: $pdf";
    return $t . "\n\nUnsubscribe: " . mem_site_url() . '/newsletters.php?unsub=' . ($sub['token'] ?? '');
}

/** Sends one issue to one address (HTML + plain text) and logs it. */
function nl_mail($conn, $to, $subject, $html, $text) {
    $s = mem_settings($conn);
    $from = $s['email_from'] ?: 'info@nufa.media'; $fromName = $s['email_from_name'] ?: 'NUFA';
    $b = 'nufa_' . bin2hex(random_bytes(8));
    $headers = "From: " . mb_encode_mimeheader($fromName) . " <{$from}>\r\nReply-To: {$from}\r\nMIME-Version: 1.0\r\nContent-Type: multipart/alternative; boundary=\"{$b}\"\r\nList-Unsubscribe: <" . mem_site_url() . "/newsletters.php>\r\n";
    $msg = "--{$b}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n{$text}\r\n\r\n--{$b}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n{$html}\r\n\r\n--{$b}--";
    $ok = false; try { $ok = @mail($to, mb_encode_mimeheader($subject), $msg, $headers); } catch (Throwable $e) { $ok = false; }
    $l = $conn->prepare("INSERT INTO member_emails (member_id, kind, to_email, subject, body, sent) VALUES (NULL, 'newsletter', ?, ?, ?, ?)"); $sent = $ok ? 1 : 0; $short = mb_substr($text, 0, 500);
    $l->bind_param("sssi", $to, $subject, $short, $sent); $l->execute();
    return $ok;
}

/** Sends up to $limit pending recipients; returns [sent, failed, remaining]. */
function nl_send_batch($conn, $n, $limit = 40) {
    $id = (int)$n['id'];
    $r = $conn->query("SELECT s.* FROM subscribers s WHERE s.unsubscribed=0 AND NOT EXISTS (SELECT 1 FROM newsletter_sends x WHERE x.newsletter_id=$id AND x.email COLLATE utf8mb4_unicode_ci = s.email COLLATE utf8mb4_unicode_ci) ORDER BY s.id LIMIT " . (int)$limit);
    $sent = $failed = 0;
    foreach ($r->fetch_all(MYSQLI_ASSOC) as $sub) {
        $subject = $n['title'] . ($n['issue_label'] !== '' ? ' — ' . $n['issue_label'] : '');
        $ok = nl_mail($conn, $sub['email'], $subject, nl_email_html($conn, $n, $sub), nl_email_text($conn, $n, $sub));
        $st = $ok ? 'sent' : 'failed';
        $l = $conn->prepare("INSERT IGNORE INTO newsletter_sends (newsletter_id, email, status) VALUES (?,?,?)"); $l->bind_param("iss", $id, $sub['email'], $st); $l->execute();
        $ok ? $sent++ : $failed++;
    }
    $rem = (int)$conn->query("SELECT COUNT(*) FROM subscribers s WHERE s.unsubscribed=0 AND NOT EXISTS (SELECT 1 FROM newsletter_sends x WHERE x.newsletter_id=$id AND x.email COLLATE utf8mb4_unicode_ci = s.email COLLATE utf8mb4_unicode_ci)")->fetch_row()[0];
    $tot = (int)$conn->query("SELECT COUNT(*) FROM newsletter_sends WHERE newsletter_id=$id AND status='sent'")->fetch_row()[0];
    $conn->query("UPDATE newsletters SET sent_total=$tot, sent_at=IF(sent_at IS NULL, NOW(), sent_at) WHERE id=$id");
    return [$sent, $failed, $rem];
}

/** Public subscribe form (posts to newsletters.php). Needs forms.php + content.php loaded. */
function nl_subscribe_box($sent, array $errors) {
    ob_start(); ?>
    <div class="subscribe-box">
      <div class="subscribe-box__intro"><div class="eyebrow">Stay in the loop</div><h2><?php echo cms_t('newsletters', 'subscribe_heading'); ?></h2><p><?php echo cms_t('newsletters', 'subscribe_text'); ?></p></div>
      <div class="form-card">
        <?php echo form_banner($sent, $errors, 'You are subscribed. Watch your inbox for the next NUFA newsletter.'); ?>
        <?php if (!$sent): ?>
        <form method="post" action="newsletters.php#subscribe"><input type="hidden" name="form" value="subscribe"><?php echo form_open_fields(); ?>
          <div class="form-grid">
            <?php echo f_input('name', 'Your name (optional)', ['autocomplete' => 'name']); ?>
            <?php echo f_input('email', 'Email address', ['type' => 'email', 'required' => true, 'autocomplete' => 'email']); ?>
          </div>
          <?php echo f_check('consent', 'Yes, send me the NUFA newsletter and news updates. I can unsubscribe at any time.'); ?>
          <button class="btn btn-gold btn-block" type="submit">Subscribe</button>
        </form><?php endif; ?>
      </div>
    </div>
    <?php return ob_get_clean();
}

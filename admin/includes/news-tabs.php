<?php
// Shared tab strip for the News & Newsletters admin pages. Set $newsTab before including.
$__nt = ['posts' => ['posts.php', 'News & stories'], 'newsletters' => ['newsletters.php', 'Newsletters'], 'subscribers' => ['subscribers.php', 'Subscribers']];
?>
<div class="news-tabs"><?php foreach ($__nt as $k => [$u, $l]): ?><a href="<?php echo $u; ?>" class="<?php echo ($newsTab ?? '') === $k ? 'active' : ''; ?>"><?php echo $l; ?></a><?php endforeach; ?><a href="../news.php" target="_blank">View news ↗</a><a href="../newsletters.php" target="_blank">View newsletters ↗</a></div>

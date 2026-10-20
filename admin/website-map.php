<?php
$adminTitle = "Website Map — what you can configure";
$adminNav = "map";
require __DIR__ . "/includes/auth.php";

// Every public page, what appears on it, and exactly where to change each part.
$pages = [
    ['Whole site (every page)', 'index.php', [
        ['Logo, favicon, share image, brand colours, contact bar', 'appearance.php', 'Appearance'],
        ['Fonts, phone, email, address, social links', 'settings.php', 'Site Settings'],
        ['Header and footer menus, footer links', 'menus.php', 'Menus & Footer'],
        ['Header button, footer wording, copyright, mobile menu button', 'content-edit.php?page=site', 'Page Content → Site-wide'],
        ['Browser titles and search descriptions for every page', 'content-edit.php?page=seo', 'Page Content → SEO'],
        ['Approval switches (vision, policies, tickets), verified impact figures', 'settings.php', 'Site Settings'],
    ]],
    ['Home', 'index.php', [
        ['Hero photographs (slideshow)', 'sliders.php', 'Hero Images'],
        ['Headline, hero buttons, Who We Are, Awards feature, Impact, Partnership, news heading, call-to-action', 'content-edit.php?page=home', 'Page Content → Home'],
        ['Programme cards', 'programmes.php', 'Programmes'],
        ['Latest news cards', 'posts.php', 'News & Stories'],
    ]],
    ['About NUFA / Leadership / Membership', 'about.php', [
        ['Story, vision, mission, values, membership text and steps', 'content-edit.php?page=about', 'Page Content → About'],
        ['Leadership profiles (name, role, bio, portrait, links)', 'team.php', 'Leadership'],
        ['Membership form fee, payment instructions, guilds, card, emails', 'membership-settings.php', 'Membership settings'],
    ]],
    ['NUFA Awards & edition pages', 'awards.php', [
        ['Landing-page text, platform list, participation info, submission link', 'content-edit.php?page=awards', 'Page Content → NUFA Awards'],
        ['Each edition: dates, venue, categories, nominees, sponsors, press, video', 'award-editions.php', 'Award Editions'],
        ['Winners', 'award-winners.php', 'Winners'],
        ['Photos shown on an edition page', 'gallery.php', 'Gallery'],
    ]],
    ['Programmes (list + detail pages)', 'programmes.php', [
        ['Intro, status note, call-to-action', 'content-edit.php?page=programmes', 'Page Content → Programmes'],
        ['Each programme: all sections, status, image, order', 'programmes.php', 'Programmes'],
    ]],
    ['NUFA SACCO', 'sacco.php', [['Status banner, background, objectives, FAQs', 'content-edit.php?page=sacco', 'Page Content → SACCO'], ['Expression-of-interest submissions', 'enquiries.php?type=sacco', 'Enquiries']]],
    ['Industry Network', 'network.php', [['Intro, categories, profile fields', 'content-edit.php?page=network', 'Page Content → Network'], ['Role choices on the form', 'lists.php', 'Form options'], ['Interest submissions', 'enquiries.php?type=network', 'Enquiries']]],
    ['News & Stories', 'news.php', [['Stories, categories, captions, featured story', 'posts.php', 'News & Stories'], ['Category list', 'lists.php', 'Form options'], ['Intro text', 'content-edit.php?page=news', 'Page Content → News']]],
    ['Resources', 'resources.php', [['Documents and links', 'resources.php', 'Resources'], ['Category list', 'lists.php', 'Form options'], ['Intro text', 'content-edit.php?page=resources', 'Page Content → Resources']]],
    ['Gallery & Video Library', 'gallery.php', [['Photographs', 'gallery.php', 'Gallery'], ['Videos (YouTube / Vimeo)', 'videos.php', 'Video Library'], ['Intro and credit note', 'content-edit.php?page=gallery', 'Page Content → Gallery']]],
    ['Partner With Us', 'partners.php', [['Opportunities list, why partner, intro', 'content-edit.php?page=partners', 'Page Content → Partner'], ['Current partners / previous supporters and logos', 'partners.php', 'Partners'], ['Form choices', 'lists.php', 'Form options'], ['Enquiries received', 'enquiries.php?type=partner', 'Enquiries']]],
    ['Contact', 'contact.php', [['Intro, office hours', 'content-edit.php?page=contact', 'Page Content → Contact'], ['Topic choices', 'lists.php', 'Form options'], ['Messages received', 'enquiries.php?type=contact', 'Enquiries']]],
    ['Policies (privacy, cookies, terms …)', 'policy.php?p=privacy', [['Policy wording', 'content-edit.php?page=policies', 'Page Content → Policies'], ['"Reviewed by a professional" switch', 'settings.php', 'Site Settings']]],
];
include __DIR__ . "/includes/admin-header.php";
?>
<p class="admin-help" style="margin-bottom:18px">Everything visitors see can be changed from the dashboard. Find a page below and follow the link beside the part you want to edit.</p>
<?php foreach ($pages as [$name, $url, $items]): ?>
<div class="admin-card">
  <div class="admin-card-head"><h2><?php echo htmlspecialchars($name); ?></h2><a href="../<?php echo htmlspecialchars($url); ?>" target="_blank" class="admin-btn admin-btn-line admin-btn-sm">View page</a></div>
  <table class="admin-table"><tbody>
  <?php foreach ($items as [$what, $link, $label]): ?>
    <tr><td><?php echo htmlspecialchars($what); ?></td><td style="text-align:right;white-space:nowrap"><a class="admin-btn admin-btn-brand admin-btn-sm" href="<?php echo htmlspecialchars($link); ?>"><?php echo htmlspecialchars($label); ?></a></td></tr>
  <?php endforeach; ?>
  </tbody></table>
</div>
<?php endforeach; ?>
<?php include __DIR__ . "/includes/admin-footer.php"; ?>

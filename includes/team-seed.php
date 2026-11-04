<?php
/** One-time: adds Okello Odong (Head of Technical and Production) to the leadership team. Safe on every request; edit or remove him in Admin → Team. */
function team_seed_okello($conn) {
    $f = $conn->query("SELECT 1 FROM settings WHERE setting_key='team_okello_v1'");
    if (!$f || $f->num_rows) return;
    if (!is_file(__DIR__ . '/../assets/images/team/team-technical-production.webp')) return;   // photo not uploaded yet
    $name = 'Okello Odong'; $role = 'Head of Technical and Production'; $photo = 'team/team-technical-production.webp';
    $bio = "Okello Odong is a Ugandan filmmaker, screenwriter, director, producer, editor, actor, film educator and creative entrepreneur with over 15 years of experience in the film and creative industries. He is passionate about storytelling, creative innovation and developing opportunities for emerging filmmakers.\n\n"
        . "Since 2011, Okello has been actively involved in film production, training and mentorship, working across various aspects of filmmaking, including screenwriting, directing, cinematography, sound, editing and production management. Through his work with organisations and initiatives such as Maisha Film Lab, he has contributed to building creative capacity and nurturing emerging talent. He has trained over 1,000 young people and aspiring creatives in different disciplines of film and multimedia production.\n\n"
        . "His work has earned him recognition at local, regional and international film festivals, making him an award-winning filmmaker whose projects have received recognition in Uganda, Ghana and the United Kingdom.\n\n"
        . "At the Northern Uganda Filmmakers' Association (NUFA), Okello serves as the Head of Technical and Production, bringing his creative, technical and production expertise to support the association's programmes, initiatives and broader mission of strengthening the film and television industry in Northern Uganda.\n\n"
        . "He remains committed to using film as a tool for creative expression, education, cultural preservation and social transformation.";
    $has = $conn->prepare("SELECT id FROM team_members WHERE name=? LIMIT 1"); $has->bind_param("s", $name); $has->execute();
    if (!$has->get_result()->num_rows) {
        $o = (int)$conn->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM team_members")->fetch_row()[0];
        $s = $conn->prepare("INSERT INTO team_members (name, role, photo, bio, sort_order, is_active) VALUES (?,?,?,?,?,1)");
        $s->bind_param("ssssi", $name, $role, $photo, $bio, $o); $s->execute();
    }
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('team_okello_v1','1') ON DUPLICATE KEY UPDATE setting_value='1'");
}

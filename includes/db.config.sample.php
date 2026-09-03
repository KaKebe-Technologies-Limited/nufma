<?php
/**
 * Optional credential override.
 *
 * db.php works without this file (it auto-detects local vs. live). Copy this
 * to db.config.php only if you need to force specific credentials on a given
 * environment. db.config.php is git-ignored so passwords stay out of the repo.
 */
return [
    "host" => "localhost",
    "user" => "u850523537_NUFAUser2",
    "pass" => "your-db-password",
    "name" => "u850523537_NUFADB2",
];

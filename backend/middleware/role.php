<?php
require_once __DIR__ . '/auth.php';

function require_role(string ...$roles): array {
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        send_error(403, 'Forbidden. You do not have permission.');
    }
    return $user;
}

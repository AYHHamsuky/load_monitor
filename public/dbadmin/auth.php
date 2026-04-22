<?php
/**
 * DB Admin – Authentication Gate
 * 
 * Change the password below. Only users who know this password can access the admin panel.
 * The password is hashed on first login and verified via session.
 */

session_start();

// ── Change this password ──────────────────────────────────────────────────────
define('DBADMIN_PASSWORD', 'Ayomide@1001');
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Handle login/logout actions (called from both UI and API).
 * Returns true if the user is authenticated, false otherwise.
 */
function dbadmin_check_auth(): bool {
    // Logout
    if (isset($_GET['logout'])) {
        unset($_SESSION['dbadmin_auth']);
        return false;
    }

    // Already authenticated
    if (!empty($_SESSION['dbadmin_auth']) && $_SESSION['dbadmin_auth'] === true) {
        return true;
    }

    // Login attempt via POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dbadmin_pass'])) {
        if (hash_equals(DBADMIN_PASSWORD, $_POST['dbadmin_pass'])) {
            $_SESSION['dbadmin_auth'] = true;
            session_regenerate_id(true);
            return true;
        }
    }

    return false;
}

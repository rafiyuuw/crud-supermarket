<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Index Redirector
 * File: index.php
 */

require_once __DIR__ . '/includes/fungsi.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . base_url('login.php'));
} else {
    header('Location: ' . base_url('dashboard.php'));
}
exit;

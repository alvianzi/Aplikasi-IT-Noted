<?php
require_once __DIR__ . '/config.php';
start_session();

if (!empty($_SESSION['user_id'])) {
    header('Location: app.html');
} else {
    header('Location: login.html');
}
exit;

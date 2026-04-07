<?php
session_start();
// Auto backup on logout
@include 'backup_db.php';
if (function_exists('perform_db_backup')) {
    perform_db_backup();
}
session_destroy();
header('Location: login.php');
exit;
?>

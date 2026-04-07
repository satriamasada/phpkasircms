<?php
/**
 * Simple Database Backup Script
 * Automatically creates a SQL dump of the pos_rbac database
 */

function perform_db_backup() {
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $dbname = 'pos_rbac';
    $backup_dir = __DIR__ . '/backups';

    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0777, true);
    }

    $filename = 'backup_' . $dbname . '_' . date('Y-m-d_H-i-s') . '.sql';
    $backup_file = $backup_dir . '/' . $filename;

    // Command for mysqldump (assumes mysqldump is in PATH)
    $command = "mysqldump --user=$user --password=$pass --host=$host $dbname > $backup_file";

    system($command, $result_code);

    if ($result_code === 0) {
        // Optional: Keep only last 10 backups
        $files = glob($backup_dir . '/*.sql');
        if (count($files) > 10) {
            $mtimes = array_map('filemtime', $files);
            array_multisort($mtimes, SORT_ASC, $files);
            unlink($files[0]);
        }
        return true;
    }
    return false;
}

// Check if run directly or included
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    if (perform_db_backup()) {
        echo "Backup successful.\n";
    } else {
        echo "Backup failed. Make sure mysqldump is in your PATH.\n";
    }
}
?>

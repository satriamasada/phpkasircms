<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';

// Check admin
if (!hasPermission('manage_users') && !hasPermission('manage_settings')) {
    header("Location: index.php");
    exit;
}

$tables = array();
$result = $pdo->query('SHOW TABLES');
while($row = $result->fetch(PDO::FETCH_NUM)) {
    $tables[] = $row[0];
}

$return = "-- Database Backup\n";
$return .= "-- Generated: " . date("Y-m-d H:i:s") . "\n\n";
$return .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
$return .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
$return .= "START TRANSACTION;\n";
$return .= "SET time_zone = \"+00:00\";\n\n";

foreach($tables as $table) {
    try {
        $result = $pdo->query('SELECT * FROM `'.$table.'`');
        $num_fields = $result->columnCount();

        $return .= 'DROP TABLE IF EXISTS `'.$table.'`;';
        $row2 = $pdo->query('SHOW CREATE TABLE `'.$table.'`')->fetch(PDO::FETCH_NUM);
        $return .= "\n\n".$row2[1].";\n\n";

        while($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $return .= 'INSERT INTO `'.$table.'` VALUES(';
            $idx = 0;
            foreach ($row as $val) {
                if (is_null($val)) { 
                    $return .= 'NULL'; 
                } else { 
                    $val = addslashes($val);
                    $val = str_replace("\n", "\\n", $val);
                    $return .= '"'.$val.'"' ; 
                }
                if ($idx < ($num_fields-1)) {
                    $return .= ','; 
                }
                $idx++;
            }
            $return .= ");\n";
        }
        $return .= "\n\n\n";
    } catch (PDOException $e) {
        continue;
    }
}

$return .= "COMMIT;\n";
$return .= "SET FOREIGN_KEY_CHECKS=1;\n";

// Save file offline in backups folder
$backup_dir = __DIR__ . '/backups';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}
global $dbname;
if (empty($dbname)) $dbname = 'belajarphpkasir'; // default fallback
$filename = 'backup_' . $dbname . '_' . date('Y-m-d_H-i-s') . '.sql';
$backup_file = $backup_dir . '/' . $filename;
file_put_contents($backup_file, $return);

// Keep only the last 10 backups
$files = glob($backup_dir . '/*.sql');
if (is_array($files) && count($files) > 10) {
    $mtimes = array_map('filemtime', $files);
    array_multisort($mtimes, SORT_ASC, $files);
    unlink($files[0]);
}

// Download the file directly
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
echo $return;
exit;
?>

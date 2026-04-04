<?php
// apply_db_updates.php
// Run this script to automatically import cafe_db.sql into the local database.
// Usage: c:\xampp\php\php.exe apply_db_updates.php (used in project folder with powershell terminal)

require_once 'db_connect.php';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=cafe_db", "root", ""); // Adjust credentials if needed
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Read the SQL file
    $sql = file_get_contents('cafe_db.sql');
    if (!$sql) {
        die("Error: Could not read cafe_db.sql\n");
    }

    // Disable foreign key checks before dropping tables
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

    // Drop child tables in reverse order to avoid foreign key issues
    $dropTables = [
        'orders',
        'products',
        'reservations',
        'categories',
        'users',
    ];

    foreach ($dropTables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
    }

    // Re-enable foreign key checks before importing the SQL file
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

    // Remove MySQL/MariaDB conditional restore statements that may reference undefined session variables.
    $sql = preg_replace('/\/\*!\d{5} .*?\*\//s', '', $sql);

    // Split into statements (basic split by semicolon followed by optional whitespace)
    $statements = array_filter(array_map('trim', preg_split('/;\s*(\r?\n|$)/', $sql)));

    foreach ($statements as $stmt) {
        if (empty($stmt)) {
            continue;
        }

        if (strpos(trim($stmt), '/*!') === 0) {
            continue;
        }

        $pdo->exec($stmt);
    }

    echo "Database updated successfully from cafe_db.sql\n";
} catch (PDOException $e) {
    die("Error updating database: " . $e->getMessage() . "\n");
}
?>
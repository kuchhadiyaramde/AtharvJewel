<?php
/**
 * Database configuration — edit these values to match your MySQL setup.
 */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'atharv_jewel');

function getDBConnection(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            error_log('MySQL connection error: ' . $conn->connect_error);
            http_response_code(500);
            exit('Database unavailable. Check config.php.');
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

<?php
// Strict MySQLi error reporting (throws exceptions instead of silent errors)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

return [
    'host'     => '127.0.0.1',
    'username' => 'root',
    'password' => '',
    'database' => 'ecommerce_db',
    'port'     => 3306,
    'charset'  => 'utf8mb4'
];
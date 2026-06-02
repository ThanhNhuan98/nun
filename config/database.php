<?php

return [
    'host'     => $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1',
    'port'     => $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306',
    'dbname'   => $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'nun_db', 
    'username' => $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '',
    'charset'  => $_ENV['DB_CHARSET'] ?? getenv('DB_CHARSET') ?: 'utf8mb4'
];

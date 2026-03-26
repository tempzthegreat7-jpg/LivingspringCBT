<?php

return [
    'host'     => getenv('DB_HOST') ?: 'localhost',
    'port'     => (int) (getenv('DB_PORT') ?: 3306),
    'dbName'   => getenv('DB_NAME') ?: 'livingspring_cbt',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: '',
];

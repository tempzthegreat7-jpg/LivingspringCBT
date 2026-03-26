<?php

return [
    'host'     => getenv('DB2_HOST') ?: (getenv('DB_HOST') ?: 'localhost'),
    'port'     => (int) (getenv('DB2_PORT') ?: (getenv('DB_PORT') ?: 3306)),
    'dbName'   => getenv('DB2_NAME') ?: 'livingspring_cbt_subjects',
    'username' => getenv('DB2_USER') ?: (getenv('DB_USER') ?: 'root'),
    'password' => getenv('DB2_PASS') ?: (getenv('DB_PASS') ?: ''),
];

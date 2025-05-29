<?php
return [
    'db' => [
        'host'    => 'localhost',
        'dbname'  => 'mina_recebo',
        'user'    => 'root',
        'pass'    => '',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ],
    ],
];
?>

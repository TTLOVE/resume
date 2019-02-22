<?php

if(getenv('RUNTIME_ENVIROMENT') == "DEV"){
    $config = array(
        'resume' => array(
            'driver'    => 'mysql',
            'host'      => '127.0.0.1',
            'database'  => 'resume',
            'username'  => 'root',
            'password'  => 'zyz123',
            'charset'   => 'utf8',
            'collation' => 'utf8_general_ci',
            'port'      => 6606,
            'prefix'    => ''
        ),
    );
}else{
    $config = array(
        'resume' => array(
            'driver'    => 'mysql',
            'host'      => '127.0.0.1',
            'database'  => 'resume',
            'username'  => 'root',
            'password'  => 'zyz123',
            'charset'   => 'utf8',
            'collation' => 'utf8_general_ci',
            'port'      => 3306,
            'prefix'    => ''
        ),
    );
}

return $config;

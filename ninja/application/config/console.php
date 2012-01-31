<?php
return \Ninja\Config::mergeArray(
require( NINJA_APPLICATION_CONFIG_PATH . '/main.php' ),

array(
	'ninja' => array(
		'resource'	=> array(
			// Database Related
			'db' => array(
				'default' => array(
					'host'			=> 'localhost',
					'username'		=> 'root',
					'password'		=> '',
					'dbname'		=> 'kachra'
				),
			),
		), // end resource

        'foo' => 'consoleee!',
	),
    // ----- CUSTOM VALUES ------------




) // end development config
);

<?php
return \Ninja\Config::mergeArray(
require(__DIR__ . '/main.php'),

array(
	'ninja' => array(
		'resource'	=> array(
			// Database Related
			'db' => array(
				'default' => array(
                    'dbname' => 'mydb',
                    'user' => 'user',
                    'password' => 'secret',
                    'host' => 'localhost',
                    'driver' => 'pdo_mysql'
				),
				'jukebox' => array(
                    'dbname' => 'mydb',
                    'user' => 'user',
                    'password' => 'secret',
                    'host' => 'localhost',
                    'driver' => 'pdo_mysql'
				),

			),
			
			
			// Mailing Related
			'mail' => array(

				/* // GMAIL SMTP TRANSPORT - http://bit.ly/fqOHfj
				'transport' => array(
					'type' 		=> 'smtp',
					'host' 		=> 'smtp.gmail.com',
					'auth' 		=> 'login',
					'ssl'		=> 'ssl', 				//  Requires the php_openssl extension to use the SSL transport protocol
					'port'		=> '465',
					'username' 	=> 'username@gmail.com',
					'password' 	=> 'password',
				),*/
				
				 // FILE TRANSPORT
				'transport' => array(
					'type' => 'file',
				),
			),
			
			
		), // end resource
	),
    // ----- CUSTOM VALUES ------------
    
    
    
    
) // end development config
);		

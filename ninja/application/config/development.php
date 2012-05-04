<?php
return \Ninja\Config::mergeArray(
require(__DIR__ . '/main.php'),

array(
	'ninja' => array(
		'resource'	=> array(
			// Database Related
			'db' => array(

                // Configure Doctrine DBAL connection for the main database
				'default' => array(
                    'driver' => 'pdo_mysql',
                    'host' => 'localhost',
                    'dbname' => 'sakila',
                    'user' => 'root',
                    'password' => 'hgfdsa'
				),


				'jukebox' => array(
                    'driver' => 'pdo_mysql',
                    'host' => 'localhost',
                    'dbname' => 'jukebox',
                    'user' => 'user',
                    'password' => 'secret'
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

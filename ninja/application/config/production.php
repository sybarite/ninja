<?php
return \Ninja\Config::mergeArray(
require(__DIR__ . '/main.php'),

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
			
			// Mailing Related
			'mail' => array(

				// SMTP TRANSPORT EXAMPLE (if not defined, sendmail will be used)
				/*'transport' => array(
					'type' 		=> 'smtp',
					'host' 		=> 'smtp.example.com',
					'auth' 		=> 'login',
					'username' 	=> 'myUsername',
					'password' 	=> 'myPassword',
				),*/
			),
			
			
		), // end resource
		
		
		// ----- CUSTOM VALUES ------------
	),
)
		
			
		

);
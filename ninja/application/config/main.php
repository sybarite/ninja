<?php

// Give a unique name to your application
define('NINJA_NAME' , 'ninjutsu'); // lowercase, underscore allowed, no spaces or other special characters.

return array(
	// Ninja Application + Framework Settings
	'ninja' => array(
		'name'		=> NINJA_NAME,
		'resource'	=> array(
			
			// Session Configuration - http://bit.ly/eZ16kc
			'session' => array(
				'name' 					=> NINJA_NAME . '_ninja_session',	// MUST be unique
				//'save_path'				=> NINJA_TMP_PATH . 'session',		// MAKE SURE this directory exists.
				'use_only_cookies'		=> TRUE,							// Always enable for security
				//'cookie_lifetime'		=> 86400,							// specifies the lifetime of the session cookie (default 0)
				//'remember_me_seconds'	=> 30,
				//'validators'			=> array('Zend_Session_Validator_HttpUserAgent'),
			),
			
			// Cookie Related
			'cookie' => array(
				'salt'	=> 'n2w40RtdVnv6XldlCS6vRQR9YT1i7305', // Secure key, use http://randomkeygen.com/
				'expire' => '+1 month', 		// Keep cookies by default for one month
			),
			
			// Cache Templates
			'cache' => array(
				'local_persistent' => array(
						'frontend' => array(
							'name'		=> 'Core',
							'options'	=> array(
								'lifetime'					=> 7200,					// 2 hours lifetime by default
								'automatic_serialization'	=> TRUE,
								'ignore_user_abort'			=> TRUE
							)
						),
						'backend' => array(
							'name'		=> 'File',
							'options'	=> array(
								'cache_dir'					=> NINJA_TMP_PATH . 'cache', // MAKE SURE this directory exists.
								'hashed_directory_level'	=> 2
							)
						)
				),
			),
			
			// Mailing Related
			'mail' => array(
				'defaultFrom' => array(
					'email' => 'john@example.com',
					'name'  => 'John Doe'
				),
				/*'defaultReplyTo' => array(
					'email' => 'jane@example.com',
					'name'  => 'Jane Doe'
				),*/
			),
			
		), // end resource
	),
	
	
	// ----- CUSTOM VALUES ------------
	
	// Common paths
	'path' => array(
        'assets' => 'assets/',
		//'static' => 'static/',		// location of css, js, static files relative to application url
	),
	
	
); //end main config
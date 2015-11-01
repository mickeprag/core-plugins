<?php

return array(
	'id' =>             'source:facebook', # notrans
	'version' =>        '0.1',
	'name' =>           /* trans */ 'Facebook integration plugin',
	'author' =>         'Micke Prag',
	'description' =>    /* trans */ 'Provides a Facebook integration',
	'url' =>            'http://www.osticket.com/plugins',
	'plugin' =>         'facebook.php:FacebookPlugin',
	'requires' => array(
		"facebook/php-sdk-v4" => array(
				"version" => "~5.0",
				"map" => array(
					"facebook/php-sdk-v4/src" => 'lib',
				)
		),
	),
);

?>

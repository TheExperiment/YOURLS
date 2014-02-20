<?php
// Handle inexistent root favicon requests and exit
if ( '/favicon.ico' == $_SERVER['REQUEST_URI'] ) {
	header( 'Content-Type: image/gif' );
	echo base64_decode( "R0lGODlhEAAQAJECAAAAzFZWzP///wAAACH5BAEAAAIALAAAAAAQABAAAAIplI+py+0PUQAgSGoNQFt0LWTVOE6GuX1H6onTVHaW2tEHnJ1YxPc+UwAAOw==" );
	exit;
}

// Handle inexistent root robots.txt requests and exit
if ( '/robots.txt' == $_SERVER['REQUEST_URI'] ) {
	header( 'Content-Type: text/plain; charset=utf-8' );
	echo "User-agent: *\n";
	echo "Disallow:\n";
	exit;
}

// Start YOURLS
require __DIR__ . '/includes/vendor/autoload.php';

$yourls = new YOURLS\Loader;
$yourls->run();

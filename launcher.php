<?php
require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/config.default.php";

$bot = new Feng\LinkyBot\Bot( $config );
if ( !$bot->sanityCheck() ) {
	echo "Naive! API key都搞不对怎么续命啊！\n";
	exit;
}
echo "API key okay! Starting...\n";
$bot->run();

<?php
use IngressSentinel\IngressSentinel;
require 'vendor/autoload.php';

$idr = new IngressSentinel(array(
	'host' => 'imap.gmail.com', //gmail: imap.gmail.com
	'port' => '993',     //gmail: 993
	'user' => 'example@gmail.com',   //example@example.com
	'pass' => 'password',       //mail pass
	'ssl'  => 'ssl'         //ssl
));
$idr->process('UNSEEN');
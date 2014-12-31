<?php
use IngressSentinel\IngressSentinel;
require 'vendor/autoload.php';

$idr = new IngressSentinel(array(
    'email' => array(
        'host' => 'imap.gmail.com', //gmail: imap.gmail.com
        'port' => '993',            //gmail: 993
        'user' => 'ffff',           //example@example.com
        'pass' => '',               //mail pass
        'ssl'  => 'ssl'             //ssl
    ),
    'db' => array(
        'dsn'  => 'mysql:dbname=sentinels;host=127.0.0.1',
        'user' => 'username',
        'pass' => 'password'
    )
));
$idr->process('UNSEEN SUBJECT "Ingress Damage Report"');

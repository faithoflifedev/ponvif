<?php
require __DIR__ . '/../src/Onvif/class.ponvif.php';

$ponvif = new Ponvif();

$ponvif->setUsername( "admin" );
$ponvif->setPassword( "admin12345" );
$ponvif->setIPAddress( "192.168.1.182" );

$ponvif->initialize();
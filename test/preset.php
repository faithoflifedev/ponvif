<?php 
require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

use Onvif\Onvif;

$toPreset = null;

if ( $argc == 2 && intval( $argv[1] ) > 0 ):
	$toPreset = $argv[1];
endif;

$onvif = new Onvif();

$onvif->initialize( "192.168.1.182", "admin", "admin12345" );

$profileToken = $onvif->sources[0]['profiletoken'];

$positions = array(
	"preifar" => 4,
	"taraweeh" => 9,
	"khutbah" => 11,
);

if ( $toPreset )
	$onvif->gotoPreset( $profileToken, $toPreset, 0.1, 0.1, 0.1 );

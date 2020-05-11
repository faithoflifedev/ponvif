<?php 
require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

use Onvif\Onvif;

$toPreset = null;

if ( $argc == 2 && intval( $argv[1] ) > 0 ):
	$toPreset = $argv[1];
endif;

$onvif = new Onvif();

$onvif->initialize( "192.168.1.182", "admin", "admin12345" );

//print_r( $onvif->getServices() );

//print_r( $onvif->getDeviceInformation() );

//print_r( $onvif->getProfiles() );

$profileToken = $onvif->sources[0]['profiletoken'];

$mediaUri = $onvif->getStreamUri( $profileToken );

echo "mediuri: " . $mediaUri . "\n\n";

$snapshotUri = $onvif->getSnapshotUri( $profileToken );

echo "snapshoturi: " . $snapshotUri . "\n\n";

//print_r( $onvif->getVideoEncoderConfigurations() );

//print_r( $onvif->getVideoEncoderConfigurationOptions( $profileToken) );

$osds = $onvif->getOSDs();

if ( array_key_exists( "@token", $osds ) ) {
	$onvif->deleteOSD( $token );
} else {
	foreach ( $osds as $osd ):
		$token = $osd["@token"];
	
		if ( $token != "" ):
			echo "removing osd: " . $token . "\n\n";
		
			$onvif->deleteOSD( $token );
		endif;
	endforeach;	
}

//print_r( $onvif->setPreset( $profileToken, "PreIftar", 4 ) );

/* $presets = $onvif->getPresets( $profileToken );

foreach ( $presets as $index => $preset ):
	$name = $preset["tt:Name"];
	$token = $preset["@token"];

	echo $index . " - token: " . $token . " name: " . $name . "\n"; 

	if ( $index == 15 )
		break;
endforeach; */

$positions = array(
	"preifar" => 4,
	"taraweeh" => 9,
	"khutbah" => 11,
);

if ( $toPreset )
	print_r( $onvif->gotoPreset( $profileToken, $toPreset, 0.1, 0.1, 0.1 ) );

//print_r( $onvif->removePreset( $profileToken, 12 ) );

//print_r( $onvif->absoluteMove( $profileToken, 0.813, -0.89, 0.26 ) );

//print_r( $onvif->continuousMoveZoom( $profileToken, 0.01 ) );

//$onvif->continuousMove( $profileToken, 0.2, 0 );

//print_r( $onvif->stop( $profileToken, true, true ) );

<?php

namespace Onvif;

class Onvif
{
	protected static $discoverytimeout = 2;
	protected static $discoverybindip = '0.0.0.0';
	protected static $discoverymcastip = '239.255.255.250';
	protected static $discoverymcastport = 3702;
	protected static $discoveryhideduplicates = true;

	protected $username;
	protected $passwd;
	protected $timeDelta;

	public $capabilities;
	public $videoSources;
	public $profiles;
	public $sources;

	public $cameraUri;
	public $serviceUri;
	public $mediaUri;
	public $deviceUri;
	public $ptzUri;
	public $baseUri;

	public $onvifVersion;

	public function initialize($host, $username, $passwd)
	{
		$this->username = $username;
		$this->passwd = $passwd;

		$this->serviceUri = "http://" . $host . "/onvif/device_service";

		try {
			$datetime = $this->getSystemDateAndTime();

			$timeStamp = mktime(
				intval($datetime["tt:Time"]["tt:Hour"]),
				intval($datetime["tt:Time"]["tt:Minute"]),
				intval($datetime["tt:Time"]["tt:Second"]),
				intval($datetime["tt:Date"]["tt:Month"]),
				intval($datetime["tt:Date"]["tt:Day"]),
				intval($datetime["tt:Date"]["tt:Year"])
			);

			$this->timeDelta = time() - $timeStamp;
		} catch (\Exception $error) {
			throw new \Exception("Could not get time from target device - " . $error);
		}

		$this->capabilities = $this->getCapabilities();

		$_onvifVersion = $this->_getOnvifVersion();

		$this->mediaUri = $_onvifVersion['media'];
		$this->deviceUri = $_onvifVersion['device'];
		$this->ptzUri = $_onvifVersion['ptz'];

		preg_match( "/^http(.*)onvif\//", $this->mediaUri, $matches );

		$this->baseUri = $matches[0];

		$this->onvifVersion = array(
			"major" => $_onvifVersion["major"],
			"minor" => $_onvifVersion["minor"]
		);

		$this->videoSources = $this->getVideoSources();

		$this->profiles = $this->getProfiles();
		$this->sources = $this->_getActiveSources();
	}

	public static function discover() {
		$result = array();

		$post_string = SoapRequest::$discover;

		try {
			if ( ($sock = @socket_create( AF_INET, SOCK_DGRAM, SOL_UDP ) ) == false ):
				echo( 'Create socket error: [' . socket_last_error() . '] ' . socket_strerror( socket_last_error() ) );
			endif;

			if ( @socket_bind($sock, Onvif::$discoverybindip, rand( 20000, 40000 ) ) == false ):
				echo( 'Bind socket error: [' . socket_last_error() . '] ' . socket_strerror( socket_last_error() ) );
			endif;

			socket_set_option( $sock, IPPROTO_IP, MCAST_JOIN_GROUP, array( 'group' => Onvif::$discoverymcastip ) );

			socket_sendto( $sock, $post_string, strlen( $post_string ), 0, Onvif::$discoverymcastip, Onvif::$discoverymcastport );

			$sock_read = array( $sock );

			$sock_write  = NULL;

			$sock_except = NULL;

			while ( socket_select( $sock_read, $sock_write, $sock_except, Onvif::$discoverytimeout ) > 0 ):
				if ( @socket_recvfrom($sock, $response, 9999, 0, $from, Onvif::$discoverymcastport ) !== false ):
					if ( $response != NULL && $response != $post_string ):
						$response = new SoapResponse( $response );

						print_r( $response ); die;

						/* if(!$this->isFault($response)){
							$response['Envelope']['Body']['ProbeMatches']['ProbeMatch']['IPAddr'] = $from;
							if($this->discoveryhideduplicates){
								$result[$from] = $response['Envelope']['Body']['ProbeMatches']['ProbeMatch'];
							} else {
								$result[] = $response['Envelope']['Body']['ProbeMatches']['ProbeMatch'];
							}
						} */
					endif;
				endif;
			endwhile;

			socket_close($sock);
		} catch ( \Exception $e ) {}

		sort( $result );

		return $result;
	}

	public function getSystemDateAndTime()
	{
		$response = Soap::send($this->serviceUri, SoapRequest::$getSystemDateAndTime);

		$systemDateAndTime = $response->data;

		if ( $response->isFault || $systemDateAndTime == null )
			throw new \Exception('GetSystemDateAndTime: Communication error: ' + $response->reason );

		return $systemDateAndTime['tds:GetSystemDateAndTimeResponse']['tds:SystemDateAndTime']['tt:UTCDateTime'];
	}

	//doesn't require authentication on my camera
	public function getCapabilities()
	{
//		$authFields = $this->_secure();

//		$soapRequest = new SoapRequest();

//		$soapRequest->authentication( $this->username, $authFields["digest"], $authFields["nonce"], $authFields["timestamp"] );

//		$soapRequest->command( SoapRequest::$getCapabilities );

//		$response = Soap::send( $this->serviceUri, $soapRequest->toString() );
		$response = Soap::send( $this->serviceUri, SoapRequest::$getCapabilities );

		$capabilities = $response->data;

		if ( $response->isFault || $capabilities == null )
			throw new \Exception('GetCapabilities: Communication error: ' + $response->reason );

		return $capabilities["tds:GetCapabilitiesResponse"]["tds:Capabilities"];
	}

	public function getVideoSources()
	{
		$authFields = $this->_secure();

		$soapRequest = new SoapRequest();

		$soapRequest->authentication( $this->username, $authFields["digest"], $authFields["nonce"], $authFields["timestamp"] );

		$soapRequest->command( SoapRequest::$getVideoSources );

		$response = Soap::send( $this->mediaUri, $soapRequest->toString() );

		$videoSources = $response->data;

		return $videoSources["trt:GetVideoSourcesResponse"]["trt:VideoSources"];
	}

	public function getProfiles()
	{
		$authFields = $this->_secure();

		$soapRequest = new SoapRequest();

		$soapRequest->authentication( $this->username, $authFields["digest"], $authFields["nonce"], $authFields["timestamp"] );

		$soapRequest->command( SoapRequest::$getProfiles );

		$response = Soap::send( $this->mediaUri, $soapRequest->toString() );

		$profiles = $response->data;

		return $profiles["trt:GetProfilesResponse"]["trt:Profiles"];
	}


	public function getServices()
	{
		$authFields = $this->_secure();

		$soapRequest = new SoapRequest();

		$soapRequest->authentication( $this->username, $authFields["digest"], $authFields["nonce"], $authFields["timestamp"] );

		$soapRequest->command( SoapRequest::$getServices );

		$response = Soap::send( $this->mediaUri, $soapRequest->toString() );

		$services = $response->data;

		return $response->isFault ? $response->reason : $services["trt:GetServices"];
	}

	public function getDeviceInformation() {
		$authFields = $this->_secure();

		$soapRequest = new SoapRequest();

		$soapRequest->authentication( $this->username, $authFields["digest"], $authFields["nonce"], $authFields["timestamp"] );

		$soapRequest->command( SoapRequest::$getDeviceInformation );

		$response = Soap::send( $this->mediaUri, $soapRequest->toString() );

		$deviceInformation = $response->data;

		return $response->isFault ? $response->reason : $deviceInformation["trt:GetDeviceInformation"];		
	}

	public function getStreamUri( $profileToken, $stream = "RTP-Unicast", $protocol = "RTSP" ) {
		$authFields = $this->_secure();

		$soapRequest = new SoapRequest();

		$soapRequest->authentication( $this->username, $authFields["digest"], $authFields["nonce"], $authFields["timestamp"] );

		$soapRequest->args( array( $protocol, $$stream, $profileToken ) );

		$soapRequest->command( SoapRequest::$getStreamUri );

		$response = Soap::send( $this->mediaUri, $soapRequest->toString() );

		$streamUri = $response->data;

		return $response->isFault ? $response->reason : $streamUri["trt:GetStreamUriResponse"]["trt:MediaUri"]["tt:Uri"];		
	}

	public function getSnapshotUri( $profileToken ) {
		$authFields = $this->_secure();

		$soapRequest = new SoapRequest();

		$soapRequest->authentication( $this->username, $authFields["digest"], $authFields["nonce"], $authFields["timestamp"] );

		$soapRequest->args( array( $profileToken ) );

		$soapRequest->command( SoapRequest::$getSnapshotUri );

		$response = Soap::send( $this->mediaUri, $soapRequest->toString() );

		$snapShotUri = $response->data;

		return $response->isFault ? $response->reason : $snapShotUri["trt:GetSnapshotUriResponse"]["trt:MediaUri"]["tt:Uri"];		
	}

	public function getVideoEncoderConfigurations() {
		$authFields = $this->_secure();

		$soapRequest = new SoapRequest();

		$soapRequest->authentication( $this->username, $authFields["digest"], $authFields["nonce"], $authFields["timestamp"] );

		$soapRequest->command( SoapRequest::$getVideoEncoderConfigurations );

		$response = Soap::send( $this->mediaUri, $soapRequest->toString() );

		$videoEncoderConfigurations = $response->data;

		return $response->isFault ? $response->reason : $videoEncoderConfigurations["trt:GetVideoEncoderConfigurationsResponse"]["trt:Configurations"];
	}

	public function GetVideoEncoderConfigurationOptions( $profileToken ) {
		$authFields = $this->_secure();

		$soapRequest = new SoapRequest();

		$soapRequest->authentication( $this->username, $authFields["digest"], $authFields["nonce"], $authFields["timestamp"] );

		$soapRequest->args( array( $profileToken ) );

		$soapRequest->command( SoapRequest::$getVideoEncoderConfigurationOptions );

		$response = Soap::send( $this->mediaUri, $soapRequest->toString() );

		$snapShotUri = $response->data;

		return $response->isFault ? $response->reason : $snapShotUri["trt:GetVideoEncoderConfigurationOptionsResponse"]["trt:Options"];		
	}

	public function setVideoEncoderConfiguration( $encoderConfiguration ) {
		$authFields = $this->_secure();

		$soapRequest = new SoapRequest();

		$soapRequest->authentication( $this->username, $authFields["digest"], $authFields["nonce"], $authFields["timestamp"] );

		if ( isset( $encoderConfiguration['RateControl'] ) ):
			$args = array( 
				$encoderConfiguration['RateControl']['FrameRateLimit'],
				$encoderConfiguration['RateControl']['EncodingInterval'],
				$encoderConfiguration['RateControl']['BitrateLimit']
			);

			$command = SoapRequest::$setVideoEncoderConfiguration["RateControl"];
		elseif ( isset($encoderConfiguration['MPEG4']) ):
			$args = array(
				$encoderConfiguration['MPEG4']['GovLength'],
				$encoderConfiguration['MPEG4']['Mpeg4Profile']
			);

			$command = SoapRequest::$setVideoEncoderConfiguration["MPEG4"];
		elseif ( isset($encoderConfiguration['H264']) ):
			$args = array(
				$encoderConfiguration['H264']['GovLength'],
				$encoderConfiguration['H264']['H264Profile']
			);

			$command = SoapRequest::$setVideoEncoderConfiguration["H264"];
		endif;

		$soapRequest->args( $args );

		$soapRequest->command( $command );

		$response = Soap::send( $this->mediaUri, $soapRequest->toString() ); die( "Not tested yet" );

		$videoEncoderConfiguration = $response->data;

		return $response->isFault ? $response->reason : $videoEncoderConfiguration;
	}

	public function getOSDs() {
		$authFields = $this->_secure();

		$soapRequest = new SoapRequest();

		$soapRequest->authentication( $this->username, $authFields["digest"], $authFields["nonce"], $authFields["timestamp"] );

		$soapRequest->command( SoapRequest::$getOSDs );

		$response = Soap::send( $this->mediaUri, $soapRequest->toString() );

		$osds = $response->data;

		if ( array_key_exists( "trt:OSDs", $osds["trt:GetOSDsResponse"] ) )
			$osds = $osds["trt:GetOSDsResponse"]["trt:OSDs"];
		else
			$osds = $osds["trt:GetOSDsResponse"];

		return $response->isFault ? $response->reason : $osds;
	}

	//returns an empty array upon success
	public function deleteOSD( $OSDToken ) {
		$authFields = $this->_secure();

		$soapRequest = new SoapRequest();

		$soapRequest->authentication( $this->username, $authFields["digest"], $authFields["nonce"], $authFields["timestamp"] );

		$soapRequest->args( array( $OSDToken ) );

		$soapRequest->command( SoapRequest::$deleteOSD );

		$response = Soap::send( $this->mediaUri, $soapRequest->toString() );

		$snapShotUri = $response->data;

		return $response->isFault ? $response->reason : $snapShotUri["DeleteOSDResponse"];		
	}

	public function getPresets( $profileToken ) {
		$authFields = $this->_secure();

		$soapRequest = new SoapRequest();

		$soapRequest->authentication( $this->username, $authFields["digest"], $authFields["nonce"], $authFields["timestamp"] );

		$soapRequest->args( array( $profileToken ) );

		$soapRequest->command( SoapRequest::$getPresets );

		$response = Soap::send( $this->ptzUri, $soapRequest->toString() );

		$presets = $response->data;

		return $response->isFault ? $response->reason : $presets["tptz:GetPresetsResponse"]["tptz:Preset"];
	}

	//returns an empty array
	public function gotoPreset( $profileToken, $presetToken, $speed_pantilt_x = 0.1, $speed_pantilt_y = 0.1,$speed_zoom_x = 0.1 ) {
		$authFields = $this->_secure();

		$soapRequest = new SoapRequest();

		$soapRequest->authentication( $this->username, $authFields["digest"], $authFields["nonce"], $authFields["timestamp"] );

		$soapRequest->args( array( $profileToken, $presetToken, $speed_pantilt_x, $speed_pantilt_y, $speed_zoom_x ) );

		$soapRequest->command( SoapRequest::$gotoPreset );

		$response = Soap::send( $this->ptzUri, $soapRequest->toString() );

		$preset = $response->data;

		return $response->isFault ? $response->reason : $preset["tptz:GotoPresetResponse"];	
	}

	//returns empty array
	public function removePreset( $profileToken, $presetToken ) {
		$authFields = $this->_secure();

		$soapRequest = new SoapRequest();

		$soapRequest->authentication( $this->username, $authFields["digest"], $authFields["nonce"], $authFields["timestamp"] );

		$soapRequest->args( array( $profileToken, $presetToken ) );

		$soapRequest->command( SoapRequest::$removePreset );

		$response = Soap::send( $this->ptzUri, $soapRequest->toString() );

		$preset = $response->data;

		return $response->isFault ? $response->reason : $preset["tptz:RemovePresetResponse"];	
	}

	//returns array with the presetToken
	public function setPreset( $profileToken, $presetName, $presetToken = null ) {
		$authFields = $this->_secure();

		$soapRequest = new SoapRequest();

		$soapRequest->authentication( $this->username, $authFields["digest"], $authFields["nonce"], $authFields["timestamp"] );

		$command = SoapRequest::$setPreset["general"];

		$args[] = $profileToken;
		$args[] = $presetName;

		if ( $presetToken ):
			$args[] = $presetToken;

			$command = SoapRequest::$setPreset["specific"];
		endif;

		$soapRequest->args( $args );

		$soapRequest->command( $command );

		$response = Soap::send( $this->ptzUri, $soapRequest->toString() );

		$preset = $response->data;

		return $response->isFault ? $response->reason : $preset["tptz:SetPresetResponse]"];			
	}

	public function relativeMove( $profileToken, $translation = null, $speed = null ) {
		$authFields = $this->_secure();

		$soapRequest = new SoapRequest();

		$soapRequest->authentication( $this->username, $authFields["digest"], $authFields["nonce"], $authFields["timestamp"] );

		$args = array( $profileToken );

		$command = SoapRequest::$relativeMove["both"];

		if ( !$translation ):
			$args[] = $speed;
			$command = SoapRequest::$relativeMove["speed"];
		elseif ( !$speed ):
			$args[] = $translation;
			$command = SoapRequest::$relativeMove["translation"];
		else:
			$args[] = $translation;
			$args[] = $speed;
		endif;

		$soapRequest->args( $args );

		$soapRequest->command( $command );

		$response = Soap::send( $this->ptzUri, $soapRequest->toString() );

		$preset = $response->data;

		return $response->isFault ? $response->reason : $preset["tptz:RelativeMoveResponse"];
	}

	//empty array
	public function absoluteMove( $profileToken, $position_pantilt_x, $position_pantilt_y, $zoom ) {
		$authFields = $this->_secure();

		$soapRequest = new SoapRequest();

		$soapRequest->authentication( $this->username, $authFields["digest"], $authFields["nonce"], $authFields["timestamp"] );

		$soapRequest->args( array( $profileToken, $position_pantilt_x, $position_pantilt_y, $zoom ) );

		$soapRequest->command( SoapRequest::$absoluteMove );

		$response = Soap::send( $this->ptzUri, $soapRequest->toString() );

		$absoluteMove = $response->data;

		return $response->isFault ? $response->reason : $absoluteMove["tptz:AbsoluteMoveResponse"];		
	}

	public function continuousMove( $profileToken, $velocity_pantilt_x, $velocity_pantilt_y ) {
		$authFields = $this->_secure();

		$soapRequest = new SoapRequest();

		$soapRequest->authentication( $this->username, $authFields["digest"], $authFields["nonce"], $authFields["timestamp"] );

		$soapRequest->args( array( $profileToken, $velocity_pantilt_x, $velocity_pantilt_y ) );

		$soapRequest->command( SoapRequest::$continuousMove );

		$response = Soap::send( $this->ptzUri, $soapRequest->toString() );

		$absoluteMove = $response->data;

		return $response->isFault ? $response->reason : $absoluteMove["tptz:ContinuousMoveResponse"];		
	}

	public function continuousMoveZoom( $profileToken, $zoom ) {
		$authFields = $this->_secure();

		$soapRequest = new SoapRequest();

		$soapRequest->authentication( $this->username, $authFields["digest"], $authFields["nonce"], $authFields["timestamp"] );

		$soapRequest->args( array( $profileToken, $zoom ) );

		$soapRequest->command( SoapRequest::$continuousMoveZoom );

		$response = Soap::send( $this->ptzUri, $soapRequest->toString() );

		$continuousMoveZoom = $response->data;

		return $response->isFault ? $response->reason : $continuousMoveZoom["tptz:ContinuousMoveZoomResponse"];				
	}

	public function stop( $profileToken, $pantilt = true, $zoom = true ) {
		$authFields = $this->_secure();

		$soapRequest = new SoapRequest();

		$soapRequest->authentication( $this->username, $authFields["digest"], $authFields["nonce"], $authFields["timestamp"] );

		$soapRequest->args( array( $profileToken, $pantilt ? "true" : "false", $zoom ? "true" : "false" ) );

		$soapRequest->command( SoapRequest::$stop );

		$response = Soap::send( $this->ptzUri, $soapRequest->toString() ); print_r( $response ); die;

		$continuousMoveZoom = $response->data;

		return $response->isFault ? $response->reason : $continuousMoveZoom["tptz:ContinuousMoveZoomResponse"];		
	}

	private function _secure()
	{
		$nonce = new Nonce();

		$timestamp = date( "Y-m-d\TH:i:s.000\Z", time() - $this->timeDelta );

		$data = pack( 'H*', $nonce->value ) . pack( 'a*', $timestamp ) . pack( 'a*', $this->passwd );

		$digest = base64_encode(pack('H*', sha1($data)));

		$result = array(
			"digest" => $digest,
			"nonce" => base64_encode( pack( "H*",$nonce->value ) ),
			"timestamp" => $timestamp
		);
		
		return $result;
	}

	private function _getOnvifVersion()
	{
		$currentMajor = 0;
		$currentMinor = 0;

		$device = $this->capabilities["tt:Device"]["tt:XAddr"];

		$media = $this->capabilities["tt:Media"]["tt:XAddr"];

		$event = $this->capabilities["tt:Events"]["tt:XAddr"];

		$ptz = null;

		try {
			$versionList = $this->capabilities["tt:Device"]["tt:System"]["tt:SupportedVersions"];

			foreach( $versionList as $version ):
				$major = intval( $version["tt:Major"] );

				if ( $major > $currentMajor ):
					$currentMajor = $major;
					$currentMinor = intval( $version["tt:Minor"] );
				endif;
			endforeach;
		} catch (\Exception $error) {
			$versionMap = $this->capabilities["tt:Device"]["tt:System"]["tt:SupportedVersions"];

			$currentMajor = intval($versionMap["tt:Major"]);
			$currentMinor = intval($versionMap["tt:Minor"]);
		}

		if ( array_key_exists( "tt:PTZ", $this->capabilities ) )
			$ptz = $this->capabilities["tt:PTZ"]["tt:XAddr"];

		return array(
			"major" => $currentMajor,
			"minor" => $currentMinor,
			"media" => $media,
			"device" => $device,
			"event" => $event,
			"ptz" => $ptz
		);
	}

	protected function _getActiveSources() {
		$sources = array();

		// NVT is a camera
		$sources['sourcetoken'] = $this->videoSources['@token'];

		$this->_getProfileData( $sources, $this->profiles );
		
		return $sources;
	}

	protected function _getProfileData( &$sources, $profiles ) {
		$inprofile = 0;

		for ( $y = 0; $y < count( $profiles ); $y++ ):
			if ( $profiles[$y]['tt:VideoSourceConfiguration']['tt:SourceToken'] == $sources['sourcetoken'] ):
				$sources[$inprofile]['profilename'] = $profiles[$y]['tt:Name'];
				$sources[$inprofile]['profiletoken'] = $profiles[$y]['@token'];

				if ( isset($profiles[$y]['tt:VideoEncoderConfiguration'])):
					$sources[$inprofile]['encodername'] = $profiles[$y]['tt:VideoEncoderConfiguration']['tt:Name'];
					$sources[$inprofile]['encoding'] = $profiles[$y]['tt:VideoEncoderConfiguration']['tt:Encoding'];
					$sources[$inprofile]['width'] = $profiles[$y]['tt:VideoEncoderConfiguration']['tt:Resolution']['tt:Width'];
					$sources[$inprofile]['height'] = $profiles[$y]['tt:VideoEncoderConfiguration']['tt:Resolution']['tt:Height'];
					$sources[$inprofile]['fps'] = $profiles[$y]['tt:VideoEncoderConfiguration']['tt:RateControl']['tt:RateControl'];
					$sources[$inprofile]['bitrate'] = $profiles[$y]['tt:VideoEncoderConfiguration']['tt:RateControl']['tt:BitrateLimit'];
				endif;

				if ( isset($profiles[$y]['tt:PTZConfiguration'])):
					$sources[$inprofile]['ptz']['name'] = $profiles[$y]['tt:PTZConfiguration']['tt:Name'];
					$sources[$inprofile]['ptz']['nodetoken'] = $profiles[$y]['tt:PTZConfiguration']['tt:NodeToken'];
				endif;

				$inprofile++;
			endif;
		endfor;
	}
}

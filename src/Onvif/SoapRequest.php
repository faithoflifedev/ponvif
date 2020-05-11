<?php 
namespace Onvif;

class SoapRequest {
  	public $security = array();
	  public $args = array();
	  public $command;

	static $getSystemDateAndTime = <<<STR
<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">
	<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
    	<GetSystemDateAndTime xmlns="http://www.onvif.org/ver10/device/wsdl"/>
  	</s:Body>
</s:Envelope>
STR;

	static $envelope = <<<STR
<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">
	<s:Header>
		<Security s:mustUnderstand="1" xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
			<UsernameToken>
				<Username>%s</Username>
				<Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest">%s</Password>
				<Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">%s</Nonce>
				<Created xmlns="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">%s</Created>
			</UsernameToken>
		</Security>
	</s:Header>
	<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">%s</s:Body>
</s:Envelope>
STR;

static $getCapabilities = <<<STR
<s:Envelope xmlns:s="http://www.w3.org/2003/05/soap-envelope">
	<s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
	<GetCapabilities xmlns="http://www.onvif.org/ver10/device/wsdl"><Category>All</Category></GetCapabilities>
  	</s:Body>
</s:Envelope>
STR;

	static $getVideoSources = '<GetVideoSources xmlns="http://www.onvif.org/ver10/media/wsdl"/>';

	static $getProfiles = '<GetProfiles xmlns="http://www.onvif.org/ver10/media/wsdl"/>';

	static $getServices = '<GetServices xmlns="http://www.onvif.org/ver10/device/wsdl"><IncludeCapability>false</IncludeCapability></GetServices>';

	static $getDeviceInformation = '<GetDeviceInformation xmlns="http://www.onvif.org/ver10/device/wsdl"/>';

	static $getStreamUri = '<GetStreamUri xmlns="http://www.onvif.org/ver10/media/wsdl"><StreamSetup><Stream xmlns="http://www.onvif.org/ver10/schema">%s</Stream><Transport xmlns="http://www.onvif.org/ver10/schema"><Protocol>%s</Protocol></Transport></StreamSetup><ProfileToken>%s</ProfileToken></GetStreamUri>';

	static $getSnapshotUri = '<GetSnapshotUri xmlns="http://www.onvif.org/ver10/media/wsdl"><ProfileToken>%s</ProfileToken></GetSnapshotUri>';

	static $getVideoEncoderConfigurations = '<GetVideoEncoderConfigurations xmlns="http://www.onvif.org/ver10/media/wsdl" />';

	static $getVideoEncoderConfigurationOptions = '<GetVideoEncoderConfigurationOptions xmlns="http://www.onvif.org/ver10/media/wsdl"><ProfileToken>%s</ProfileToken></GetVideoEncoderConfigurationOptions>';

	static $setVideoEncoderConfiguration = array(
		'RateControl' => '<tt:RateControl><tt:FrameRateLimit>%s</tt:FrameRateLimit><tt:EncodingInterval>%s</tt:EncodingInterval><tt:BitrateLimit>%s</tt:BitrateLimit></tt:RateControl>',
		'MPEG4' => '<tt:MPEG4><tt:GovLength>%s</tt:GovLength><tt:Mpeg4Profile>%s</tt:H264Profile></tt:MPEG4>',
		'H264' => '<tt:H264><tt:GovLength>%s</tt:GovLength><tt:H264Profile>%s</tt:H264Profile></tt:H264>'
	);

	static $getOSDs = '<GetOSDs xmlns="http://www.onvif.org/ver10/media/wsdl"></GetOSDs>';

	static $deleteOSD = '<DeleteOSD xmlns="http://www.onvif.org/ver10/media/wsdl"><OSDToken>%s</OSDToken></DeleteOSD>';

	static $getPresets = '<GetPresets xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>%s</ProfileToken></GetPresets>';

	static $gotoPreset = '<GotoPreset xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>%s</ProfileToken><PresetToken>%s</PresetToken><Speed><PanTilt x="%s" y="%s" xmlns="http://www.onvif.org/ver10/schema"/><Zoom x="%s" xmlns="http://www.onvif.org/ver10/schema"/></Speed></GotoPreset>';

	static $removePreset = '<RemovePreset xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>%s</ProfileToken><PresetToken>%s</PresetToken></RemovePreset>';

	static $setPreset = array(
		"specific" => '<SetPreset xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>%s</ProfileToken><PresetName>%s</PresetName><wsdl:PresetToken>%s</wsdl:PresetToken></SetPreset>',
		"general" => '<SetPreset xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>%s</ProfileToken><PresetName>%s</PresetName></SetPreset>'
	);

	static $relativeMove = array(
		"translation" => '<RelativeMove xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>%s</ProfileToken><Translation><Zoom x="%s" space="http://www.onvif.org/ver10/tptz/ZoomSpaces/TranslationGenericSpace" xmlns="http://www.onvif.org/ver10/schema"/></Translation></RelativeMove>',
		"speed" => '<RelativeMove xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>%s</ProfileToken><Speed><Zoom x="%s" space="http://www.onvif.org/ver10/tptz/ZoomSpaces/ZoomGenericSpeedSpace" xmlns="http://www.onvif.org/ver10/schema"/></Speed></RelativeMove>',
		"both" => '<RelativeMove xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>%s</ProfileToken><Translation><Zoom x="%s" space="http://www.onvif.org/ver10/tptz/ZoomSpaces/TranslationGenericSpace" xmlns="http://www.onvif.org/ver10/schema"/></Translation><Speed><Zoom x="%s" space="http://www.onvif.org/ver10/tptz/ZoomSpaces/ZoomGenericSpeedSpace" xmlns="http://www.onvif.org/ver10/schema"/></Speed></RelativeMove>'
	);

	static $absoluteMove = '<AbsoluteMove xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>%s</ProfileToken><Position><PanTilt x="%s" y="%s" space="http://www.onvif.org/ver10/tptz/PanTiltSpaces/PositionGenericSpace" xmlns="http://www.onvif.org/ver10/schema"/><Zoom x="%s" space="http://www.onvif.org/ver10/tptz/ZoomSpaces/PositionGenericSpace" xmlns="http://www.onvif.org/ver10/schema"/></Position></AbsoluteMove>';

	static $continuousMove = '<ContinuousMove xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>%s</ProfileToken><Velocity><PanTilt x="%s" y="%s" space="http://www.onvif.org/ver10/tptz/PanTiltSpaces/VelocityGenericSpace" xmlns="http://www.onvif.org/ver10/schema"/></Velocity></ContinuousMove>';

	static $continuousMoveZoom = '<ContinuousMove xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>%s</ProfileToken><Velocity><Zoom x="%s" space="http://www.onvif.org/ver10/tptz/ZoomSpaces/VelocityGenericSpace" xmlns="http://www.onvif.org/ver10/schema"/></Velocity></ContinuousMove>';

	static $stop = '<Stop xmlns="http://www.onvif.org/ver20/ptz/wsdl"><ProfileToken>%s</ProfileToken><PanTilt>%s</PanTilt><Zoom>%s</Zoom></Stop>';

	function authentication( $username, $digest, $nonce, $timestamp ) {
		$this->security[] = $username;
		$this->security[] = $digest;
		$this->security[] = $nonce;
		$this->security[] = $timestamp;
	}

	function command( $command ) {
		$this->command = $command;
	}

	function args( $args ) {
		$this->args = $args;
	}

	function toString() {
		$params = $this->security;

		if ( count( $this->args ) > 0 )
			$this->command = vsprintf( $this->command, $this->args );

		$params[] = $this->command;

		return vsprintf( SoapRequest::$envelope, $params );
	}
}
<?php
namespace Onvif;

class Soap {
	public static function send( $url, $post_string ) {
		$soap_do = curl_init();

		curl_setopt( $soap_do, CURLOPT_URL, $url );

		curl_setopt( $soap_do, CURLOPT_CONNECTTIMEOUT, 10 );
		curl_setopt( $soap_do, CURLOPT_TIMEOUT, 10 );
		curl_setopt( $soap_do, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $soap_do, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $soap_do, CURLOPT_SSL_VERIFYHOST, false );
		curl_setopt( $soap_do, CURLOPT_POST, true );
		curl_setopt( $soap_do, CURLOPT_POSTFIELDS, $post_string );
		curl_setopt( $soap_do, CURLOPT_HTTPHEADER, array( 'Content-Type: text/xml; charset=utf-8', 'Content-Length: ' . strlen( $post_string ) ) );

		$result = curl_exec( $soap_do );

		//curl_setopt($soap_do, CURLOPT_USERPWD, $user . ":" . $password); // HTTP authentication
		if ( $result === false ):
			throw new \Exception( curl_error( $soap_do ) );
		endif;

		curl_close( $soap_do );

		return new SoapResponse( $result );
	}
}
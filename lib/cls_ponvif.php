<?php 
class Soap {

}

class SoapResponse {

}

class SoapRequest {

}

class Nonce {
	public $hexList;
	public $nonce;
	public $randomBytes;

  function __construct( $size = 8) {
	$randomBytes = random_bytes( $size );

	$nonce = unpack('C*', $randomBytes );
  }
}
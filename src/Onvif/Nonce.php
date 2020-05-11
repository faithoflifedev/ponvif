<?php 
namespace Onvif;

class Nonce {
	public $value;

  function __construct( $size = 8) {
	$randomBytes = random_bytes( $size );

	$this->value = bin2hex( $randomBytes );
  }
}
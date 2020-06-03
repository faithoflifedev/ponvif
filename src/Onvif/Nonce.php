<?php

namespace Onvif;

class Nonce
{
	public $value;

	function __construct($size = 8)
	{
		try {
			$randomBytes = random_bytes($size);

			$this->value = bin2hex($randomBytes);
		} catch ( \Exception $e ) {
			throw new \Exception( $e );
		}
	}
}

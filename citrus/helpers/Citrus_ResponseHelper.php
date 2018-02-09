<?php
namespace Craft;

class Citrus_ResponseHelper
{
	const CODE_OK = 0;
	const CODE_ERROR_GENERAL = -1;
	const CODE_ERROR_CURL = -2;

	public function __construct($code, $message = '', $data = null)
	{
		$this->code = $code;
		$this->message = $message;
		$this->data = ($data !== null ? $data : array());
	}

	public function __toString()
	{
		return $this->code . ' ' . $this->message;
	}

	public function writeData($return = false)
	{
		$str = '';

		foreach ($data as $key => $value) {
			$str .= sprintf("%s: %s\r\n", $key, $value);
		}

		if ($return) {
			return $str;
		}

		echo $str;
	}
}

<?php
namespace Craft;

class Citrus_UriHelper
{
	public $path;
	public $locale;

	public function __construct($path, $locale) {
		$this->path = $path;
		$this->locale = $locale;
	}
}

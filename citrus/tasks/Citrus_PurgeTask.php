<?php
namespace Craft;

class Citrus_PurgeTask extends BaseTask
{
	private $_uris;
	private $_debug;
	private $_purge;

	public function __construct()
	{
		$this->_purge = new Citrus_PurgeHelper();
	}

	public function getDescription()
	{
		return Craft::t('Purging Varnish cache');
	}

	public function getTotalSteps()
	{
		$this->_uris = $this->getSettings()->uris;
		$this->_debug = $this->getSettings()->debug;

		return count($this->_uris);
	}

	public function runStep($step)
	{
		$this->_purge->purge(
			$this->_uris[$step],
			$this->_debug
		);

		// Sleep for .1 seconds
		usleep(100000);

		return true;
	}

	protected function defineSettings()
	{
		return array(
		  'uris' => AttributeType::Mixed,
		  'locale' => AttributeType::String,
		  'debug' => AttributeType::Bool,
		);
	}

}

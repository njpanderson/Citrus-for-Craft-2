<?php
namespace Craft;

class Citrus_PurgeTask extends BaseTask
{
	private $uris;
	private $debug;
	private $purge;

	public function __construct()
	{
		$this->purge = new Citrus_PurgeHelper();
	}

	public function getDescription()
	{
		return Craft::t('Purging Varnish cache');
	}

	public function getTotalSteps()
	{
		$this->uris = $this->getSettings()->uris;
		$this->debug = $this->getSettings()->debug;

		return count($this->uris);
	}

	public function runStep($step)
	{
		$this->purge->purge(
			$this->uris[$step],
			$this->debug
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

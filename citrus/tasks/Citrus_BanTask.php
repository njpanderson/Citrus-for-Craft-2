<?php
namespace Craft;

class Citrus_BanTask extends BaseTask
{
	private $bans;
	private $socket;
	private $ban;
	private $debug;

	public function __construct()
	{
		$this->ban = new Citrus_BanHelper();
	}

	public function getDescription()
	{
		return Craft::t('Banning from Varnish cache');
	}

	public function getTotalSteps()
	{
		$this->bans = $this->getSettings()->bans;
		$this->debug = $this->getSettings()->debug;

		return count($this->bans);
	}

	public function runStep($step)
	{
		require_once __DIR__ . '/../vendor/autoload.php';

		if (!isset($this->bans[$step]['full'])) {
			$this->bans[$step]['full'] = false;
		}

		if (!isset($this->bans[$step]['hostId'])) {
			$this->bans[$step]['hostId'] = null;
		}

		$this->ban->ban(
			$this->bans[$step],
			$this->debug
		);

		// Sleep for .1 seconds
		usleep(100000);

		return true;
	}

	protected function defineSettings()
	{
		return array(
		  'bans' => AttributeType::Mixed,
		  'debug' => AttributeType::Bool
		);
	}
}

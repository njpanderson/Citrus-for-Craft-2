<?php
namespace Craft;

class Varnishpurge_BanTask extends BaseTask
{
	private $_bans;
	private $_socket;
	private $_ban;
	private $_debug;

	public function __construct()
	{
		$this->_ban = new Varnishpurge_BanHelper();
	}

	public function getDescription()
	{
		return Craft::t('Banning from Varnish cache');
	}

	public function getTotalSteps()
	{
		$this->_bans = $this->getSettings()->bans;
		$this->_debug = $this->getSettings()->debug;

		return count($this->_bans);
	}

	public function runStep($step)
	{
		require_once __DIR__ . '/../vendor/autoload.php';

		if (!isset($this->_bans[$step]['full'])) {
			$this->_bans[$step]['full'] = false;
		}

		if (!isset($this->_bans[$step]['hostId'])) {
			$this->_bans[$step]['hostId'] = null;
		}

		$this->_ban->ban(
			$this->_bans[$step],
			$this->_debug
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

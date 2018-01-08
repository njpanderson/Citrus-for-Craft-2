<?php
namespace Craft;

use \njpanderson\VarnishConnect;

class Varnishpurge_BanTask extends BaseTask
{
	private $_bans;
	private $_socket;

	public function getDescription()
	{
		return Craft::t('Banning from Varnish cache');
	}

	public function getTotalSteps()
	{
		$bans = $this->getSettings()->bans;

		$this->_bans = array();
		$this->_bans = array_chunk($bans, 20);

		return count($this->_bans);
	}

	public function runStep($step)
	{
		require_once __DIR__ . '/../vendor/autoload.php';

		VarnishpurgePlugin::log(
			'Varnish ban task run step: ' . $step,
			LogLevel::Info,
			craft()->varnishpurge->getSetting('logAll')
		);

		if (craft()->varnishpurge->canDoAdminBans()) {
			$this->sendAdmin($this->_bans[$step]);
		} else {
			$this->sendHTTP($this->_bans[$step]);
		}

		// Sleep for .1 seconds
		usleep(100000);

		return true;
	}

	private function sendHTTP($bans)
	{
		$batch = \Guzzle\Batch\BatchBuilder::factory()
			->transferRequests(20)
			->notify(function(array $transferredItems) {
                if (count($transferredItems) > 0) {
                    VarnishpurgePlugin::log(
                        'Purged  '  . count($transferredItems) . ' item(s)',
                        LogLevel::Info,
                        craft()->varnishpurge->getSetting('logAll')
                    );
                }
            })
			->autoFlushAt(10)
			->bufferExceptions()
			->build();

		$client = new \Guzzle\Http\Client();
		$client->setDefaultOption('headers/Accept', '*/*');

		$banQueryHeader = craft()->varnishpurge->getSetting('banQueryHeader');
		$headers = array(
			'Host' => craft()->varnishpurge->getSetting('varnishHostName')
		);

		foreach ($bans as $query) {
			$banQuery = $this->prefixBan($query);

			// $headers[$banQueryHeader] = urlencode($banQuery);
			$headers[$banQueryHeader] = $banQuery;

			VarnishpurgePlugin::log(
				'Adding query to ban: ' . $banQuery,
				LogLevel::Info,
				craft()->varnishpurge->getSetting('logAll')
			);

			// Ban requests always go to / but with a header determining the ban query
			$request = $client->createRequest('BAN', craft()->request->hostInfo . '/', $headers);
			$batch->add($request);
		}

		$requests = $batch->flush();

		foreach ($batch->getExceptions() as $e) {
			VarnishpurgePlugin::log($e->getMessage(), LogLevel::Error);
		}

		$batch->clearExceptions();
	}

	private function sendAdmin($bans) {
		try {
			$this->_socket = new VarnishConnect\Socket(
				craft()->varnishpurge->getSetting('adminIP'),
				craft()->varnishpurge->getSetting('adminPort'),
				craft()->varnishpurge->getSetting('adminSecret')
			);

			$this->_socket->connect();

			foreach ($bans as $query) {
				$banQuery = $this->prefixBan($query);

				VarnishpurgePlugin::log(
					'Adding query to ban: ' . $banQuery,
					LogLevel::Info,
					craft()->varnishpurge->getSetting('logAll')
				);

				$result = $this->_socket->addBan($banQuery);

				if ($result !== true) {
					VarnishpurgePlugin::log('Ban error: ' . $result, LogLevel::Error);
				}
			}
		} catch(\Exception $e) {
			VarnishpurgePlugin::log(
				$e->getMessage(),
				LogLevel::Error
			);
		}
	}

	protected function defineSettings()
	{
		return array(
		  'bans' => AttributeType::Mixed
		);
	}

	private function prefixBan($query) {
		return craft()->varnishpurge->getSetting('banPrefix') . $query;
	}

}

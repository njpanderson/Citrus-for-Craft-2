<?php
namespace Craft;

class Varnishpurge_PurgeController extends BaseController
{
	use Varnishpurge_BaseHelper;

	private $numUris = 100;

	public function actionTest() {
		$settings = array(
			'urls' => $this->fillUris(
				'http://' . craft()->varnishpurge->getSetting('varnishHostName') . '/',
				$this->numUris
			),
			'debug' => true
		);

		$task = craft()->tasks->createTask('Varnishpurge_Purge', null, $settings);
		craft()->tasks->runTask($task);
	}

	private function fillUris($prefix, int $count = 1) {
		$result = array();

		for ($a = 0; $a < $count; $a += 1) {
			array_push($result, $prefix . '?n=' . $this->uuid());
		}

		return $result;
	}
}
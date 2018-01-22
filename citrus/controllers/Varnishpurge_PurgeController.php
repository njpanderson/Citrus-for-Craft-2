<?php
namespace Craft;

class Varnishpurge_PurgeController extends BaseController
{
	use Varnishpurge_BaseHelper;

	private $elementId, $numUris;

	function __construct()
	{
		$this->elementId = (int) craft()->request->getQuery('id');
		$this->numUris = (int) craft()->request->getQuery('n', 10);
	}

	public function actionTest()
	{
		if ($this->elementId) {
			$this->_testElement($this->elementId);
		} else {
			$this->_testUris($this->numUris);
		}
	}

	private function _testElement($id)
	{
		$element = craft()->elements->getElementById($id);

		echo "Purging element \"{$element->title}\" ({$element->id})<br/>\r\n";

		$tasks = craft()->varnishpurge->purgeElement($element, true, true);

		foreach ($tasks as $task) {
			craft()->tasks->runTask($task);
		}
	}

	private function _testUris($num)
	{
		$settings = array(
			'uris' => $this->_fillUris(
				'',
				$num
			),
			'debug' => true
		);

		$task = craft()->tasks->createTask('Varnishpurge_Purge', null, $settings);
		craft()->tasks->runTask($task);
	}

	private function _fillUris($prefix, int $count = 1) {
		$result = array();

		for ($a = 0; $a < $count; $a += 1) {
			array_push(
				$result,
				craft()->varnishpurge->makeVarnishUri($prefix . '?n=' . $this->uuid())
			);
		}

		return $result;
	}
}
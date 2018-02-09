<?php
namespace Craft;

class Citrus_PurgeController extends BaseController
{
	use Citrus_BaseHelper;

	private $elementId;
	private $numUris;

	public function __construct()
	{
		$this->elementId = (int) craft()->request->getQuery('id');
		$this->numUris = (int) craft()->request->getQuery('n', 10);
	}

	public function actionTest()
	{
		if ($this->elementId) {
			$this->testElementId($this->elementId);
		} else {
			$this->testUris($this->numUris);
		}
	}

	private function testElementId($id)
	{
		$element = craft()->elements->getElementById($id);

		echo "Purging element \"{$element->title}\" ({$element->id})<br/>\r\n";

		$tasks = craft()->citrus->purgeElement($element, true, true);

		foreach ($tasks as $task) {
			craft()->tasks->runTask($task);
		}
	}

	private function testUris($num)
	{
		$settings = array(
			'uris' => $this->fillUris(
				'',
				$num
			),
			'debug' => true
		);

		$task = craft()->tasks->createTask('Citrus_Purge', null, $settings);
		craft()->tasks->runTask($task);
	}

	private function fillUris($prefix, int $count = 1) {
		$result = array();

		for ($a = 0; $a < $count; $a += 1) {
			array_push(
				$result,
				craft()->citrus->makeVarnishUri($prefix . '?n=' . $this->uuid())
			);
		}

		return $result;
	}
}

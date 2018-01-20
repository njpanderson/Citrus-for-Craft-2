<?php
namespace Craft;

class Varnishpurge_BanController extends BaseController
{
	use Varnishpurge_BaseHelper;

	private $_query;
	private $_isFullQuery;
	private $_hostId;

	function __construct()
	{
		$this->_query = craft()->request->getQuery('q');
		$this->_hostId = craft()->request->getQuery('h');
		$this->_isFullQuery = craft()->request->getQuery('f', false);
	}

	public function actionTest()
	{
		if (!empty($this->_query)) {
			$bans = array(
				'query' => $this->_query,
				'full' => $this->_isFullQuery
			);
		} else {
			$bans = array(
				array('query' => '.*\.jpg', 'hostId' => $this->_hostId),
				array('query' => '.*\.gif', 'hostId' => $this->_hostId),
				array('query' => '^/testing', 'hostId' => $this->_hostId),
				array('query' => 'admin', 'hostId' => $this->_hostId),
				array('query' => '\?.+$', 'hostId' => $this->_hostId)
			);
		}

		$settings = array(
			'bans' => $bans,
			'debug' => true
		);

		$task = craft()->tasks->createTask('Varnishpurge_Ban', null, $settings);
		craft()->tasks->runTask($task);
	}
}
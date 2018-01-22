<?php
namespace Craft;

use \njpanderson\VarnishConnect;

class Citrus_BanController extends BaseController
{
	use Citrus_BaseHelper;

	private $_query;
	private $_isFullQuery;
	private $_hostId;
	private $_socket;

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

		$task = craft()->tasks->createTask('Citrus_Ban', null, $settings);
		craft()->tasks->runTask($task);
	}

	public function actionList()
	{
		$variables = array(
			'hostList' => array()
		);
		$hostId = $this->getPostWithDefault('host', null);

		foreach ($this->getVarnishHosts() as $id => $host) {
			if (
				($id === $hostId || $hostId === null) && $host['canDoAdminBans']
			) {
				$this->_socket = new VarnishConnect\Socket(
					$host['adminIP'],
					$host['adminPort'],
					$host['adminSecret']
				);

				try {
					$this->_socket->connect();
					$variables['hostList'][$id]['banList'] = $this->_socket->getBanList();
					$variables['hostList'][$id]['hostName'] = $host['hostName'];
					$variables['hostList'][$id]['id'] = $id;
				} catch (\Exception $e) {
					$variables['hostList'][$id]['adminError'] = $e->getMessage();
				}
			}
		}

		return $this->renderTemplate('citrus/fragments/banlist', $variables);
	}
}
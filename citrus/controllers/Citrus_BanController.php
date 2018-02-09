<?php
// phpcs:disable Generic.Commenting.Todo.Found
namespace Craft;

use \njpanderson\VarnishConnect;

class Citrus_BanController extends BaseController
{
    use Citrus_BaseHelper;

    private $query;
    private $isFullQuery;
    private $hostId;
    private $socket;

    public function __construct()
    {
        $this->query = craft()->request->getQuery('q');
        $this->hostId = craft()->request->getQuery('h');
        $this->isFullQuery = craft()->request->getQuery('f', false);
    }

    public function actionTest()
    {
        if (!empty($this->query)) {
            $bans = array(
                'query' => $this->query,
                'full' => $this->isFullQuery
            );
        } else {
            $bans = array(
                array('query' => '.*\.jpg', 'hostId' => $this->hostId),
                array('query' => '.*\.gif', 'hostId' => $this->hostId),
                array('query' => '^/testing', 'hostId' => $this->hostId),
                array('query' => 'admin', 'hostId' => $this->hostId),
                array('query' => '\?.+$', 'hostId' => $this->hostId)
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
            if (($id === $hostId || $hostId === null) && $host['canDoAdminBans']) {
                $this->socket = new VarnishConnect\Socket(
                    $host['adminIP'],
                    $host['adminPort'],
                    $host['adminSecret']
                );

                try {
                    $this->socket->connect();
                    $variables['hostList'][$id]['banList'] = $this->socket->getBanList();
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

<?php
namespace Craft;

class Varnishpurge_PurgeTask extends BaseTask
{
    private $_urls;
    private $_debug;

    public function getDescription()
    {
        return Craft::t('Purging Varnish cache');
    }

    public function getTotalSteps()
    {
        $this->_urls = $this->getSettings()->urls;
        $this->_debug = $this->getSettings()->debug;

        return count($this->_urls);
    }

    public function runStep($step)
    {
        $client = new \Guzzle\Http\Client();
        $client->setDefaultOption('headers/Accept', '*/*');
        $headers = array(
            'Host' => craft()->varnishpurge->getSetting('varnishHostName')
        );

        VarnishpurgePlugin::log(
            'Adding url to purge: ' . $this->_urls[$step],
            LogLevel::Info,
            craft()->varnishpurge->getSetting('logAll'),
            $this->_debug
        );

        $request = $client->createRequest('PURGE', $this->_urls[$step], $headers);

        try {
            $response = $request->send();
        } catch (\Guzzle\Http\Exception\BadResponseException $e) {
            VarnishpurgePlugin::log(
                'Error on PURGE URL "' . $e->getRequest()->getUrl() . '"' .
                    ' (' . $e->getResponse()->getStatusCode() . ' - ' .
                    $e->getResponse()->getReasonPhrase() . ')',
                LogLevel::Error,
                true,
                $this->_debug
            );
        }

        // Sleep for .1 seconds
        usleep(100000);

        return true;
    }

    protected function defineSettings()
    {
        return array(
          'urls' => AttributeType::Mixed,
          'locale' => AttributeType::String,
          'debug' => AttributeType::Bool,
        );
    }

}

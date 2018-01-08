<?php
namespace Craft;

class Varnishpurge_PurgeTask extends BaseTask
{
    private $_urls;
    private $_locale;
    private $_debug;

    public function getDescription()
    {
        return Craft::t('Purging Varnish cache');
    }

    public function getTotalSteps()
    {
        $urls = $this->getSettings()->urls;
        $this->_locale = $this->getSettings()->locale;
        $this->_debug = $this->getSettings()->debug;

        $this->_urls = array();
        $this->_urls = array_chunk($urls, 20);

        return count($this->_urls);
    }

    public function runStep($step)
    {
        VarnishpurgePlugin::log(
            'Varnish purge task run step: ' . $step,
            LogLevel::Info,
            craft()->varnishpurge->getSetting('logAll'),
            $this->_debug
        );

        $batch = \Guzzle\Batch\BatchBuilder::factory()
            ->transferRequests(20)
            ->autoFlushAt(10)
            ->notify(function(array $transferredItems) {
                if (count($transferredItems) > 0) {
                    VarnishpurgePlugin::log(
                        'Purged  '  . count($transferredItems) . ' item(s)',
                        LogLevel::Info,
                        craft()->varnishpurge->getSetting('logAll'),
                        $this->_debug
                    );
                }
            })
            ->bufferExceptions()
            ->build();

        $client = new \Guzzle\Http\Client();
        $client->setDefaultOption('headers/Accept', '*/*');
        $headers = array(
            'Host' => craft()->varnishpurge->getSetting('varnishHostName')
        );

        foreach ($this->_urls[$step] as $url) {
            VarnishpurgePlugin::log(
                'Adding url to purge: ' . $url,
                LogLevel::Info,
                craft()->varnishpurge->getSetting('logAll'),
                $this->_debug
            );

            $request = $client->createRequest('PURGE', $url, $headers);
            $batch->add($request);
        }

        $requests = $batch->flush();

        foreach ($batch->getExceptions() as $e) {
            VarnishpurgePlugin::log(
                $e->getMessage(),
                LogLevel::Error,
                true,
                $this->_debug
            );
        }

        $batch->clearExceptions();

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

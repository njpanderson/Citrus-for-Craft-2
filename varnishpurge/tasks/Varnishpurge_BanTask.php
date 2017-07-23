<?php
namespace Craft;


class Varnishpurge_BanTask extends BaseTask
{
    private $_bans;

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
        VarnishpurgePlugin::log(
            'Varnish ban task run step: ' . $step,
            LogLevel::Info,
            craft()->varnishpurge->getSetting('logAll')
        );

        $batch = \Guzzle\Batch\BatchBuilder::factory()
          ->transferRequests(20)
          ->bufferExceptions()
          ->build();

        $client = new \Guzzle\Http\Client();
        $client->setDefaultOption('headers/Accept', '*/*');

        $banQueryHeader = craft()->varnishpurge->getSetting('banQueryHeader');
        $headers = array();

        foreach ($this->_bans[$step] as $query) {
            $banQuery =
                'obj.http.host == ' . craft()->request->hostName .
                ' && obj.http.url ~ ' . $query;

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
            VarnishpurgePlugin::log('An exception occurred: ' . $e->getMessage(), LogLevel::Error);
        }

        $batch->clearExceptions();

        return true;
    }

    protected function defineSettings()
    {
        return array(
          'bans' => AttributeType::Mixed
        );
    }

}

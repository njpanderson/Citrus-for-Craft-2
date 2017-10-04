<?php
namespace Craft;

return array(
    'varnishUrl' => craft()->getSiteUrl(),
    'varnishHostName' => craft()->request->hostName,
    'purgeEnabled' => isset($_SERVER['HTTP_X_VARNISH']),
    'purgeRelated' => true,
    'logAll' => 0,
    'purgeUrlMap' => [],
    'bansSupported' => true,
    'banQueryHeader' => 'Ban-Query-Full',
);

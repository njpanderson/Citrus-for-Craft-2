<?php
namespace Craft;

return array(
    'varnishUrl' => craft()->getSiteUrl(),
    'varnishHostName' => craft()->request->hostName,
    'purgeEnabled' => isset($_SERVER['HTTP_X_VARNISH']),
    'purgeRelated' => true,
    'logAll' => 0,
    'purgeUrlMap' => [],
    'adminIP' => '',
    'adminPort' => '',
    'adminSecret' => '',
    'bansSupported' => true,
    'banPrefix' => 'req.http.host == ' . craft()->request->hostName . ' && req.url ~ ',
    'banQueryHeader' => 'Ban-Query-Full',
);

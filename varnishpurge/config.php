<?php
namespace Craft;

return array(
    'varnishHosts' => array(
        'public' => array(
            'url' => craft()->getSiteUrl(),
            'hostName' => craft()->request->hostName,
            'adminIP' => '',
            'adminPort' => '',
            'adminSecret' => ''
        )
    ),
    'varnishUrl' => '',
    'varnishHostName' => '',
    'purgeEnabled' => isset($_SERVER['HTTP_X_VARNISH']),
    'purgeRelated' => true,
    'logAll' => 0,
    'purgeUriMap' => [],
    'bansSupported' => true,
    'adminIP' => '',
    'adminPort' => '',
    'adminSecret' => '',
    'banQueryHeader' => 'Ban-Query-Full',
);

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
    'varnishHostName' => craft()->request->hostName,
    'purgeEnabled' => isset($_SERVER['HTTP_X_VARNISH']),
    'purgeRelated' => true,
    'logAll' => 0,
    'purgeUriMap' => [],
    'bansSupported' => false,
    'adminIP' => '',
    'adminPort' => '',
    'adminSecret' => '',
    'banQueryHeader' => 'Ban-Query-Full',
    'adminCookieName' => 'CitrusAdmin'
);

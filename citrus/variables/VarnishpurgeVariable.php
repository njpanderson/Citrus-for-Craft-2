<?php
namespace Craft;

class VarnishpurgeVariable extends BaseApplicationComponent
{
    use Varnishpurge_BaseHelper;

    /**
     * Gets the client IP, accounting for request being routed through Varnish (HTTP_X_FORWARDED_FOR header set)
     *
     * @return string
     */
    public function clientip()
    {
        $ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];

        // X-forwarded-for could be a comma-delimited list of all ip's the request was routed through.
        // The last ip in the list is expected to be the users originating ip.
        if (strpos($ip, ',') !== false) {
            $arr = explode(',', $ip);
            $ip = trim(array_pop($arr), " ");
        }

        return $ip;
    }

    /**
     * Tags a single entryId as belonging to a URI for later purging
     * @return void
     */
    public function tag(array $criteria = array())
    {
        $criteria = array_merge(array(
            'entryId' => 0,
            'uri' => craft()->elements->getElementUriForLocale(
                $criteria['entryId'],
                craft()->language
            ),
            'uriHash' => null
        ), $criteria);

        $criteria['entryId'] = (int) $criteria['entryId'];
        $criteria['uriHash'] = $this->hash($criteria['uri']);

        if ($criteria['entryId'] === 0) {
            throw new Exception('Entry ID in varnishpurge.tag cannot be zero or empty');
        }

        craft()->varnishpurge_uri->saveURIEntry(
            $criteria['uri'],
            $criteria['entryId'],
            craft()->language
        );

        return '';
    }

    public function deleteTag()
    {
        craft()->varnishpurge_uri->deleteURI(craft()->request->url);
    }

}
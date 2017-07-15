<?php
namespace Craft;

class Varnishpurge_uriService extends Varnishpurge_BaseHelper
{
    public function saveURIEntry($pageUri, $entryId)
    {
        $uriHash = $this->hash($pageUri);

        // Save URI record
        $uri = $this->getURIByURIHash(
            $uriHash
        );

        $uri->uri = $pageUri;
        $uri->uriHash = $uriHash;

        $this->saveURI($uri);

        // Save Entry record
        $entry = new Varnishpurge_EntryRecord();

        $entry->uriId = $uri->id;
        $entry->entryId = $entryId;

        craft()->varnishpurge_entry->saveEntry($entry);
    }

    public function getURIByURIHash($uriHash = '')
    {
        if (empty($uriHash)) {
            throw new Exception('$uriHash cannot be blank.');
        }

        if (($uri = Varnishpurge_uriRecord::model()->findByAttributes(array(
              'uriHash' => $uriHash
            ))) !== null) {
            return $uri;
        }

        return new Varnishpurge_uriRecord();
    }

    public function saveURI(
        Varnishpurge_uriRecord $uri
    )
    {
        $uri->save();
    }
}
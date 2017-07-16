<?php
namespace Craft;

class Varnishpurge_uriService extends Varnishpurge_BaseHelper
{
    public function saveURIEntry(string $pageUri, int $entryId)
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

    public function deleteURI(string $pageUri)
    {
        $uriHash = $this->hash($pageUri);

        // Save URI record
        $uri = $this->getURIByURIHash(
            $uriHash
        );

        if (!$uri->isNewRecord) {
            $uri->delete();
        }
    }

    public function getURI($id)
    {
        return Varnishpurge_uriRecord::model()->findAllByPk($id);
    }

    public function getURIByURIHash($uriHash = '')
    {
        if (empty($uriHash)) {
            throw new Exception('$uriHash cannot be blank.');
        }

        $uri = Varnishpurge_uriRecord::model()->findByAttributes(array(
          'uriHash' => $uriHash
        ));

        if ($uri !== null) {
            return $uri;
        }

        return new Varnishpurge_uriRecord();
    }

    public function getAllURIsByEntryId(int $entryId)
    {
        return Varnishpurge_uriRecord::model()->with(array(
            'entries' => array(
                'select' => false,
                'condition' => 'entryId = ' . $entryId
            )
        ))->findAll();
    }

    public function saveURI(
        Varnishpurge_uriRecord $uri
    )
    {
        $uri->save();
    }
}
<?php
namespace Craft;

class Varnishpurge_EntryService extends BaseApplicationComponent
{
    public function getAllByEntryId($entryId)
    {
        return Varnishpurge_EntryRecord::model()->findAllByAttributes(array(
          'entryId' => $entryId
        ));
    }

    public function saveEntry(
        Varnishpurge_EntryRecord $entry
    )
    {
        $entry->save();
    }
}
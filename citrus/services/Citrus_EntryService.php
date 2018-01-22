<?php
namespace Craft;

class Citrus_EntryService extends BaseApplicationComponent
{
    public function getAllByEntryId($entryId)
    {
        return Citrus_EntryRecord::model()->findAllByAttributes(array(
          'entryId' => $entryId
        ));
    }

    public function saveEntry(
        Citrus_EntryRecord $entry
    )
    {
        $entry->save();
    }
}
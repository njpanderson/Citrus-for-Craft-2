<?php
namespace Craft;

class Varnishpurge_EntryService extends BaseApplicationComponent
{
    public function saveEntry(
        Varnishpurge_EntryRecord $entry
    )
    {
        $entry->save();
    }
}
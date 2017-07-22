<?php
namespace Craft;

class Varnishpurge_EntryRecord extends BaseRecord
{
    public function getTableName()
    {
        return 'varnishpurge_entries';
    }

    protected function defineAttributes()
    {
        return array(
            'uriId' => AttributeType::Number,
            'entryId' => AttributeType::Number,
        );
    }

    public function defineIndexes()
    {
        return array(
            array('columns' => array('uriId', 'entryId'), 'unique' => true),
        );
    }
}
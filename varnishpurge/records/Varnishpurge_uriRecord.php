<?php
namespace Craft;

class Varnishpurge_uriRecord extends BaseRecord
{
    public function getTableName()
    {
        return 'varnishpurge_uris';
    }

    protected function defineAttributes()
    {
        return array(
            'uriHash' => AttributeType::String,
            'uri' => array(
                AttributeType::Uri,
                'column' => ColumnType::MediumText
            ),
        );
    }

    public function defineIndexes()
    {
        return array(
            array('columns' => array('uriHash')),
        );
    }

    public function defineRelations()
    {
        return array(
            'entries' => array(
                static::HAS_MANY,
                'Varnishpurge_EntryRecord',
                'uriId',
                'onDelete' => static::CASCADE
            ),
        );
    }
}
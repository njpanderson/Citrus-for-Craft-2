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
            'locale' => AttributeType::String
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
            'entries' => array(static::HAS_MANY, 'Varnishpurge_EntryRecord', 'uriId'),
        );
    }

    public function afterDelete()
    {
        foreach($this->entries as $entry) {
            $entry->delete();
        }

        return parent::afterDelete();
    }
}
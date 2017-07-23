<?php
namespace Craft;

class Varnishpurge_BindingsRecord extends BaseRecord
{
    const TYPE_PURGE = 'PURGE';
    const TYPE_BAN = 'BAN';

    public function getTableName()
    {
        return 'varnishpurge_bindings';
    }

    protected function defineAttributes()
    {
        return array(
            'sectionId' => AttributeType::Number,
            'typeId' => AttributeType::Number,
            'bindType' => [
                AttributeType::Enum,
                'values' => self::TYPE_PURGE . ',' . self::TYPE_BAN
            ],
            'query' => AttributeType::String
        );
    }

    public function defineIndexes()
    {
        return array(
            array('columns' => array('sectionId', 'typeId')),
        );
    }
}
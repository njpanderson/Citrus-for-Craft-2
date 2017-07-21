<?php
namespace Craft;

class Varnishpurge_BindingsRecord extends BaseRecord
{
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
                'values' => 'PURGE,BAN'
            ],
            'query' => AttributeType::String
        );
    }

    public function defineIndexes()
    {
        return array(
            array('columns' => array('sectionId', 'typeId'), 'unique' => true),
        );
    }
}
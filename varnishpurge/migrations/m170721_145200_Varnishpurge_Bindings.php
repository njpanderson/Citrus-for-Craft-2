<?php
namespace Craft;

class m170721_145200_Varnishpurge_Bindings extends BaseMigration
{
    public function safeUp()
    {
        // Create the craft_varnishpurge_bindings table
        craft()->db->createCommand()->createTable('varnishpurge_bindings', array(
            'sectionId' => array('maxLength' => 11, 'decimals' => 0, 'unsigned' => false, 'length' => 10, 'column' => 'integer'),
            'typeId'    => array('maxLength' => 11, 'decimals' => 0, 'unsigned' => false, 'length' => 10, 'column' => 'integer'),
            'bindType'  => array('values' => 'PURGE,BAN', 'column' => 'enum'),
            'query'     => array(),
        ), null, true);

        // Add indexes to craft_varnishpurge_bindings
        craft()->db->createCommand()->createIndex('varnishpurge_bindings', 'sectionId,typeId', true);

        return true;
    }
}
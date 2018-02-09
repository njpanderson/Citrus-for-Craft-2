<?php
namespace Craft;

class Citrus_BindingsRecord extends BaseRecord
{
	const TYPE_PURGE = 'PURGE';
	const TYPE_BAN = 'BAN';
	const TYPE_FULLBAN = 'FULLBAN';

	public function getTableName()
	{
		return 'citrus_bindings';
	}

	protected function defineAttributes()
	{
		return array(
			'sectionId' => AttributeType::Number,
			'typeId' => AttributeType::Number,
			'bindType' => [
				AttributeType::Enum,
				'values' => self::TYPE_PURGE . ',' . self::TYPE_BAN . ',' . self::TYPE_FULLBAN
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

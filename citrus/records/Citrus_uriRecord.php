<?php
namespace Craft;

class Citrus_uriRecord extends BaseRecord
{
	public function getTableName()
	{
		return 'citrus_uris';
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
			'entries' => array(static::HAS_MANY, 'Citrus_EntryRecord', 'uriId'),
		);
	}

	public function afterDelete()
	{
		foreach ($this->entries as $entry) {
			$entry->delete();
		}

		return parent::afterDelete();
	}
}

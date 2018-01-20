<?php
namespace Craft;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_pluginHandle_migrationName
 */
class m180113_173524_varnishpurge_LocaleSupport extends BaseMigration
{
	/**
	 * Any migration code in here is wrapped inside of a transaction.
	 *
	 * @return bool
	 */
	public function safeUp()
	{
		// Alter the craft_varnishpurge_uris table to include the locale column
		craft()->db->createCommand()->addColumn(
			'varnishpurge_uris',
			'locale',
			'string'
		);

		return true;
	}
}

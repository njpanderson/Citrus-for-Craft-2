<?php
namespace Craft;

class Citrus_PurgeCacheElementAction extends BaseElementAction
{
    public function getName()
    {
        return Craft::t('Purge cache');
    }

    public function isDestructive()
    {
        return false;
    }

    public function performAction(ElementCriteriaModel $criteria)
    {
			if (craft()->citrus->getSetting('purgeEnabled')) {
				$elements = $criteria->find();
				craft()->citrus->purgeElements($elements, false);
				$this->setMessage(Craft::t('Varnish cache was purged.'));
				return true;
			}
    }
}

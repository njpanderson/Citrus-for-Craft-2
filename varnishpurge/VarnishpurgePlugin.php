<?php
namespace Craft;

class VarnishpurgePlugin extends BasePlugin
{

	protected $_version = '0.4.0',
	  $_schemaVersion = '1.2.0',
	  $_name = 'Varnish Purge',
	  $_url = 'https://github.com/njpanderson/VarnishPurge-Craft',
	  $_releaseFeedUrl = 'https://raw.githubusercontent.com/njpanderson/VarnishPurge-Craft/master/releases.json',
	  $_documentationUrl = 'https://github.com/njpanderson/VarnishPurge-Craft/blob/master/README.md',
	  $_description = 'Varnish cache purging/management plugin',
	  $_developer = 'Neil Anderson',
	  $_developerUrl = 'http://neilinscotland.net/',
	  $_minVersion = '2.4';

	const URI_TAG = 0;
	const URI_ELEMENT = 1;
	const URI_BINDING = 2;

	public function getName()
	{
		return Craft::t($this->_name);
	}

	public function getUrl()
	{
		return $this->_url;
	}

	public function getVersion()
	{
		return $this->_version;
	}

	public function getDeveloper()
	{
		return $this->_developer;
	}

	public function getDeveloperUrl()
	{
		return $this->_developerUrl;
	}

	public function getDescription()
	{
		return $this->_description;
	}

	public function getDocumentationUrl()
	{
		return $this->_documentationUrl;
	}

	public function getSchemaVersion()
	{
		return $this->_schemaVersion;
	}

	public function getReleaseFeedUrl()
	{
		return $this->_releaseFeedUrl;
	}

	public function getCraftRequiredVersion()
	{
		return $this->_minVersion;
	}

	public function hasCpSection()
	{
		return true;
	}

	public function init()
	{
		parent::init();

		require __DIR__ . '/vendor/autoload.php';

		if (craft()->request->isCpRequest()) {
			craft()->templates->hook('varnishpurge.prepCpTemplate', array($this, 'prepCpTemplate'));
		}

		if (craft()->varnishpurge->getSetting('purgeEnabled')) {
			$purgeRelated = craft()->varnishpurge->getSetting('purgeRelated');

			craft()->on('elements.onSaveElement', function (Event $event) use ($purgeRelated) {
				// element saved
				craft()->varnishpurge->purgeElement($event->params['element'], $purgeRelated);
			});

			craft()->on('entries.onDeleteEntry', function (Event $event) use ($purgeRelated) {
				//entry deleted
				craft()->varnishpurge->purgeElement($event->params['entry'], $purgeRelated);
			});

			craft()->on('elements.onBeforePerformAction', function(Event $event) use ($purgeRelated) {
				//entry deleted via element action
				$action = $event->params['action']->classHandle;
				if ($action == 'Delete') {
					$elements = $event->params['criteria']->find();
					foreach ($elements as $element) {
						if ($element->elementType !== 'Entry') { return; }
						craft()->varnishpurge->purgeElement($element, $purgeRelated);
					}
				}
			});
		}
	}

	public function registerCpRoutes()
	{
		return array(
			'varnishpurge' => array('action' => 'Varnishpurge/index'),
			'varnishpurge/pages' => array('action' => 'Varnishpurge_Pages/index'),
			'varnishpurge/bindings' => array('action' => 'Varnishpurge_Bindings/index'),
			'varnishpurge/bindings/section' => array('action' => 'Varnishpurge_Bindings/section'),
			'varnishpurge/ban' => array('action' => 'Varnishpurge_Pages/index'),
			'varnishpurge/ban/list' => array('action' => 'Varnishpurge_Ban/list'),
			'varnishpurge/test/purge' => array('action' => 'Varnishpurge_Purge/test'),
			'varnishpurge/test/ban' => array('action' => 'Varnishpurge_Ban/test'),
			'varnishpurge/test/bindings' => array('action' => 'Varnishpurge_Bindings/test')
		);
	}

	public function prepCpTemplate(&$context)
	{
		$context['subnav'] = array();
		// $context['subnav']['pages'] = array('label' => 'Pages', 'url' => 'varnishpurge/pages');
		$context['subnav']['bindings'] = array('label' => 'Bindings', 'url' => 'varnishpurge/bindings');
		$context['subnav']['logs'] = array('label' => 'Logs', 'url' => 'utils/logs/varnishpurge.log');
	}

	public function addEntryActions()
	{
		$actions = array();

		if (craft()->varnishpurge->getSetting('purgeEnabled')) {
			$purgeAction = craft()->elements->getAction('Varnishpurge_PurgeCache');

			$purgeAction->setParams(array(
			  'label' => Craft::t('Purge cache'),
			));

			$actions[] = $purgeAction;
		}

		return $actions;
	}

	public function addCategoryActions()
	{
		$actions = array();

		if (craft()->varnishpurge->getSetting('purgeEnabled')) {
			$purgeAction = craft()->elements->getAction('Varnishpurge_PurgeCache');

			$purgeAction->setParams(array(
			  'label' => Craft::t('Purge cache'),
			));

			$actions[] = $purgeAction;
		}

		return $actions;
	}

	public static function log(
		$message,
		$level = LogLevel::Info,
		$override = false,
		$debug = false
	)
	{
		if ($debug) {
			// Also write to screen
			if ($level === LogLevel::Error) {
				echo '<span style="color: red; font-weight: bold;">' . $message . "</span><br/>\n";
			} else {
				echo $message . "<br/>\n";
			}
		}

		parent::log($message, $level, $override);
	}

}

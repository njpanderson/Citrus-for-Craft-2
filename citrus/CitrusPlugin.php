<?php
namespace Craft;

class CitrusPlugin extends BasePlugin
{
	protected $version = '0.4.1';
	protected $schemaVersion = '1.2.0';
	protected $name = 'Citrus';
	protected $url = 'https://github.com/njpanderson/Citrus';
	protected $releaseFeedUrl = 'https://raw.githubusercontent.com/njpanderson/Citrus/master/releases.json';
	protected $documentationUrl = 'https://github.com/njpanderson/Citrus/blob/master/README.md';
	protected $description = 'A Craft CMS plugin for purging and banning Varnish caches when elements are saved.';
	protected $developer = 'Neil Anderson';
	protected $developerUrl = 'http://neilinscotland.net/';
	protected $minVersion = '2.4';

	const URI_TAG = 0;
	const URI_ELEMENT = 1;
	const URI_BINDING = 2;

	public function getName()
	{
		return Craft::t($this->name);
	}

	public function getUrl()
	{
		return $this->url;
	}

	public function getVersion()
	{
		return $this->version;
	}

	public function getDeveloper()
	{
		return $this->developer;
	}

	public function getDeveloperUrl()
	{
		return $this->developerUrl;
	}

	public function getDescription()
	{
		return $this->description;
	}

	public function getDocumentationUrl()
	{
		return $this->documentationUrl;
	}

	public function getSchemaVersion()
	{
		return $this->schemaVersion;
	}

	public function getReleaseFeedUrl()
	{
		return $this->releaseFeedUrl;
	}

	public function getCraftRequiredVersion()
	{
		return $this->minVersion;
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
			craft()->templates->hook('citrus.prepCpTemplate', array($this, 'prepCpTemplate'));
		}

		if (craft()->citrus->getSetting('purgeEnabled')) {
			$purgeRelated = craft()->citrus->getSetting('purgeRelated');

			craft()->on('elements.onSaveElement', function (Event $event) use ($purgeRelated) {
				// element saved
				craft()->citrus->purgeElement($event->params['element'], $purgeRelated);
			});

			craft()->on('entries.onDeleteEntry', function (Event $event) use ($purgeRelated) {
				//entry deleted
				craft()->citrus->purgeElement($event->params['entry'], $purgeRelated);
			});

			craft()->on(
				'elements.onBeforePerformAction',
				function (Event $event) use ($purgeRelated) {
					//entry deleted via element action
					$action = $event->params['action']->classHandle;
					if ($action == 'Delete') {
						$elements = $event->params['criteria']->find();

						foreach ($elements as $element) {
							if ($element->elementType !== 'Entry') {
								return;
							}

							craft()->citrus->purgeElement($element, $purgeRelated);
						}
					}
				}
			);

			craft()->on('userSession.onBeforeLogin', function (Event $event) {
				$this->setCitrusCookie('1');
			});

			craft()->on('userSession.onLogout', function (Event $event) {
				$this->setCitrusCookie();
			});
		}
	}

	public function registerCpRoutes()
	{
		return array(
			'citrus' => array('action' => 'Citrus/index'),
			'citrus/pages' => array('action' => 'Citrus_Pages/index'),
			'citrus/bindings' => array('action' => 'Citrus_Bindings/index'),
			'citrus/bindings/section' => array('action' => 'Citrus_Bindings/section'),
			'citrus/ban' => array('action' => 'Citrus_Pages/index'),
			'citrus/ban/list' => array('action' => 'Citrus_Ban/list'),
			'citrus/test/purge' => array('action' => 'Citrus_Purge/test'),
			'citrus/test/ban' => array('action' => 'Citrus_Ban/test'),
			'citrus/test/bindings' => array('action' => 'Citrus_Bindings/test')
		);
	}

	public function prepCpTemplate(&$context)
	{
		$context['subnav'] = array();
		// $context['subnav']['pages'] = array('label' => 'Pages', 'url' => 'citrus/pages');
		$context['subnav']['bindings'] = array('label' => 'Bindings', 'url' => 'citrus/bindings');
		$context['subnav']['logs'] = array('label' => 'Logs', 'url' => 'utils/logs/citrus.log');
	}

	public function addEntryActions()
	{
		$actions = array();

		if (craft()->citrus->getSetting('purgeEnabled')) {
			$purgeAction = craft()->elements->getAction('Citrus_PurgeCache');

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

		if (craft()->citrus->getSetting('purgeEnabled')) {
			$purgeAction = craft()->elements->getAction('Citrus_PurgeCache');

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
	) {
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

	private function setCitrusCookie($value = '')
	{
		$cookieName = craft()->citrus->getSetting('adminCookieName');

		if ($cookieName === false) {
			return;
		}

		setcookie(
			$cookieName,
			$value,
			0,
			'/',
			null,
			craft()->request->isSecureConnection(),
			true
		);
	}
}

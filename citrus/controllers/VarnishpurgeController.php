<?php
namespace Craft;

use \njpanderson\VarnishConnect;

class VarnishpurgeController extends BaseController
{
	use Varnishpurge_BaseHelper;

	/**
	 * @var    bool|array Allows anonymous access to this controller's actions.
	 * @access protected
	 */
	protected $allowAnonymous = array('actionIndex');

	private $socket;

	/**
	 * Handle a request going to our plugin's index action URL, e.g.: actions/controllersExample
	 */
	public function actionIndex()
	{
		$bansSupported = craft()->varnishpurge->getSetting('bansSupported');

		$variables = $this->getTemplateStandardVars([
			'title' => 'Varnish Purge',
			'tabs' => [
				0 => [
					'label' => 'Purge',
					'url' => '#tab-purge'
				]
			],
			'bansSupported' => $bansSupported,
			'hosts' => $this->getVarnishHosts(),
			'adminHosts' => $this->getVarnishAdminHosts()
		]);

		if ($bansSupported) {
			array_push($variables['tabs'], [
				'label' => 'Ban',
				'url' => '#tab-ban'
			]);
		}

		if (craft()->request->getPost('purgeban_type')) {
			return $this->actionPurgeBan();
		}

		return $this->renderTemplate('varnishpurge/index', $variables);
	}

	public function actionPurgeBan()
	{
		$type = craft()->request->getPost('purgeban_type');
		$query = craft()->request->getPost('query');
		$hostId = $this->getPostWithDefault('host', null);

		HeaderHelper::setHeader('Content-type: application/json');

		if ($type === 'ban') {
			// Type is "ban" - send a ban query
			$responses = craft()->varnishpurge->banQuery($query, true, $hostId);
		} else {
			// Fall back to purge
			$responses = craft()->varnishpurge->purgeURI($query, $hostId);
		}

		if (craft()->request->isAjaxRequest) {
			echo json_encode(array(
				'query' => $query,
				'responses' => ($responses),
				'CSRF' => array(
					'name' => craft()->config->get('csrfTokenName'),
					'value' => craft()->request->getCsrfToken()
				)
			));
		} else {
			$userSessionService = craft()->userSession;
			$userSessionService->setNotice(Craft::t('Cache cleared.'));

			$this->redirect('varnishpurge');
		}
	}
}
<?php
namespace Craft;

class VarnishpurgeController extends BaseController
{

	/**
	 * @var    bool|array Allows anonymous access to this controller's actions.
	 * @access protected
	 */
	protected $allowAnonymous = array('actionIndex');

	/**
	 * Handle a request going to our plugin's index action URL, e.g.: actions/controllersExample
	 */
	public function actionIndex()
	{
		$variables = [
			'title' => 'Varnish Purge',
			'tabs' => [
				0 => [
					'label' => 'Purge',
					'url' => '#tab-purge'
				],
				1 => [
					'label' => 'Ban',
					'url' => '#tab-ban'
				]
			]
		];

		if (craft()->request->getPost('purgeban_type')) {
			return $this->actionPurgeBan();
		}

		return $this->renderTemplate('varnishpurge/index', $variables);
	}

	public function actionPurgeBan()
	{
		$type = craft()->request->getPost('purgeban_type');
		$query = craft()->request->getPost('query');
		HeaderHelper::setHeader('Content-type: application/json');

		if ($type === 'ban') {
			// Type is "ban" - send a ban query
			$response = craft()->varnishpurge->banQuery($query);
		} else {
			// Assume purge - add varnishUrl prefix to query
			$query = craft()->varnishpurge->getSetting('varnishUrl') . $query;
			$response = craft()->varnishpurge->purgeURI($query);
		}

		echo json_encode(array(
			'response' => array(
				'query' => $query,
				'message' => (
					$response === true ?
					ucfirst($type . ' query queued.') :
					ucfirst($type . ' query failed.')
				)
			),
			'CSRF' => array(
				'name' => craft()->config->get('csrfTokenName'),
				'value' => craft()->request->getCsrfToken()
			)
		));
	}

}
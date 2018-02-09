<?php
namespace Craft;

class Citrus_PagesController extends BaseController
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
		$variables = array(
			'title' => '🍊 Citrus - Pages'
		);

		return $this->renderTemplate('citrus/pages/index', $variables);
	}
}

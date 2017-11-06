<?php
namespace Craft;

class Varnishpurge_PagesController extends BaseController
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
            'title' => 'Varnish Purge - Pages'
        );

        return $this->renderTemplate('varnishpurge/pages/index', $variables);
    }

}
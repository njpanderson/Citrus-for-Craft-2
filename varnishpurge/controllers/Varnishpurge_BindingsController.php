<?php
namespace Craft;

class Varnishpurge_BindingsController extends BaseController
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
            'title' => 'Varnish Purge - Bindings',
            'sections' => []
        );

        return $this->renderTemplate('varnishpurge/bindings/index', $variables);
    }

    public function actionSection()
    {
        $variables = array(
            'title' => 'Varnish Purge - Bindings',
            'sectionname' => craft()->request->getSegment(4),
            'purgeTypes' => array('PURGE', 'BAN')
        );

        return $this->renderTemplate('varnishpurge/bindings/section', $variables);
    }
}
<?php
namespace Craft;

class Citrus_BindingsController extends BaseController
{

	const BINDINGS_TABLE_PREFIX = 'bindingsType_';

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
			'title' => '🍊 Citrus - Bindings',
			'sections' => craft()->citrus_bindings->getSections()
		);

		return $this->renderTemplate('citrus/bindings/index', $variables);
	}

	public function actionSection()
	{
		$bansSupported = craft()->citrus->getSetting('bansSupported');
		$bindTypes = array('PURGE' => 'PURGE');

		if ($bansSupported) {
			$bindTypes['BAN'] = 'BAN';
			$bindTypes['FULLBAN'] = 'FULLBAN';
		}

		$variables = $this->getTemplateStandardVars([
			'title' => 'Varnish Purge - Bindings',
			'sectionId' => craft()->request->getRequiredParam('sectionId'),
			'bindTypes' => $bindTypes,
			'tabs' => [],
			'bindings' => [],
			'fullPageForm' => true,
			'bansSupported' => $bansSupported
		]);

		if (!empty($variables['sectionId'])) {
			$variables['section'] = craft()->sections->getSectionById(
				$variables['sectionId']
			);

			if (isset($variables['section'])) {
				$variables['title'] .= ' - ' . $variables['section']->name;
				$variables['types'] = $variables['section']->getEntryTypes();

				// populate tabs with types
				foreach ($variables['types'] as $type) {
					$variables['tabs'][$type->id] = [
						'label' => $type->name,
						'url' => '#type' . $type->id
					];
					$variables['bindings'][$type->id] = [];
				}

				// populate rows with bindings
				$bindings = craft()->citrus_bindings->getBindings(
					$variables['sectionId']
				);

				foreach ($bindings as $binding) {
					$variables['bindings'][$binding->typeId][$binding->id] = [
						'bindType' => $binding->bindType,
						'query' => $binding->query
					];
				}
			}

			return $this->renderTemplate('citrus/bindings/section', $variables);
		} else {
			throw new HttpException(400, Craft::t('Param sectionId must not be empty.'));
		}
	}

	public function actionSave()
	{
		$userSessionService = craft()->userSession;
		$sectionId = (int) craft()->request->getRequiredPost('sectionId');
		$bindings = [];
		$saved = true;

		foreach (craft()->request->post as $key => $data) {
			if (($pos = strrpos($key, self::BINDINGS_TABLE_PREFIX)) !== false) {
				$typeId = (int) str_replace(self::BINDINGS_TABLE_PREFIX, '', $key);

				if ($typeId) {
					foreach ($data as $values) {
						$bindings[$typeId][] = [
							'bindType' => $values['bindType'],
							'query' => $values['query']
						];
					}
				}
			}
		}

		$cleared = craft()->citrus_bindings->clearBindings($sectionId);
		$saved = craft()->citrus_bindings->setBindings($sectionId, $bindings);

		if ($cleared && $saved) {
			$userSessionService->setNotice(Craft::t('Bindings saved.'));
		} else {
			$userSessionService->setError(Craft::t('Couldn’t save bindings.'));
		}

		$this->redirect('citrus/bindings');
	}

	public function actionTest()
	{
		$tasks = array();
		$element = craft()->elements->getElementById(
			(int) craft()->request->getQuery('id')
		);

		if ($element->getElementType() != ElementType::Entry) {
			throw(new Exception('Element tyoe is not an Entry. Only entries are supported.'));
		}

		$uris = craft()->citrus->getBindingQueries(
			$element->section->id,
			$element->type->id,
			Citrus_BindingsRecord::TYPE_PURGE
		);

		$bans = craft()->citrus->getBindingQueries(
			$element->section->id,
			$element->type->id,
			array(
				Citrus_BindingsRecord::TYPE_BAN,
				Citrus_BindingsRecord::TYPE_FULLBAN
			)
		);

		if (count($uris) > 0) {
			array_push(
				$tasks,
				craft()->tasks->createTask('Citrus_Purge', null, array(
					'uris' => $uris,
					'debug' => true
				))
			);
		}

		if (count($bans) > 0) {
			array_push(
				$tasks,
				craft()->tasks->createTask('Citrus_Ban', null, array(
					'bans' => $bans,
					'debug' => true
				))
			);
		}

		foreach ($tasks as $task) {
			craft()->tasks->runTask($task);
		}
	}
}
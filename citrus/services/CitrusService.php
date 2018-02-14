<?php
namespace Craft;

class CitrusService extends BaseApplicationComponent
{
	public $settings = array();

	/**
	 * Purge a single element. Just a wrapper for purgeElements().
	 *
	 * @param  mixed $event
	 */
	public function purgeElement($element, $purgeRelated = true, $debug = false)
	{
		return $this->purgeElements(array($element), $purgeRelated, $debug);
	}

	/**
	 * Purge an array of elements
	 *
	 * @param  mixed $event
	 */
	public function purgeElements($elements, $purgeRelated = true, $debug = false)
	{
		$tasks = array();

		if (count($elements) > 0) {
			// Assume that we only want to purge elements in one locale.
			// May not be the case if other thirdparty plugins sends elements.
			$locale = $elements[0]->locale;

			$uris = array();
			$bans = array();

			foreach ($elements as $element) {
				$uris = array_merge(
					$uris,
					$this->getElementUris($element, $locale, $purgeRelated)
				);

				if ($element->getElementType() == ElementType::Entry) {
					$uris = array_merge($uris, $this->getTagUris($element->id));

					$uris = array_merge($uris, $this->getBindingQueries(
						$element->section->id,
						$element->type->id,
						Citrus_BindingsRecord::TYPE_PURGE
					));

					$bans = array_merge($bans, $this->getBindingQueries(
						$element->section->id,
						$element->type->id,
						array(
							Citrus_BindingsRecord::TYPE_BAN,
							Citrus_BindingsRecord::TYPE_FULLBAN
						)
					));
				}
			}

			$uris = $this->uniqueUris($uris);

			// $uris = array_merge($uris, $this->getMappedUris($uris));

			if (count($uris) > 0) {
				array_push(
					$tasks,
					$this->makeTask('Citrus_Purge', array(
						'uris' => $uris,
						'debug' => $debug
					))
				);
			}

			if (count($bans) > 0) {
				array_push(
					$tasks,
					$this->makeTask('Citrus_Ban', array(
						'bans' => $bans,
						'debug' => $debug
					))
				);
			}
		}

		return $tasks;
	}

	public function purgeURI($uri, $hostId = null)
	{
		$purge = new Citrus_PurgeHelper;

		return $purge->purge($this->makeVarnishUri(
			$uri,
			null,
			CitrusPlugin::URI_ELEMENT,
			$hostId
		));
	}

	public function banQuery($query, $isFullQuery = false, $hostId = null)
	{
		$ban = new Citrus_BanHelper;

		return $ban->ban(array(
			'query' => $query,
			'full' => $isFullQuery,
			'hostId' => $hostId
		));
	}

	/**
	 * Gets URIs from section/entryType bindings
	 */
	public function getBindingQueries($sectionId, $typeId, $bindType = null)
	{
		$queries = array();
		$bindings = craft()->citrus_bindings->getBindings(
			$sectionId,
			$typeId,
			$bindType
		);

		foreach ($bindings as $binding) {
			$isCorrectType = (
				$binding->bindType === Citrus_BindingsRecord::TYPE_PURGE &&
				$bindType === Citrus_BindingsRecord::TYPE_PURGE
			);

			if ($isCorrectType) {
				// A single PURGE type is requested
				$queries[] = $this->makeVarnishUri(
					$binding->query,
					null,
					CitrusPlugin::URI_BINDING
				);
			} elseif (is_array($bindType)) {
				// Multiple bind types are requested
				$queries[] = array(
					'query' => $binding->query,
					'full' => ($binding->bindType === Citrus_BindingsRecord::TYPE_FULLBAN)
				);
			} else {
				// One bind type is requested (but not purge)
				$queries[] = $binding->query;
			}
		}

		return $queries;
	}

	public function makeVarnishUri(
		$uri,
		$locale = null,
		$type = CitrusPlugin::URI_ELEMENT,
		$hostId = null
	) {
		if ($locale instanceof LocaleModel) {
			$locale = $locale->id;
		}

		// Sanity check beginning slashes
		$uri = '/' . ltrim($uri, '/');

		return array(
			'uri' => $uri,
			'locale' => $locale,
			'host' => $hostId,
			'type' => $type
		);
	}

	/**
	 * Get URIs to purge from $element in $locale.
	 *
	 * Adds the URI of the $element, and all related elements
	 *
	 * @param $element
	 * @param $locale
	 * @return array
	 */
	private function getElementUris($element, $locale, $getRelated = true)
	{
		$uris = array();

		foreach (craft()->i18n->getEditableLocales() as $locale) {
			if ($element->uri) {
				$uris[] = $this->makeVarnishUri(
					craft()->elements->getElementUriForLocale($element->id, $locale),
					$locale
				);
			}

			// If this is a matrix block, get the uri of matrix block owner
			if ($element->getElementType() == ElementType::MatrixBlock) {
				if ($element->owner->uri != '') {
					$uris[] = $this->makeVarnishUri($element->owner->uri, $locale);
				}
			}

			// Get related elements and their uris
			if ($getRelated) {
				// get directly related entries
				$relatedEntries = $this->getRelatedElementsOfType($element, $locale, ElementType::Entry);
				foreach ($relatedEntries as $related) {
					if ($related->uri != '') {
						$uris[] = $this->makeVarnishUri($related->uri, $locale);
					}
				}
				unset($relatedEntries);

				// get directly related categories
				$relatedCategories = $this->getRelatedElementsOfType($element, $locale, ElementType::Category);
				foreach ($relatedCategories as $related) {
					if ($related->uri != '') {
						$uris[] = $this->makeVarnishUri($related->uri, $locale);
					}
				}
				unset($relatedCategories);

				// get directly related matrix block and its owners uri
				$relatedMatrixes = $this->getRelatedElementsOfType($element, $locale, ElementType::MatrixBlock);
				foreach ($relatedMatrixes as $relatedMatrixBlock) {
					if ($relatedMatrixBlock->owner->uri != '') {
						$uris[] = $this->makeVarnishUri($relatedMatrixBlock->owner->uri, $locale);
					}
				}
				unset($relatedMatrixes);

				// get directly related categories
				$relatedCategories = $this->getRelatedElementsOfType($element, $locale, ElementType::Category);
				foreach ($relatedCategories as $related) {
					if ($related->uri != '') {
						$uris[] = $this->makeVarnishUri($related->uri, $locale);
					}
				}
				unset($relatedCategories);

				// get directly Commerce products
				$relatedProducts = $this->getRelatedElementsOfType($element, $locale, 'Commerce_Product');
				foreach ($relatedProducts as $related) {
					if ($related->uri != '') {
						$uris[] = $this->makeVarnishUri($related->uri, $locale);
					}
				}
				unset($relatedProducts);
			}
		}

		foreach (craft()->plugins->call('CitrusTransformElementUris', [$element, $uris]) as $plugin => $pluginUris) {
			if ($pluginUris !== null) {
				$uris = $pluginUris;
			}
		}

		return $uris;
	}

	/**
	 * Gets URIs from tags attached to the front-end
	 */
	private function getTagUris($elementId)
	{
		$uris = array();
		$tagUris = craft()->citrus_uri->getAllURIsByEntryId($elementId);

		foreach ($tagUris as $tagUri) {
			$uris[] = $this->makeVarnishUri(
				$tagUri->uri,
				$tagUri->locale,
				CitrusPlugin::URI_TAG
			);
			// $tagUri->delete();
		}

		return $uris;
	}

	/**
	 * Gets elements of type $elementType related to $element in $locale
	 *
	 * @param $element
	 * @param $locale
	 * @param $elementType
	 * @return mixed
	 */
	private function getRelatedElementsOfType($element, $locale, $elementType)
	{
		$elementTypeExists = craft()->elements->getElementType($elementType);
		if (!$elementTypeExists) {
			return array();
		}

		$criteria = craft()->elements->getCriteria($elementType);
		$criteria->relatedTo = $element;
		$criteria->locale = $locale;
		return $criteria->find();
	}

	/**
	 *
	 *
	 * @param $uris
	 * @return array
	 */
	private function getMappedUris($uris)
	{
		$mappedUris = array();
		$map = $this->getSetting('purgeUriMap');

		if (is_array($map)) {
			foreach ($uris as $uri) {
				if (isset($map[$uri->uri])) {
					$mappedVal = $map[$uri->uri];

					if (is_array($mappedVal)) {
						$mappedUris = array_merge($mappedUris, $mappedVal);
					} else {
						array_push($mappedUris, $mappedVal);
					}
				}
			}
		}

		return $mappedUris;
	}

	/**
	 * Create task for purging urls
	 *
	 * @param $taskName
	 * @param $uris
	 * @param $locale
	 */
	private function makeTask($taskName, $settings = array())
	{
		// If there are any pending tasks, just append the paths to it
		$task = craft()->tasks->getNextPendingTask($taskName);

		if ($task && is_array($task->settings)) {
			$original_settings = $task->settings;

			switch ($taskName) {
				case 'Citrus_Purge':
					// Ensure 'uris' setting is an array
					if (!is_array($original_settings['uris'])) {
						$original_settings['uris'] = array($original_settings['uris']);
					}

					// Merge with existing URLs
					$original_settings['uris'] = array_merge(
						$original_settings['uris'],
						$settings['uris']
					);

					// Make sure there aren't any duplicate paths
					$original_settings['uris'] = $this->uniqueUris($original_settings['uris']);
					break;

				case 'Citrus_Ban':
					// Merge with existing bans
					$original_settings['bans'] = array_merge(
						$original_settings['bans'],
						$settings['bans']
					);

					// Make sure there aren't any duplicate bans
					$original_settings['bans'] = $this->uniqueUris($original_settings['bans']);
					break;
			}

			// Set the new settings and save the task
			$task->settings = $original_settings;
			craft()->tasks->saveTask($task, false);

			CitrusPlugin::log(
				'Appended task (' . $taskName . ')',
				LogLevel::Info,
				craft()->citrus->getSetting('logAll')
			);

			return $task;
		} else {
			CitrusPlugin::log(
				'Created task (' . $taskName . ')',
				LogLevel::Info,
				craft()->citrus->getSetting('logAll')
			);

			return craft()->tasks->createTask($taskName, null, $settings);
		}
	}

	/**
	 * Gets a plugin setting
	 *
	 * @param $name String Setting name
	 * @return mixed Setting value
	 * @author André Elvan
	 */
	public function getSetting($name)
	{
		return craft()->config->get($name, 'citrus');
	}

	private function uniqueUris($uris)
	{
		$found = array();
		$result = array();

		foreach ($uris as $uri) {
			if (!isset($uri['locale']) || empty($uri['locale'])) {
				$uri['locale'] = '<none>';
			}

			if (!isset($found[$uri['locale']])) {
				$found[$uri['locale']] = array();
			}

			if (isset($uri['uri']) && !in_array($uri['uri'], $found[$uri['locale']])) {
				array_push($found[$uri['locale']], $uri['uri']);
				array_push($result, $uri);
			}
		}

		return $result;
	}
}

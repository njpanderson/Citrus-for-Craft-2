<?php
namespace Craft;

class VarnishpurgeService extends BaseApplicationComponent
{

    var $settings = array();

    /**
     * Purge a single element. Just a wrapper for purgeElements().
     *
     * @param  mixed $event
     */
    public function purgeElement($element, $purgeRelated = true)
    {
        $this->purgeElements(array($element), $purgeRelated);
    }

    /**
     * Purge an array of elements
     *
     * @param  mixed $event
     */
    public function purgeElements($elements, $purgeRelated = true)
    {
        if (count($elements) > 0) {
            // Assume that we only want to purge elements in one locale.
            // May not be the case if other thirdparty plugins sends elements.
            $locale = $elements[0]->locale;

            $uris = array();
            $bans = array();

            foreach ($elements as $element) {
                $uris = array_merge($uris, $this->_getElementUris($element, $locale, $purgeRelated));

                if ($element->getElementType() == ElementType::Entry) {
                    $uris = array_merge($uris, $this->_getTagUris($element->id));
                    $elementSectionId = $element->section->id;
                    $elementTypeId = $element->type->id;

                    $uris = array_merge($uris, $this->_getBindingQueries(
                        $elementSectionId,
                        $elementTypeId,
                        Varnishpurge_BindingsRecord::TYPE_PURGE
                    ));

                    $bans = array_merge($bans, $this->_getBindingQueries(
                        $elementSectionId,
                        $elementTypeId,
                        Varnishpurge_BindingsRecord::TYPE_BAN
                    ));
                }
            }

            $urls = $this->_generateUrls($uris, $locale);
            $urls = array_merge($urls, $this->_getMappedUrls($urls));
            $urls = array_unique($urls);

            if (count($urls) > 0) {
                $this->_makeTask('Varnishpurge_Purge', array(
                    'urls' => $urls,
                    'locale' => $locale
                ));
            }

            if (count($bans) > 0) {
                $this->_makeTask('Varnishpurge_Ban', array(
                    'bans' => $bans
                ));
            }
        }
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
    private function _getElementUris($element, $locale, $getRelated = true)
    {
        $uris = array();

        // Get elements own uri
        if ($element->uri != '') {
            $uris[] = $element->uri;
        }

        // If this is a matrix block, get the uri of matrix block owner
        if ($element->getElementType() == ElementType::MatrixBlock) {
            if ($element->owner->uri != '') {
                $uris[] = $element->owner->uri;
            }
        }

        // Get related elements and their uris
        if ($getRelated) {

            // get directly related entries
            $relatedEntries = $this->_getRelatedElementsOfType($element, $locale, ElementType::Entry);
            foreach ($relatedEntries as $related) {
                if ($related->uri != '') {
                    $uris[] = $related->uri;
                }
            }
            unset($relatedEntries);

            // get directly related categories
            $relatedCategories = $this->_getRelatedElementsOfType($element, $locale, ElementType::Category);
            foreach ($relatedCategories as $related) {
                if ($related->uri != '') {
                    $uris[] = $related->uri;
                }
            }
            unset($relatedCategories);

            // get directly related matrix block and its owners uri
            $relatedMatrixes = $this->_getRelatedElementsOfType($element, $locale, ElementType::MatrixBlock);
            foreach ($relatedMatrixes as $relatedMatrixBlock) {
                if ($relatedMatrixBlock->owner->uri != '') {
                    $uris[] = $relatedMatrixBlock->owner->uri;
                }
            }
            unset($relatedMatrixes);

            // get directly related categories
            $relatedCategories = $this->_getRelatedElementsOfType($element, $locale, ElementType::Category);
            foreach ($relatedCategories as $related) {
                if ($related->uri != '') {
                    $uris[] = $related->uri;
                }
            }
            unset($relatedCategories);

            // get directly Commerce products
            $relatedProducts = $this->_getRelatedElementsOfType($element, $locale, 'Commerce_Product');
            foreach ($relatedProducts as $related) {
                if ($related->uri != '') {
                    $uris[] = $related->uri;
                }
            }
            unset($relatedProducts);
        }

        $uris = array_unique($uris);

        foreach (craft()->plugins->call('varnishPurgeTransformElementUris', [$element, $uris]) as $plugin => $pluginUris) {
            if ($pluginUris !== null) {
                $uris = $pluginUris;
            }
        }

        return $uris;
    }

    /**
     * Gets URIs from tags attached to the front-end
     */
    private function _getTagUris($elementId)
    {
        $uris = array();
        $tagUris = craft()->varnishpurge_uri->getAllURIsByEntryId($elementId);

        foreach ($tagUris as $tagUri) {
            $uris[] = $tagUri->uri;
            $tagUri->delete();
        }

        return $uris;
    }

    /**
     * Gets URIs from section/entryType bindings
     */
    private function _getBindingQueries($sectionId, $typeId, $bindType = null)
    {
        $uris = array();
        $bindings = craft()->varnishpurge_bindings->getBindings(
            $sectionId,
            $typeId,
            $bindType
        );

        foreach ($bindings as $binding) {
            $uris[] = $binding->query;
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
    private function _getRelatedElementsOfType($element, $locale, $elementType)
    {
        $elementTypeExists = craft()->elements->getElementType($elementType);
        if(!$elementTypeExists) { return array(); }

        $criteria = craft()->elements->getCriteria($elementType);
        $criteria->relatedTo = $element;
        $criteria->locale = $locale;
        return $criteria->find();
    }

    /**
     *
     *
     * @param $uris
     * @param $locale
     * @return array
     */
    private function _generateUrls($uris, $locale)
    {
        $urls = array();
        $varnishUrlSetting = craft()->varnishpurge->getSetting('varnishUrl');

        if (is_array($varnishUrlSetting)) {
            $varnishUrl = $varnishUrlSetting[$locale];
        } else {
            $varnishUrl = $varnishUrlSetting;
        }

        if (!$varnishUrl) {
            VarnishpurgePlugin::log('Varnish URL could not be found', LogLevel::Error);
            return $urls;
        }

        foreach ($uris as $uri) {
            $path = $uri == '__home__' ? '' : $uri;
            $url = rtrim($varnishUrl, '/') . '/' . trim($path, '/');

            if ($path && craft()->config->get('addTrailingSlashesToUrls')) {
                $url .= '/';
            }

            array_push($urls, $url);
        }

        return $urls;
    }

    /**
     *
     *
     * @param $uris
     * @return array
     */
    private function _getMappedUrls($urls)
    {
        $mappedUrls = array();
        $map = $this->getSetting('purgeUrlMap');

        if (is_array($map)) {
            foreach ($urls as $url) {
                if (isset($map[$url])) {
                    $mappedVal = $map[$url];

                    if (is_array($mappedVal)) {
                        $mappedUrls = array_merge($mappedUrls, $mappedVal);
                    } else {
                        array_push($mappedUrls, $mappedVal);
                    }
                }
            }
        }

        return $mappedUrls;
    }

    /**
     * Create task for purging urls
     *
     * @param $taskName
     * @param $uris
     * @param $locale
     */
    private function _makeTask($taskName, $settings = array())
    {
        VarnishpurgePlugin::log(
            'Creating task (' . $taskName . ')',
            LogLevel::Info,
            craft()->varnishpurge->getSetting('logAll')
        );

        // If there are any pending tasks, just append the paths to it
        $task = craft()->tasks->getNextPendingTask($taskName);

        if ($task && is_array($task->settings)) {
            $original_settings = $task->settings;

            switch ($taskName) {
            case 'Varnishpurge_Purge':
                // Ensure 'urls' setting is an array
                if (!is_array($original_settings['urls'])) {
                    $original_settings['urls'] = array($original_settings['urls']);
                }

                // Merge with existing URLs
                $original_settings['urls'] = array_merge(
                    $original_settings['urls'],
                    $settings['urls']
                );

                // Make sure there aren't any duplicate paths
                $original_settings['urls'] = array_unique($original_settings['urls']);
                break;

            case 'Varnishpurge_Ban':
                // Merge with existing bans
                $original_settings['bans'] = array_merge(
                    $original_settings['bans'],
                    $settings['bans']
                );

                // Make sure there aren't any duplicate bans
                $original_settings['bans'] = array_unique($original_settings['bans']);
                break;
            }

            // Set the new settings and save the task
            $task->settings = $original_settings;
            craft()->tasks->saveTask($task, false);
        } else {
            craft()->tasks->createTask($taskName, null, $settings);
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
        return craft()->config->get($name, 'varnishpurge');
    }

}

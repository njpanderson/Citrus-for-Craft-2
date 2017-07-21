<?php
namespace Craft;

class Varnishpurge_BindingsService extends BaseApplicationComponent
{
    /**
     * Returns the current CMS sections with binding counts
     */
    public function getSections()
    {
        $result = [];

        $sections = craft()->sections->getAllSections();
        $bindings = $this->getBindingCounts();

        foreach ($sections as $section) {
            $result[] = array(
                'bindings' => isset($bindings[$section->id]) ? $bindings[$section->id] : 0,
                'craftSection' => $section
            );
        }

        return $result;
    }

    /**
     * Returns the binding counts, grouped by section
     */
    public function getBindingCounts()
    {
        $result = [];
        $model = Varnishpurge_BindingsRecord::model();

        $sections = craft()->db->createCommand()
            ->select('sectionId, count(*) AS num')
            ->from($model->tableName())
            ->group('sectionId')
            ->queryAll();

        foreach ($sections as $section) {
            $result[$section['sectionId']] = $section['num'];
        }

        return $result;
    }
}

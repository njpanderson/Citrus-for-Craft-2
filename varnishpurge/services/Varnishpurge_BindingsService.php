<?php
namespace Craft;

class Varnishpurge_BindingsService extends BaseApplicationComponent
{
    /**
     * Returns the active BindingsRecord bindings for a section, grouped by type
     */
    public function getBindings(int $sectionId, int $typeId = 0, $bindType = '') {
        $attrs = [
            'sectionId' => $sectionId
        ];

        if ($typeId !== 0) $attrs['typeId'] = $typeId;
        if (!empty($bindType)) $attrs['bindType'] = $bindType;

        return Varnishpurge_BindingsRecord::model()->findAllByAttributes($attrs);
    }

    /**
     * Returns the current CMS sections with binding counts.
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
     * Returns the binding counts, grouped by section.
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

    /**
     * Clears the current bindings for a section.
     */
    public function clearBindings(int $sectionId) {
        Varnishpurge_BindingsRecord::model()->deleteAll(
            'sectionId = ?',
            [$sectionId]
        );

        return true;
    }

    /**
     * (Re)sets the active bindings for a section.
     */
    public function setBindings(int $sectionId, array $data = array()) {
        $success = true;

        foreach ($data as $entryType => $bindings) {
            foreach ($bindings as $binding) {
                $record = new Varnishpurge_BindingsRecord;
                $record->sectionId = $sectionId;
                $record->typeId = $entryType;
                $record->bindType = $binding['bindType'];
                $record->query = $binding['query'];
                $success = $record->save();

                if (!$success) {
                    // early return if a save failed
                    return $success;
                }
            }
        }

        return $success;
    }
}

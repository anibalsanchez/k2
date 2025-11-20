<?php

/*
 * @package     k2-jx-ready
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2025 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see         https://www.extly.com
 *
 * Based on K2 by JoomlaWorks Ltd. See: https://github.com/getk2/k2
 */

// no direct access
defined('_JEXEC') || die;

jimport('joomla.application.component.model');

Joomla\CMS\Table\Table::addIncludePath(JPATH_COMPONENT.'/tables');

class K2ModelExtraField extends K2Model
{
    public function getData()
    {
        $cid = K2Request::getVar('cid');
        $row = Joomla\CMS\Table\Table::getInstance('K2ExtraField', 'Table');
        $row->load($cid);

        return $row;
    }

    public function save()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $row = Joomla\CMS\Table\Table::getInstance('K2ExtraField', 'Table');
        if (!$row->bind(K2Request::getPost())) {
            $app->enqueueMessage($row->getError(), 'error');
            $app->redirect('index.php?option=com_k2&view=extrafields');
        }

        $isNewGroup = K2Request::getInt('isNew');

        if ($isNewGroup) {
            $group = Joomla\CMS\Table\Table::getInstance('K2ExtraFieldsGroup', 'Table');
            $group->set('name', K2Request::getVar('group'));
            $group->store();
            $row->group = $group->id;
        }

        if (!$row->id) {
            $row->ordering = $row->getNextOrder('`group` = '.(int) $row->group);
        }

        $objects = [];
        $values = K2Request::getVar('option_value', null, 'default', 'none', 4);
        $names = K2Request::getVar('option_name');
        $target = K2Request::getVar('option_target');
        $editor = K2Request::getVar('option_editor');
        $rows = K2Request::getVar('option_rows');
        $cols = K2Request::getVar('option_cols');
        $alias = K2Request::getWord('alias');
        $required = K2Request::getInt('required');
        $showNull = K2Request::getInt('showNull');
        $displayInFrontEnd = K2Request::getInt('displayInFrontEnd');

        if (JString::strtolower($alias) == 'this') {
            $alias = '';
        }

        $lastOptionId = 1;
        $counter = count($values);
        for ($i = 0; $i < $counter; $i++) {
            $object = new stdClass();
            $object->name = $names[$i];

            if ($row->type == 'select' || $row->type == 'multipleSelect' || $row->type == 'radio') {
                if (!empty($values[$i])) {
                    $object->value = $values[$i];
                    $lastOptionId = intval($values[$i]);
                } else {
                    $lastOptionId++;
                    $object->value = $lastOptionId;
                }
            } elseif ($row->type == 'link') {
                if (trim($values[$i]) !== '') {
                    if (str_starts_with($values[$i], 'http://') || str_starts_with($values[$i], 'https://') || str_starts_with($values[$i], '//') || str_starts_with($values[$i], '/') || str_starts_with($values[$i], 'mailto:') || str_starts_with($values[$i], 'tel:')) {
                        $values[$i] = $values[$i];
                    } else {
                        $values[$i] = 'http://'.$values[$i];
                    }
                }

                $object->value = trim($values[$i]);
            } elseif ($row->type == 'csv') {
                $file = K2Request::getVar('csv_file', null, 'FILES');
                $csvFile = $file['tmp_name'];
                if (!empty($csvFile) && Joomla\CMS\Filesystem\File::getExt($file['name']) == 'csv') {
                    $handle = @fopen($csvFile, 'r');
                    $csvData = [];
                    while (($data = fgetcsv($handle, 0)) !== false) {
                        $csvData[] = $data;
                    }

                    fclose($handle);
                    $object->value = $csvData;
                } else {
                    $object->value = json_decode($values[$i]);
                    if (K2Request::getBool('K2ResetCSV')) {
                        $object->value = null;
                    }
                }
            } elseif ($row->type == 'textarea') {
                $object->value = $values[$i];
                $object->editor = $editor[$i];
                $object->rows = $rows[$i];
                $object->cols = $cols[$i];
            } elseif ($row->type == 'image') {
                $object->value = $values[$i];
            } elseif ($row->type == 'header') {
                $object->value = K2Request::getString('name');
                $object->displayInFrontEnd = $displayInFrontEnd;
            } else {
                $object->value = $values[$i];
            }

            $object->target = $target[$i];
            $object->alias = $alias;
            $object->required = $required;
            $object->showNull = $showNull;
            $objects[] = $object;
        }

        $row->value = json_encode($objects);

        if (!$row->check()) {
            $app->enqueueMessage($row->getError(), 'error');
            $app->redirect('index.php?option=com_k2&view=extrafield&cid='.$row->id);
        }

        if (!$row->store()) {
            $app->enqueueMessage($row->getError(), 'error');
            $app->redirect('index.php?option=com_k2&view=extrafields');
        }

        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        if (!$params->get('disableCompactOrdering')) {
            $row->reorder('`group` = '.(int) $row->group);
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        switch (K2Request::getCmd('task')) {
            case 'apply':
                $msg = Joomla\CMS\Language\Text::_('K2_CHANGES_TO_EXTRA_FIELD_SAVED');
                $link = 'index.php?option=com_k2&view=extrafield&cid='.$row->id;
                break;
            case 'saveAndNew':
                $msg = Joomla\CMS\Language\Text::_('K2_EXTRA_FIELD_SAVED');
                $link = 'index.php?option=com_k2&view=extrafield';
                break;
            case 'save':
            default:
                $msg = Joomla\CMS\Language\Text::_('K2_EXTRA_FIELD_SAVED');
                $link = 'index.php?option=com_k2&view=extrafields';
                break;
        }

        $app->enqueueMessage($msg);
        $app->redirect($link);
    }

    public function getExtraFieldsByGroup($group)
    {
        $db = Joomla\CMS\Factory::getDbo();
        $group = (int) $group;
        $query = sprintf('SELECT * FROM #__k2_extra_fields WHERE `group`=%d AND published=1 ORDER BY ordering', $group);
        $db->setQuery($query);
        $rows = $db->loadObjectList();

        return $rows;
    }

    public function renderExtraField($extraField, $itemID = null)
    {
        $app = Joomla\CMS\Factory::getApplication();

        if (!is_null($itemID)) {
            $item = Joomla\CMS\Table\Table::getInstance('K2Item', 'Table');
            $item->load($itemID);
        }

        $defaultValues = json_decode($extraField->value);

        foreach ($defaultValues as $defaultValue) {
            $required = $defaultValue->required ?? 0;
            $showNull = $defaultValue->showNull ?? 0;

            if ($extraField->type == 'textfield' || $extraField->type == 'csv' || $extraField->type == 'labels' || $extraField->type == 'date' || $extraField->type == 'image') {
                $active = $defaultValue->value;
            } elseif ($extraField->type == 'textarea') {
                $active[0] = $defaultValue->value;
                $active[1] = $defaultValue->editor;
                $active[2] = (int) $defaultValue->rows ?: 10;
                $active[3] = (int) $defaultValue->cols ?: 40;
            } elseif ($extraField->type == 'link') {
                $active[0] = $defaultValue->name;
                $active[1] = $defaultValue->value;
                $active[2] = $defaultValue->target;
            } else {
                $active = '';
            }
        }

        if (!isset($active)) {
            $active = '';
        }

        if (isset($item)) {
            $currentValues = json_decode($item->extra_fields);
            if ($currentValues && count($currentValues)) {
                foreach ($currentValues as $currentValue) {
                    if ($currentValue->id == $extraField->id) {
                        if ($extraField->type == 'textarea') {
                            $active[0] = $currentValue->value;
                        } elseif ($extraField->type == 'date') {
                            $active = (is_array($currentValue->value)) ? $currentValue->value[0] : $currentValue->value;
                        } elseif ($extraField->type == 'header') {
                            continue;
                        } else {
                            $active = $currentValue->value;
                        }
                    }
                }
            }
        }

        $attributes = '';
        $arrayAttributes = [];
        if ($required) {
            $arrayAttributes['class'] = 'k2Required';
            $attributes .= 'class="k2Required"';
        }

        if ($showNull && in_array($extraField->type, [
            'select',
            'multipleSelect',
        ])) {
            $nullOption = new stdClass();
            $nullOption->name = Joomla\CMS\Language\Text::_('K2_PLEASE_SELECT');
            $nullOption->value = '';
            array_unshift($defaultValues, $nullOption);
        }

        if (in_array($extraField->type, [
            'textfield',
            'labels',
            'date',
            'image',
        ])) {
            $active = htmlspecialchars($active, ENT_QUOTES, 'UTF-8');
        }

        switch ($extraField->type) {
            case 'textfield':
                $output = '<input type="text" name="K2ExtraField_'.$extraField->id.'" id="K2ExtraField_'.$extraField->id.'" value="'.$active.'" '.$attributes.' />';
                break;

            case 'labels':
                $output = '<input type="text" name="K2ExtraField_'.$extraField->id.'" id="K2ExtraField_'.$extraField->id.'" value="'.$active.'" '.$attributes.' /> '.Joomla\CMS\Language\Text::_('K2_COMMA_SEPARATED_VALUES');
                break;

            case 'textarea':
                if ($active[1]) {
                    $attributes = $required ? 'class="k2ExtraFieldEditor k2Required"' : 'class="k2ExtraFieldEditor"';
                }

                $output = '<textarea name="K2ExtraField_'.$extraField->id.'" id="K2ExtraField_'.$extraField->id.'" rows="'.$active[2].'" cols="'.$active[3].'" '.$attributes.'>'.htmlspecialchars($active[0], ENT_QUOTES, 'UTF-8').'</textarea>';
                break;

            case 'select':
                $attributes .= ' id="K2ExtraField_'.$extraField->id.'"';
                $arrayAttributes['id'] = 'K2ExtraField_'.$extraField->id;
                $attrs = version_compare(JVERSION, '3.2', 'ge') ? $arrayAttributes : $attributes;
                $output = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $defaultValues, 'K2ExtraField_'.$extraField->id, $attrs, 'value', 'name', $active);
                break;

            case 'multipleSelect':

                $attributes .= ' id="K2ExtraField_'.$extraField->id.'" multiple="multiple"';
                $arrayAttributes['id'] = 'K2ExtraField_'.$extraField->id;
                $arrayAttributes['multiple'] = 'multiple';
                $attrs = version_compare(JVERSION, '3.2', 'ge') ? $arrayAttributes : $attributes;
                $output = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $defaultValues, 'K2ExtraField_'.$extraField->id.'[]', $attrs, 'value', 'name', $active);
                break;

            case 'radio':
                if (!$active && isset($defaultValues[0])) {
                    $active = $defaultValues[0]->value;
                }

                $attrs = version_compare(JVERSION, '3.2', 'ge') ? $arrayAttributes : $attributes;
                $output = Joomla\CMS\HTML\HTMLHelper::_('select.radiolist', $defaultValues, 'K2ExtraField_'.$extraField->id, $attrs, 'value', 'name', $active);
                break;

            case 'link':
                $output = '
                    <label>'.Joomla\CMS\Language\Text::_('K2_TEXT').'</label><input type="text" name="K2ExtraField_'.$extraField->id.'[]" value="'.htmlspecialchars($active[0], ENT_QUOTES, 'UTF-8').'" />
                    <label>'.Joomla\CMS\Language\Text::_('K2_URL').'</label><input type="text" name="K2ExtraField_'.$extraField->id.'[]" id="K2ExtraField_'.$extraField->id.'"  value="'.htmlspecialchars($active[1], ENT_QUOTES, 'UTF-8').'" '.$attributes.'/>
                    <label>'.Joomla\CMS\Language\Text::_('K2_OPEN_IN').'</label>';

                $targetOptions[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 'same', Joomla\CMS\Language\Text::_('K2_SAME_WINDOW'));
                $targetOptions[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 'new', Joomla\CMS\Language\Text::_('K2_NEW_WINDOW'));
                $targetOptions[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 'popup', Joomla\CMS\Language\Text::_('K2_CLASSIC_JAVASCRIPT_POPUP'));
                $targetOptions[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 'lightbox', Joomla\CMS\Language\Text::_('K2_LIGHTBOX_POPUP'));
                $output .= Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $targetOptions, 'K2ExtraField_'.$extraField->id.'[]', '', 'value', 'text', $active[2]);
                break;

            case 'csv':
                if ($active) {
                    $attributes = '';
                }

                $output = '<input type="file" id="K2ExtraField_'.$extraField->id.'" class="fileUpload k2Selector" name="K2ExtraField_'.$extraField->id.'[]" accept=".csv" '.$attributes.' />';
                if (is_array($active) && count($active)) {
                    $output .= '<input type="hidden" name="K2CSV_'.$extraField->id.'" value="'.htmlspecialchars(json_encode($active)).'" /><table class="k2ui-ef-csv">';
                    foreach ($active as $key => $row) {
                        $output .= '<tr>';
                        foreach ($row as $cell) {
                            $output .= ($key > 0) ? '<td>'.$cell.'</td>' : '<th>'.$cell.'</th>';
                        }

                        $output .= '</tr>';
                    }

                    $output .= '</table><hr /><div class="k2ui-ef-row"><input type="checkbox" name="K2ResetCSV_'.$extraField->id.'" /><label>'.Joomla\CMS\Language\Text::_('K2_DELETE_CSV_DATA').'</label></div>';
                }

                break;

            case 'date':
                $cssClass = $required ? 'k2Calendar k2Required' : 'k2Calendar';

                $output = '<input class="'.$cssClass.'" type="text" data-k2-datetimepicker="{allowInput:true}" name="K2ExtraField_'.$extraField->id.'" id="K2ExtraField_'.$extraField->id.'" value="'.$active.'" />';
                break;
            case 'image':
                $output = '<input type="text" name="K2ExtraField_'.$extraField->id.'" id="K2ExtraField_'.$extraField->id.'" value="'.$active.'" '.$attributes.' /><a class="k2app-ef-image-button k2Button" href="'.Joomla\CMS\Uri\Uri::base(true).'/index.php?option=com_k2&view=media&type=image&tmpl=component&fieldID=K2ExtraField_'.$extraField->id.'">'.Joomla\CMS\Language\Text::_('K2_SELECT').'</a>';
                break;
            case 'header':
                $output = '';
                break;
        }

        return $output;
    }

    public function getExtraFieldInfo($fieldID)
    {
        $db = Joomla\CMS\Factory::getDbo();
        $fieldID = (int) $fieldID;
        $query = 'SELECT * FROM #__k2_extra_fields WHERE published=1 AND id = '.$fieldID;
        $db->setQuery($query, 0, 1);
        $row = $db->loadObject();

        return $row;
    }

    public function getSearchValue($id, $currentValue)
    {
        $row = Joomla\CMS\Table\Table::getInstance('K2ExtraField', 'Table');
        $row->load($id);

        $jsonObject = json_decode($row->value);

        $value = '';
        if ($row->type == 'textfield' || $row->type == 'textarea') {
            $value = $currentValue;
        } elseif ($row->type == 'multipleSelect') {
            foreach ($jsonObject as $option) {
                if (in_array($option->value, $currentValue)) {
                    $value .= $option->name.' ';
                }
            }
        } elseif ($row->type == 'link') {
            $value .= $currentValue[0].' ';
            $value .= $currentValue[1].' ';
        } elseif ($row->type == 'labels') {
            $parts = explode(',', $currentValue);
            $value .= implode(' ', $parts);
        } else {
            foreach ($jsonObject as $option) {
                if ($option->value == $currentValue) {
                    $value .= $option->name;
                }
            }
        }

        return $value;
    }
}

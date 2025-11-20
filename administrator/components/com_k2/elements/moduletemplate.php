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
defined('_JEXEC') or die;

require_once JPATH_ADMINISTRATOR.'/components/com_k2/elements/base.php';

class K2ElementModuleTemplate extends K2Element
{
    public function fetchElement($name, $value, &$node, $control_name)
    {
        jimport('joomla.filesystem.folder');
        if (K2_JVERSION != '15') {
            $moduleName = $node->attributes()->modulename;
        } else {
            $moduleName = $node->_attributes['modulename'];
        }
        $moduleTemplatesPath = JPATH_SITE.'/modules/'.$moduleName.'/tmpl';
        $moduleTemplatesFolders = JFolder::folders($moduleTemplatesPath);

        $db = JFactory::getDbo();
        if (K2_JVERSION != '15') {
            $query = 'SELECT template FROM #__template_styles WHERE client_id = 0 AND home = 1';
        } else {
            $query = 'SELECT template FROM #__templates_menu WHERE client_id = 0 AND menuid = 0';
        }
        $db->setQuery($query);
        $defaultemplate = $db->loadResult();
        $templatePath = JPATH_SITE.'/templates/'.$defaultemplate.'/html/'.$moduleName;

        if (JFolder::exists($templatePath)) {
            $templateFolders = JFolder::folders($templatePath);
            $folders = @array_merge($templateFolders, $moduleTemplatesFolders);
            $folders = @array_unique($folders);
        } else {
            $folders = $moduleTemplatesFolders;
        }

        $exclude = 'Default';
        $options = [];

        foreach ($folders as $folder) {
            if (preg_match(chr(1).$exclude.chr(1), $folder)) {
                continue;
            }
            $options[] = JHTML::_('select.option', $folder, $folder);
        }

        array_unshift($options, JHTML::_('select.option', 'Default', '-- '.JText::_('K2_USE_DEFAULT').' --'));

        if (K2_JVERSION != '15') {
            $fieldName = $name;
        } else {
            $fieldName = $control_name.'['.$name.']';
        }

        return JHTML::_('select.genericlist', $options, $fieldName, 'class="inputbox"', 'value', 'text', $value);
    }
}

class JFormFieldModuleTemplate extends K2ElementModuleTemplate
{
    public $type = 'moduletemplate';
}

class JElementModuleTemplate extends K2ElementModuleTemplate
{
    public $_name = 'moduletemplate';
}

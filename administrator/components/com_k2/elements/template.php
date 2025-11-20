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

require_once JPATH_ADMINISTRATOR.'/components/com_k2/elements/base.php';

class K2ElementTemplate extends K2Element
{
    public function fetchElement($name, $value, &$node, $control_name)
    {
        jimport('joomla.filesystem.folder');
        $app = Joomla\CMS\Factory::getApplication();
        $fieldName = (K2_JVERSION != '15') ? $name : $control_name.'['.$name.']';
        $componentPath = JPATH_SITE.'/components/com_k2/templates';
        $componentFolders = Joomla\CMS\Filesystem\Folder::folders($componentPath);
        $db = Joomla\CMS\Factory::getDbo();
        if (K2_JVERSION != '15') {
            $query = 'SELECT template FROM #__template_styles WHERE client_id = 0 AND home = 1';
        } else {
            $query = 'SELECT template FROM #__templates_menu WHERE client_id = 0 AND menuid = 0';
        }

        $db->setQuery($query);
        $defaultemplate = $db->loadResult();

        if (Joomla\CMS\Filesystem\Folder::exists(JPATH_SITE.'/templates/'.$defaultemplate.'/html/com_k2/templates')) {
            $templatePath = JPATH_SITE.'/templates/'.$defaultemplate.'/html/com_k2/templates';
        } else {
            $templatePath = JPATH_SITE.'/templates/'.$defaultemplate.'/html/com_k2';
        }

        if (Joomla\CMS\Filesystem\Folder::exists($templatePath)) {
            $templateFolders = Joomla\CMS\Filesystem\Folder::folders($templatePath);
            $folders = @array_merge($templateFolders, $componentFolders);
            $folders = @array_unique($folders);
        } else {
            $folders = $componentFolders;
        }

        $exclude = 'default';
        $options = [];
        foreach ($folders as $folder) {
            if (preg_match(chr(1).$exclude.chr(1), $folder)) {
                continue;
            }

            $options[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', $folder, $folder);
        }

        array_unshift($options, Joomla\CMS\HTML\HTMLHelper::_('select.option', '', '-- '.Joomla\CMS\Language\Text::_('K2_USE_DEFAULT').' --'));

        return Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $options, $fieldName, 'class="inputbox"', 'value', 'text', $value);
    }
}

class JFormFieldTemplate extends K2ElementTemplate
{
    public $type = 'template';
}

class JElementTemplate extends K2ElementTemplate
{
    public $_name = 'template';
}

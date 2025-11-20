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

jimport('joomla.application.component.view');

class K2ViewExtraFieldsGroup extends K2View
{
    public function display($tpl = null)
    {
        $model = $this->getModel();
        $extraFieldsGroup = $model->getExtraFieldsGroup();
        Joomla\CMS\Filter\OutputFilter::objectHTMLSafe($extraFieldsGroup);
        $this->assignRef('row', $extraFieldsGroup);

        // Disable Joomla menu
        JRequest::setVar('hidemainmenu', 1);

        // Toolbar
        $title = (JRequest::getInt('cid')) ? Joomla\CMS\Language\Text::_('K2_EDIT_EXTRA_FIELD_GROUP') : Joomla\CMS\Language\Text::_('K2_ADD_EXTRA_FIELD_GROUP');
        Joomla\CMS\Toolbar\ToolbarHelper::title($title, 'k2.png');

        Joomla\CMS\Toolbar\ToolbarHelper::apply();
        Joomla\CMS\Toolbar\ToolbarHelper::save();
        $saveNewIcon = version_compare(JVERSION, '2.5.0', 'ge') ? 'save-new.png' : 'save.png';
        Joomla\CMS\Toolbar\ToolbarHelper::custom('saveAndNew', $saveNewIcon, 'save_f2.png', 'K2_SAVE_AND_NEW', false);
        Joomla\CMS\Toolbar\ToolbarHelper::cancel();

        // JS
        $document = Joomla\CMS\Factory::getDocument();
        $document->addScriptDeclaration("
            Joomla.submitbutton = function(pressbutton) {
                if (pressbutton == 'cancel') {
                    submitform(pressbutton);
                    return;
                }
                if (\$K2.trim(\$K2('#name').val()) == '') {
                    alert('".Joomla\CMS\Language\Text::_('K2_GROUP_NAME_CANNOT_BE_EMPTY', true)."');
                } else {
                    submitform(pressbutton);
                }
            };
        ");

        parent::display($tpl);
    }
}

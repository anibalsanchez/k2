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

class K2ViewUserGroup extends K2View
{
    public function display($tpl = null)
    {
        Joomla\CMS\HTML\HTMLHelper::_('behavior.tooltip');

        $model = $this->getModel();
        $userGroup = $model->getData();
        if (K2_JVERSION == '15') {
            Joomla\CMS\Filter\OutputFilter::objectHTMLSafe($userGroup);
        } else {
            Joomla\CMS\Filter\OutputFilter::objectHTMLSafe($userGroup, ENT_QUOTES, 'permissions');
        }

        $this->assignRef('row', $userGroup);

        if (K2_JVERSION == '15') {
            $form = new JParameter('', JPATH_COMPONENT.'/models/usergroup.xml');
            $form->loadINI($userGroup->permissions);
            $appliedCategories = $form->get('categories');
            $inheritance = $form->get('inheritance');
        } else {
            jimport('joomla.form.form');
            $form = Joomla\CMS\Form\Form::getInstance('permissions', JPATH_COMPONENT_ADMINISTRATOR.'/models/usergroup.xml');
            $values = ['params' => json_decode($userGroup->permissions)];
            $form->bind($values);
            $inheritance = $values['params']->inheritance ?? 0;
            $appliedCategories = $values['params']->categories ?? '';
        }

        $this->assignRef('form', $form);
        $this->assignRef('categories', $appliedCategories);

        $lists = [];
        require_once JPATH_ADMINISTRATOR.'/components/com_k2/models/categories.php';
        $categoriesModel = K2Model::getInstance('Categories', 'K2Model');
        $categories = $categoriesModel->categoriesTree(null, true);
        $lists['categories'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $categories, 'params[categories][]', 'multiple="multiple" size="15"', 'value', 'text', $appliedCategories);
        $lists['inheritance'] = Joomla\CMS\HTML\HTMLHelper::_('select.booleanlist', 'params[inheritance]', null, $inheritance);
        $this->assignRef('lists', $lists);

        // Disable Joomla menu
        K2Request::setVar('hidemainmenu', 1);

        // Toolbar
        $title = (K2Request::getInt('cid')) ? Joomla\CMS\Language\Text::_('K2_EDIT_USER_GROUP') : Joomla\CMS\Language\Text::_('K2_ADD_USER_GROUP');
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

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

class K2ViewUser extends K2View
{
    public function display($tpl = null)
    {
        $model = $this->getModel();
        $user = $model->getData();
        if (K2_JVERSION == '15') {
            Joomla\CMS\Filter\OutputFilter::objectHTMLSafe($user);
        } else {
            Joomla\CMS\Filter\OutputFilter::objectHTMLSafe($user, ENT_QUOTES, ['params', 'plugins']);
        }

        $joomlaUser = Joomla\CMS\User\User::getInstance(K2Request::getInt('cid'));

        $user->name = $joomlaUser->name;
        $user->userID = $joomlaUser->id;
        $this->assignRef('row', $user);

        $wysiwyg = Joomla\CMS\Factory::getEditor();
        $editor = $wysiwyg->display('description', $user->description, '480px', '250px', '', '', false);
        $this->assignRef('editor', $editor);

        $lists = [];
        $genderOptions[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 'n', Joomla\CMS\Language\Text::_('K2_NOT_SPECIFIED'));
        $genderOptions[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 'm', Joomla\CMS\Language\Text::_('K2_MALE'));
        $genderOptions[] = Joomla\CMS\HTML\HTMLHelper::_('select.option', 'f', Joomla\CMS\Language\Text::_('K2_FEMALE'));
        $lists['gender'] = Joomla\CMS\HTML\HTMLHelper::_('select.radiolist', $genderOptions, 'gender', '', 'value', 'text', $user->gender);

        $userGroupOptions = $model->getUserGroups();
        $lists['userGroup'] = Joomla\CMS\HTML\HTMLHelper::_('select.genericlist', $userGroupOptions, 'group', 'class="inputbox"', 'id', 'name', $user->group);

        $this->assignRef('lists', $lists);

        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        $this->assignRef('params', $params);

        // Plugins
        Joomla\CMS\Plugin\PluginHelper::importPlugin('k2');
        $dispatcher = JDispatcher::getInstance();
        $K2Plugins = $dispatcher->trigger('onRenderAdminForm', [&$user, 'user']);
        $this->assignRef('K2Plugins', $K2Plugins);

        // Disable Joomla menu
        K2Request::setVar('hidemainmenu', 1);

        // Toolbar
        $toolbar = Joomla\CMS\Toolbar\Toolbar::getInstance('toolbar');
        Joomla\CMS\Toolbar\ToolbarHelper::title(Joomla\CMS\Language\Text::_('K2_USER'), 'k2.png');

        Joomla\CMS\Toolbar\ToolbarHelper::apply();
        Joomla\CMS\Toolbar\ToolbarHelper::save();
        Joomla\CMS\Toolbar\ToolbarHelper::cancel();

        if (K2_JVERSION != '15') {
            $editJoomlaUserButtonUrl = Joomla\CMS\Uri\Uri::base().'index.php?option=com_users&view=user&task=user.edit&id='.$user->userID;
        } else {
            $editJoomlaUserButtonUrl = Joomla\CMS\Uri\Uri::base().'index.php?option=com_users&view=user&task=edit&cid[]='.$user->userID;
        }

        if (K2_JVERSION == '30') {
            $editJoomlaUserButton = '<a data-k2-modal="iframe" href="'.$editJoomlaUserButtonUrl.'" class="btn btn-small"><i class="icon-edit"></i>'.Joomla\CMS\Language\Text::_('K2_EDIT_JOOMLA_USER').'</a>';
        } else {
            $editJoomlaUserButton = '<a data-k2-modal="iframe" href="'.$editJoomlaUserButtonUrl.'"><span class="icon-32-edit" title="'.Joomla\CMS\Language\Text::_('K2_EDIT_JOOMLA_USER').'"></span>'.Joomla\CMS\Language\Text::_('K2_EDIT_JOOMLA_USER').'</a>';
        }

        $toolbar->prependButton('Custom', $editJoomlaUserButton);

        parent::display($tpl);
    }
}

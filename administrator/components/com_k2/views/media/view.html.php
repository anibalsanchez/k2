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

class K2ViewMedia extends K2View
{
    public function display($tpl = null)
    {
        $app = Joomla\CMS\Factory::getApplication();
        $user = Joomla\CMS\Factory::getUser();
        $document = Joomla\CMS\Factory::getDocument();
        $type = JRequest::getCmd('type');
        $fieldID = JRequest::getCmd('fieldID');
        if ($type == 'video') {
            $mimes = "'video','audio'";
        } elseif ($type == 'image') {
            $mimes = "'image'";
        } else {
            $mimes = '';
        }

        $token = version_compare(JVERSION, '2.5', 'ge') ? Joomla\CMS\Session\Session::getFormToken() : Joomla\CMS\Utility\Utility::getToken();

        $this->assignRef('mimes', $mimes);
        $this->assignRef('type', $type);
        $this->assignRef('fieldID', $fieldID);
        $this->assignRef('token', $token);

        if ($app->isAdmin()) {
            // Toolbar
            Joomla\CMS\Toolbar\ToolbarHelper::title(Joomla\CMS\Language\Text::_('K2_MEDIA_MANAGER'), 'k2.png');
            if (K2_JVERSION != '15') {
                Joomla\CMS\Toolbar\ToolbarHelper::preferences('com_k2', '(window.innerHeight) * 0.9', '(window.innerWidth) * 0.7', 'K2_SETTINGS');
            } else {
                $toolbar = Joomla\CMS\Toolbar\Toolbar::getInstance('toolbar');
                $toolbar->appendButton('Popup', 'config', 'K2_SETTINGS', 'index.php?option=com_k2&view=settings', '(window.innerWidth) * 0.7', '(window.innerHeight) * 0.9');
            }

            $this->loadHelper('html');
            K2HelperHTML::subMenu();
        }

        parent::display($tpl);
    }
}

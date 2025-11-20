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

jimport('joomla.application.component.controller');

class K2ControllerItemlist extends K2Controller
{
    public function display($cachable = false, $urlparams = [])
    {
        $model = $this->getModel('item');
        $format = JRequest::getWord('format', 'html');
        $document = Joomla\CMS\Factory::getDocument();
        $viewType = $document->getType();
        $view = $this->getView('itemlist', $viewType);
        $view->setModel($model);

        $user = Joomla\CMS\Factory::getUser();
        $cache = (bool) $user->guest;

        if (K2_JVERSION != '15') {
            $urlparams['amp'] = 'INT';
            $urlparams['day'] = 'INT';
            $urlparams['id'] = 'INT';
            $urlparams['Itemid'] = 'INT';
            $urlparams['lang'] = 'CMD';
            $urlparams['limit'] = 'UINT';
            $urlparams['limitstart'] = 'UINT';
            $urlparams['m'] = 'INT';
            $urlparams['moduleID'] = 'INT';
            $urlparams['month'] = 'INT';
            $urlparams['ordering'] = 'CMD';
            $urlparams['print'] = 'INT';
            $urlparams['searchword'] = 'STRING';
            $urlparams['tag'] = 'STRING';
            $urlparams['template'] = 'CMD';
            $urlparams['tmpl'] = 'CMD';
            $urlparams['year'] = 'INT';
        }

        parent::display($cache, $urlparams);
    }

    // For mod_k2_content
    public function module()
    {
        $document = Joomla\CMS\Factory::getDocument();
        $view = $this->getView('itemlist', 'raw');
        $model = $this->getModel('itemlist');
        $view->setModel($model);
        $model = $this->getModel('item');
        $view->setModel($model);
        $view->module();
    }

    // For mod_k2_tools
    public function calendar()
    {
        require_once JPATH_SITE.'/modules/mod_k2_tools/helper.php';
        $calendar = new modK2ToolsHelper();
        $calendar->calendarNavigation();
    }
}

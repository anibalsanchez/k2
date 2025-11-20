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

class K2ControllerLatest extends K2Controller
{
    public function display($cachable = false, $urlparams = [])
    {
        $view = $this->getView('latest', 'html');
        $model = $this->getModel('itemlist');
        $view->setModel($model);
        $itemModel = $this->getModel('item');
        $view->setModel($itemModel);
        $user = Joomla\CMS\Factory::getUser();
        $cache = (bool) $user->guest;

        if (K2_JVERSION != '15') {
            $urlparams['Itemid'] = 'INT';
            $urlparams['m'] = 'INT';
            $urlparams['amp'] = 'INT';
            $urlparams['tmpl'] = 'CMD';
            $urlparams['template'] = 'CMD';
        }

        parent::display($cache, $urlparams);
    }
}

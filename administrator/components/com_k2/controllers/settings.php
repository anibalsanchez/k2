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

class K2ControllerSettings extends K2Controller
{
    public function display($cachable = false, $urlparams = [])
    {
        if (K2_JVERSION != '15') {
            $app = Joomla\CMS\Factory::getApplication();
            $app->redirect('index.php?option=com_config&view=component&component=com_k2&path=&tmpl=component');
        } else {
            K2Request::setVar('tmpl', 'component');
            parent::display();
        }
    }

    public function save()
    {
        $app = Joomla\CMS\Factory::getApplication();
        K2Request::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('settings');
        $model->save();

        $app->redirect('index.php?option=com_k2&view=settings');
    }
}

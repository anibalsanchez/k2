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

class K2ControllerUser extends K2Controller
{
    public function display($cachable = false, $urlparams = [])
    {
        K2Request::setVar('view', 'user');
        parent::display();
    }

    public function save()
    {
        K2Request::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('user');
        $model->save();
    }

    public function apply()
    {
        $this->save();
    }

    public function cancel()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $app->redirect('index.php?option=com_k2&view=users');
    }

    public function report()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $model = K2Model::getInstance('User', 'K2Model');
        $model->setState('id', K2Request::getInt('id'));
        $model->reportSpammer();
        if (K2Request::getCmd('context') == 'modalselector') {
            $app->redirect('index.php?option=com_k2&view=users&tmpl=component&template=system&context=modalselector');
        } else {
            $app->redirect('index.php?option=com_k2&view=users');
        }
    }
}

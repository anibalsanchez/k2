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

class K2ControllerExtraFieldsGroups extends K2Controller
{
    public function display($cachable = false, $urlparams = [])
    {
        JRequest::setVar('view', 'extrafieldsgroups');
        $model = $this->getModel('extraFields');
        $view = $this->getView('extrafieldsgroups', 'html');
        $view->setModel($model, true);
        parent::display();
    }

    public function add()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $app->redirect('index.php?option=com_k2&view=extrafieldsgroup');
    }

    public function edit()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $cid = JRequest::getVar('cid');
        $app->redirect('index.php?option=com_k2&view=extrafieldsgroup&cid='.$cid[0]);
    }

    public function remove()
    {
        JRequest::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('extraFields');
        $model->removeGroups();
    }
}

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
defined('_JEXEC') or die;

jimport('joomla.application.component.controller');

class K2ControllerExtraFieldsGroup extends K2Controller
{
    public function display($cachable = false, $urlparams = [])
    {
        JRequest::setVar('view', 'extrafieldsgroup');
        $model = $this->getModel('extraFields');
        $view = $this->getView('extrafieldsgroup', 'html');
        $view->setModel($model, true);
        parent::display();
    }

    public function apply()
    {
        $this->save();
    }

    public function save()
    {
        JRequest::checkToken() or jexit('Invalid Token');
        $model = $this->getModel('extraFields');
        $view = $this->getView('extrafieldsgroup', 'html');
        $view->setModel($model, true);
        $model->saveGroup();
    }

    public function saveAndNew()
    {
        $this->save();
    }

    public function cancel()
    {
        $app = JFactory::getApplication();
        $app->redirect('index.php?option=com_k2&view=extrafieldsgroups');
    }
}

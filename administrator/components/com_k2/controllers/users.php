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

class K2ControllerUsers extends K2Controller
{
    public function display($cachable = false, $urlparams = [])
    {
        JRequest::setVar('view', 'users');
        parent::display();
    }

    public function edit()
    {
        $app = JFactory::getApplication();
        $cid = JRequest::getVar('cid');
        $app->redirect('index.php?option=com_k2&view=user&cid='.$cid[0]);
    }

    public function remove()
    {
        JRequest::checkToken() or jexit('Invalid Token');
        $model = $this->getModel('users');
        $model->remove();
    }

    public function enable()
    {
        JRequest::checkToken() or jexit('Invalid Token');
        $model = $this->getModel('users');
        $model->enable();
    }

    public function disable()
    {
        JRequest::checkToken() or jexit('Invalid Token');
        $model = $this->getModel('users');
        $model->disable();
    }

    public function delete()
    {
        JRequest::checkToken() or jexit('Invalid Token');
        $model = $this->getModel('users');
        $model->delete();
    }

    public function move()
    {
        $view = $this->getView('users', 'html');
        $view->setLayout('move');
        $model = $this->getModel('users');
        $view->setModel($model);
        $view->move();
    }

    public function saveMove()
    {
        JRequest::checkToken() or jexit('Invalid Token');
        $model = $this->getModel('users');
        $model->saveMove();
    }

    public function cancelMove()
    {
        JRequest::checkToken() or jexit('Invalid Token');
        $app = JFactory::getApplication();
        $app->redirect('index.php?option=com_k2&view=users');
    }

    public function import()
    {
        $model = $this->getModel('users');
        $model->import();
    }

    public function search()
    {
        $app = JFactory::getApplication();
        $db = JFactory::getDbo();
        $word = JRequest::getString('q', null);
        if (K2_JVERSION == '15') {
            $word = $db->Quote($db->getEscaped($word, true).'%', false);
        } else {
            $word = $db->Quote($db->escape($word, true).'%', false);
        }
        $query = 'SELECT id,name FROM #__users WHERE name LIKE '.$word.' OR username LIKE '.$word.' OR email LIKE '.$word;
        $db->setQuery($query);
        $result = $db->loadObjectList();
        echo json_encode($result);
        $app->close();
    }
}

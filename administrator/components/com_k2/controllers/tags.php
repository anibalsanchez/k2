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

class K2ControllerTags extends K2Controller
{
    public function display($cachable = false, $urlparams = [])
    {
        K2Request::setVar('view', 'tags');
        parent::display();
    }

    public function publish()
    {
        K2Request::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('tags');
        $model->publish();
    }

    public function unpublish()
    {
        K2Request::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('tags');
        $model->unpublish();
    }

    public function remove()
    {
        K2Request::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('tags');
        $model->remove();
    }

    public function add()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $app->redirect('index.php?option=com_k2&view=tag');
    }

    public function edit()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $cid = K2Request::getVar('cid');
        $app->redirect('index.php?option=com_k2&view=tag&cid='.$cid[0]);
    }

    public function removeOrphans()
    {
        K2Request::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('tags');
        $model->removeOrphans();
    }
}

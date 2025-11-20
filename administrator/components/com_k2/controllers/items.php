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

class K2ControllerItems extends K2Controller
{
    public function display($cachable = false, $urlparams = [])
    {
        JRequest::setVar('view', 'items');
        parent::display();
    }

    public function publish()
    {
        JRequest::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('items');
        $model->publish();
    }

    public function unpublish()
    {
        JRequest::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('items');
        $model->unpublish();
    }

    public function saveorder()
    {
        JRequest::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('items');
        $result = $model->saveorder();
        $document = Joomla\CMS\Factory::getDocument();
        if ($document->getType() == 'raw') {
            echo '1';

            return $this;
        }

        $this->setRedirect('index.php?option=com_k2&view=items', Joomla\CMS\Language\Text::_('K2_NEW_ORDERING_SAVED'));

        return null;
    }

    public function orderup()
    {
        JRequest::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('items');
        $model->orderup();
    }

    public function orderdown()
    {
        JRequest::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('items');
        $model->orderdown();
    }

    public function savefeaturedorder()
    {
        JRequest::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('items');
        $result = $model->savefeaturedorder();
        $document = Joomla\CMS\Factory::getDocument();
        if ($document->getType() == 'raw') {
            echo '1';

            return $this;
        }

        $this->setRedirect('index.php?option=com_k2&view=items', Joomla\CMS\Language\Text::_('K2_NEW_FEATURED_ORDERING_SAVED'));

        return null;
    }

    public function featuredorderup()
    {
        JRequest::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('items');
        $model->featuredorderup();
    }

    public function featuredorderdown()
    {
        JRequest::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('items');
        $model->featuredorderdown();
    }

    public function accessregistered()
    {
        JRequest::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('items');
        $model->accessregistered();
    }

    public function accessspecial()
    {
        JRequest::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('items');
        $model->accessspecial();
    }

    public function accesspublic()
    {
        JRequest::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('items');
        $model->accesspublic();
    }

    public function featured()
    {
        JRequest::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('items');
        $model->featured();
    }

    public function trash()
    {
        JRequest::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('items');
        $model->trash();
    }

    public function restore()
    {
        JRequest::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('items');
        $model->restore();
    }

    public function remove()
    {
        JRequest::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('items');
        $model->remove();
    }

    public function add()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $app->redirect('index.php?option=com_k2&view=item');
    }

    public function edit()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $cid = JRequest::getVar('cid');
        $app->redirect('index.php?option=com_k2&view=item&cid='.$cid[0]);
    }

    public function copy()
    {
        JRequest::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('items');
        $model->copy();
    }

    public function import()
    {
        $model = $this->getModel('items');
        if (K2_JVERSION != '15') {
            $model->importJ16();
        } else {
            $model->import();
        }
    }

    public function saveBatch()
    {
        JRequest::checkToken() || jexit('Invalid Token');
        $model = $this->getModel('items');
        $model->saveBatch();
    }

    public function logStats()
    {
        JRequest::checkToken() || jexit('Invalid Token');
        $status = JRequest::getInt('status');
        $response = JRequest::getString('response');
        $date = Joomla\CMS\Factory::getDate();
        $now = version_compare(JVERSION, '2.5', 'ge') ? $date->toSql() : $date->toMySQL();
        $db = Joomla\CMS\Factory::getDbo();

        $query = 'DELETE FROM #__k2_log';
        $db->setQuery($query);
        $db->execute();

        $query = 'INSERT INTO #__k2_log VALUES('.$status.', '.$db->quote($response).', '.$db->quote($now).')';
        $db->setQuery($query);
        $db->execute();

        exit;
    }
}

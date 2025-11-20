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

jimport('joomla.application.component.model');

Joomla\CMS\Table\Table::addIncludePath(JPATH_COMPONENT.'/tables');

class K2ModelUser extends K2Model
{
    public function getData()
    {
        $cid = K2Request::getInt('cid');
        $db = Joomla\CMS\Factory::getDbo();
        $query = 'SELECT * FROM #__k2_users WHERE userID = '.$cid;
        $db->setQuery($query);
        $row = $db->loadObject();
        if (!$row) {
            return Joomla\CMS\Table\Table::getInstance('K2User', 'Table');
        }

        return $row;
    }

    public function save()
    {
        $app = Joomla\CMS\Factory::getApplication();
        jimport('joomla.filesystem.file');
        $row = Joomla\CMS\Table\Table::getInstance('K2User', 'Table');
        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');

        if (!$row->bind(K2Request::getPost())) {
            $app->enqueueMessage($row->getError(), 'error');
            $app->redirect('index.php?option=com_k2&view=users');
        }

        $row->description = K2Request::getVar('description', '', 'post', 'string', 2);
        if ($params->get('xssFiltering')) {
            $jFilterInput = new JFilterInput([], [], 1, 1, 0);
            $row->description = $jFilterInput->clean($row->description);
        }

        $jUser = Joomla\CMS\Factory::getUser($row->userID);
        $row->userName = $jUser->name;

        if (!$row->store()) {
            $app->enqueueMessage($row->getError(), 'error');
            $app->redirect('index.php?option=com_k2&view=users');
        }

        // Image
        if ((int) $params->get('imageMemoryLimit') !== 0) {
            ini_set('memory_limit', (int) $params->get('imageMemoryLimit').'M');
        }

        $file = K2Request::getFiles();

        if (isset($file['image']) && $file['image']['error'] == 0 && !K2Request::getBool('del_image')) {
            require_once JPATH_SITE.'/media/k2/assets/vendors/verot/class.upload.php/src/class.upload.php';
            $savepath = JPATH_ROOT.'/media/k2/users/';

            try {
                $handle = new Verot\Upload\Upload($file['image']);
                $handle->allowed = ['image/*'];
                $handle->forbidden = ['image/bmp', 'image/tiff'];

                if ($handle->uploaded) {
                    $handle->file_auto_rename = false;
                    $handle->file_new_name_body = $row->id;
                    $handle->file_overwrite = true;
                    $handle->image_convert = 'webp';
                    $handle->image_ratio_y = true;
                    $handle->image_resize = true;
                    $handle->image_x = $params->get('userImageWidth', '100');
                    $handle->webp_quality = $params->get('imagesQuality', '90');

                    $handle->process($savepath);

                    if ($handle->processed) {
                        $handle->clean();
                        $row->image = $handle->file_dst_name;
                    } else {
                        throw new RuntimeException($handle->error);
                    }
                }
            } catch (Exception $e) {
                $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_COULD_NOT_UPLOAD_YOUR_IMAGE').$e->getMessage(), 'error');
                $app->redirect('index.php?option=com_k2&view=users');
            }
        }

        if (K2Request::getBool('del_image')) {
            $current = Joomla\CMS\Table\Table::getInstance('K2User', 'Table');
            $current->load($row->id);
            $currentImage = basename($current->image);
            if (Joomla\CMS\Filesystem\File::exists(JPATH_ROOT.'/media/k2/users/'.$currentImage)) {
                Joomla\CMS\Filesystem\File::delete(JPATH_ROOT.'/media/k2/users/'.$currentImage);
            }

            $row->image = '';
        }

        if (!$row->check()) {
            $app->enqueueMessage($row->getError(), 'error');
            $app->redirect('index.php?option=com_k2&view=user&cid='.$row->id);
        }

        if (!$row->store()) {
            $app->enqueueMessage($row->getError(), 'error');
            $app->redirect('index.php?option=com_k2&view=users');
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        switch (K2Request::getCmd('task')) {
            case 'apply':
                $msg = Joomla\CMS\Language\Text::_('K2_CHANGES_TO_USER_SAVED');
                $link = 'index.php?option=com_k2&view=user&cid='.$row->userID;
                break;
            case 'save':
            default:
                $msg = Joomla\CMS\Language\Text::_('K2_USER_SAVED');
                $link = 'index.php?option=com_k2&view=users';
                break;
        }

        $app->enqueueMessage($msg);
        $app->redirect($link);
    }

    public function getUserGroups()
    {
        $db = Joomla\CMS\Factory::getDbo();
        $query = 'SELECT * FROM #__k2_user_groups';
        $db->setQuery($query);
        $rows = $db->loadObjectList();

        return $rows;
    }

    public function reportSpammer()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        $id = (int) $this->getState('id');
        if ($id === 0) {
            return false;
        }

        $user = Joomla\CMS\Factory::getUser();
        if ($user->id == $id) {
            $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_YOU_CANNOT_REPORT_YOURSELF'), 'error');

            return false;
        }

        $db = Joomla\CMS\Factory::getDbo();

        // Unpublish user comments
        $db->setQuery('UPDATE #__k2_comments SET published = 0 WHERE userID = '.$id);
        $db->execute();

        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_USER_COMMENTS_UNPUBLISHED'));

        // Unpublish user items
        $db->setQuery('UPDATE #__k2_items SET published = 0 WHERE created_by = '.$id);
        $db->execute();

        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_USER_ITEMS_UNPUBLISHED'));

        // Report the user to stopforumspam.com
        // We need the IP for this, so the user has to be a registered K2 user
        $spammer = Joomla\CMS\Factory::getUser($id);
        $db->setQuery('SELECT ip FROM #__k2_users WHERE userID='.$id, 0, 1);
        $ip = $db->loadResult();
        $stopForumSpamApiKey = trim($params->get('stopForumSpamApiKey'));
        if ($ip && function_exists('fsockopen') && $stopForumSpamApiKey) {
            $data = 'username='.$spammer->username.'&ip_addr='.$ip.'&email='.$spammer->email.'&api_key='.$stopForumSpamApiKey;
            $fp = fsockopen('www.stopforumspam.com', 80);
            fwrite($fp, "POST /add.php HTTP/1.1\n");
            fwrite($fp, "Host: www.stopforumspam.com\n");
            fwrite($fp, "Content-type: application/x-www-form-urlencoded\n");
            fwrite($fp, 'Content-length: '.strlen($data)."\n");
            fwrite($fp, "Connection: close\n\n");
            fwrite($fp, $data);
            fclose($fp);
            $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_USER_DATA_SUBMITTED_TO_STOPFORUMSPAM'));
        }

        // Finally block the user
        $db->setQuery('UPDATE #__users SET block = 1 WHERE id='.$id);
        $db->execute();

        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_USER_BLOCKED'));

        return true;
    }
}

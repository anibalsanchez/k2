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

class K2ModelCategory extends K2Model
{
    public function getData()
    {
        $cid = K2Request::getVar('cid');
        $row = Joomla\CMS\Table\Table::getInstance('K2Category', 'Table');
        $row->load($cid);

        return $row;
    }

    public function save()
    {
        $app = Joomla\CMS\Factory::getApplication();
        jimport('joomla.filesystem.file');
        $row = Joomla\CMS\Table\Table::getInstance('K2Category', 'Table');
        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');

        // Plugin Events
        Joomla\CMS\Plugin\PluginHelper::importPlugin('k2');
        Joomla\CMS\Plugin\PluginHelper::importPlugin('content');
        Joomla\CMS\Plugin\PluginHelper::importPlugin('finder');
        $dispatcher = K2Dispatcher::getInstance();

        if (!$row->bind(K2Request::getPost())) {
            $app->enqueueMessage($row->getError(), 'error');
            $app->redirect('index.php?option=com_k2&view=categories');
        }

        $isNew = !(bool) $row->id;

        // Trigger K2 plugins
        $result = $dispatcher->trigger('onBeforeK2Save', [&$row, $isNew]);

        if (in_array(false, $result, true)) {
            JError::raiseError(500, $row->getError());

            return false;
        }

        // Trigger content & finder plugins before the save event
        $dispatcher->trigger('onContentBeforeSave', ['com_k2.category', $row, $isNew]);
        $dispatcher->trigger('onFinderBeforeSave', ['com_k2.category', $row, $isNew]);

        $row->description = K2Request::getVar('description', '', 'post', 'string', 2);
        if ($params->get('xssFiltering')) {
            $jFilterInput = new JFilterInput([], [], 1, 1, 0);
            $row->description = $jFilterInput->clean($row->description);
        }

        if (!$row->id) {
            $row->ordering = $row->getNextOrder('parent = '.(int) $row->parent.' AND trash=0');
        }

        if (!$row->check()) {
            $app->enqueueMessage($row->getError(), 'error');
            $app->redirect('index.php?option=com_k2&view=category&cid='.$row->id);
        }

        if (!$row->store()) {
            $app->enqueueMessage($row->getError(), 'error');
            $app->redirect('index.php?option=com_k2&view=categories');
        }

        if (!$params->get('disableCompactOrdering')) {
            $row->reorder('parent = '.(int) $row->parent.' AND trash=0');
        }

        if ((int) $params->get('imageMemoryLimit') !== 0) {
            ini_set('memory_limit', (int) $params->get('imageMemoryLimit').'M');
        }

        $files = K2Request::getFiles();

        $existingImage = K2Request::getVar('existingImage');
        if (($files['image']['error'] == 0 || $existingImage) && !K2Request::getBool('del_image')) {
            $image = $files['image']['error'] == 0 ? $files['image'] : JPATH_SITE.'/'.Joomla\CMS\Filesystem\Path::clean($existingImage);

            require_once JPATH_SITE.'/media/k2/assets/vendors/verot/class.upload.php/src/class.upload.php';
            $savepath = JPATH_ROOT.'/media/k2/categories/';

            try {
                $handle = new Verot\Upload\Upload($image);
                $handle->allowed = ['image/*'];
                $handle->forbidden = ['image/bmp', 'image/tiff'];

                if ($handle->uploaded) {
                    $handle->file_auto_rename = false;
                    $handle->file_new_name_body = $row->id;
                    $handle->file_overwrite = true;
                    $handle->image_convert = 'webp';
                    $handle->image_ratio_y = true;
                    $handle->image_resize = true;
                    $handle->image_x = $params->get('catImageWidth', '100');
                    $handle->webp_quality = $params->get('imagesQuality', '90');

                    $handle->process($savepath);

                    if ($handle->processed) {
                        if ($files['image']['error'] == 0) {
                            $handle->clean();
                        }

                        $row->image = $handle->file_dst_name;
                    } else {
                        throw new RuntimeException($handle->error);
                    }
                }
            } catch (Exception $e) {
                $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_COULD_NOT_UPLOAD_YOUR_IMAGE').$e->getMessage(), 'error');
                $app->redirect('index.php?option=com_k2&view=categories');
            }
        }

        if (K2Request::getBool('del_image')) {
            $savedRow = Joomla\CMS\Table\Table::getInstance('K2Category', 'Table');
            $savedRow->load($row->id);
            if (Joomla\CMS\Filesystem\File::exists(JPATH_ROOT.'/media/k2/categories/'.$savedRow->image)) {
                Joomla\CMS\Filesystem\File::delete(JPATH_ROOT.'/media/k2/categories/'.$savedRow->image);
            }

            $row->image = '';
        }

        if (!$row->store()) {
            $app->enqueueMessage($row->getError(), 'error');
            $app->redirect('index.php?option=com_k2&view=categories');
        }

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        // Trigger K2 plugins
        $dispatcher->trigger('onAfterK2Save', [&$row, $isNew]);

        // Trigger content & finder plugins after the save event
        if (K2_JVERSION != '15') {
            $dispatcher->trigger('onContentAfterSave', ['com_k2.category', &$row, $isNew]);
        } else {
            $dispatcher->trigger('onAfterContentSave', [&$row, $isNew]);
        }

        $results = $dispatcher->trigger('onFinderAfterSave', ['com_k2.category', $row, $isNew]);

        switch (K2Request::getCmd('task')) {
            case 'apply':
                $msg = Joomla\CMS\Language\Text::_('K2_CHANGES_TO_CATEGORY_SAVED');
                $link = 'index.php?option=com_k2&view=category&cid='.$row->id;
                break;
            case 'saveAndNew':
                $msg = Joomla\CMS\Language\Text::_('K2_CATEGORY_SAVED');
                $link = 'index.php?option=com_k2&view=category';
                break;
            case 'save':
            default:
                $msg = Joomla\CMS\Language\Text::_('K2_CATEGORY_SAVED');
                $link = 'index.php?option=com_k2&view=categories';
                break;
        }

        $app->enqueueMessage($msg);
        $app->redirect($link);

        return null;
    }

    public function countCategoryItems($catid, $trash = 0)
    {
        $db = Joomla\CMS\Factory::getDbo();
        $catid = (int) $catid;
        $query = sprintf('SELECT COUNT(*) FROM #__k2_items WHERE catid=%d AND trash = ', $catid).(int) $trash;
        $db->setQuery($query);
        $result = $db->loadResult();

        return $result;
    }
}

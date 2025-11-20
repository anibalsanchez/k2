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

jimport('joomla.plugin.plugin');

class plgUserK2 extends Joomla\CMS\Plugin\CMSPlugin
{
    public function onUserAfterSave($user, $isnew, $success, $msg)
    {
        return $this->onAfterStoreUser($user, $isnew, $success, $msg);
    }

    public function onUserLogin($user, $options)
    {
        return $this->onLoginUser($user, $options);
    }

    public function onUserLogout($user)
    {
        return $this->onLogoutUser($user);
    }

    public function onUserAfterDelete($user, $success, $msg)
    {
        return $this->onAfterDeleteUser($user, $success, $msg);
    }

    public function onUserBeforeSave($user, $isNew)
    {
        return $this->onBeforeStoreUser($user, $isNew);
    }

    public function onAfterStoreUser($user, $isnew, $success, $msg)
    {
        jimport('joomla.filesystem.file');
        $app = Joomla\CMS\Factory::getApplication();
        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        $task = K2Request::getCmd('task');

        if ($app->isClient('site') && ($task == 'activate' || $isnew) && $params->get('stopForumSpam')) {
            $this->checkSpammer($user);
        }

        if ($app->isClient('site') && $task != 'activate' && K2Request::getInt('K2UserForm')) {
            Joomla\CMS\Plugin\CMSPlugin::loadLanguage('com_k2');
            Joomla\CMS\Table\Table::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2/tables');
            $row = Joomla\CMS\Table\Table::getInstance('K2User', 'Table');
            $k2id = $this->getK2UserID($user['id']);
            K2Request::setVar('id', $k2id, 'post');
            $row->bind(K2Request::getPost());
            $row->set('userID', $user['id']);
            $row->set('userName', $user['name']);
            $row->set('ip', $_SERVER['REMOTE_ADDR']);
            $row->set('hostname', gethostbyaddr($_SERVER['REMOTE_ADDR']));
            if (isset($user['notes'])) {
                $row->set('notes', $user['notes']);
            }

            if ($isnew) {
                $row->set('group', $params->get('K2UserGroup', 1));
            } else {
                $row->set('group', null);
                $row->set('gender', K2Request::getVar('gender', 'n'));
                $row->set('url', K2Request::getString('url'));
            }

            /*
            if ($row->gender != 'm' && $row->gender != 'f') {
                $row->gender = 'n';
            }
            */
            $row->url = JString::str_ireplace(' ', '', $row->url);
            $row->url = JString::str_ireplace('"', '', $row->url);
            $row->url = JString::str_ireplace('<', '', $row->url);
            $row->url = JString::str_ireplace('>', '', $row->url);
            $row->url = JString::str_ireplace("'", '', $row->url);
            $row->set('description', K2Request::getVar('description', '', 'post', 'string', 4));
            if ($params->get('xssFiltering')) {
                $jFilterInput = new JFilterInput([], [], 1, 1, 0);
                $row->description = $jFilterInput->clean($row->description);
            }

            $row->store();

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
                            $image = $handle->file_dst_name;
                        } else {
                            throw new RuntimeException($handle->error);
                        }
                    }
                } catch (Exception $e) {
                    $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_COULD_NOT_UPLOAD_YOUR_IMAGE').$e->getMessage(), 'error');
                }
            }

            if (K2Request::getBool('del_image')) {
                $currentImage = basename($row->image);
                if (Joomla\CMS\Filesystem\File::exists(JPATH_ROOT.'/media/k2/users/'.$currentImage)) {
                    Joomla\CMS\Filesystem\File::delete(JPATH_ROOT.'/media/k2/users/'.$currentImage);
                }

                $image = '';
            }

            if (isset($image)) {
                $row->image = $image;
                $row->store();
            }

            $itemid = $params->get('redirect');
            if (!$isnew && $itemid) {
                $menu = $app->getMenu();
                $item = $menu->getItem($itemid);
                $url = Joomla\CMS\Router\Route::_($item->link.'&Itemid='.$itemid, false);

                if (K2_JVERSION == '15') {
                    if (Joomla\CMS\Uri\Uri::isInternal($url)) {
                        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_YOUR_SETTINGS_HAVE_BEEN_SAVED'));
                        $app->redirect($url);
                    }
                } else {
                    $app->setUserState('com_users.edit.profile.redirect', $url);
                }
            }
        }
    }

    public function onLoginUser($user, $options)
    {
        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        $app = Joomla\CMS\Factory::getApplication();
        if ($app->isClient('site')) {
            // Get the user id
            $db = Joomla\CMS\Factory::getDbo();
            $db->setQuery('SELECT id FROM #__users WHERE username = '.$db->Quote($user['username']));
            $id = $db->loadResult();

            // If K2 profiles are enabled assign non-existing K2 users to the default K2 group. Update user info for existing K2 users.
            if ($params->get('K2UserProfile') && $id) {
                $k2id = $this->getK2UserID($id);
                Joomla\CMS\Table\Table::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2/tables');
                $row = Joomla\CMS\Table\Table::getInstance('K2User', 'Table');
                if ($k2id) {
                    $row->load($k2id);
                } else {
                    $row->set('userID', $id);
                    $row->set('userName', $user['fullname']);
                    $row->set('group', $params->get('K2UserGroup', 1));
                }

                $row->ip = $_SERVER['REMOTE_ADDR'];
                $row->hostname = gethostbyaddr($_SERVER['REMOTE_ADDR']);
                $row->store();
            }

            // Set the Cookie domain for user based on K2 parameters
            if ($params->get('cookieDomain') && $id) {
                setcookie('userID', $id, ['expires' => 0, 'path' => '/', 'domain' => $params->get('cookieDomain'), 'secure' => 0]);
            }
        }

        return true;
    }

    public function onLogoutUser($user)
    {
        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        $app = Joomla\CMS\Factory::getApplication();
        if ($app->isClient('site') && $params->get('cookieDomain')) {
            setcookie('userID', '', ['expires' => time() - 3600, 'path' => '/', 'domain' => $params->get('cookieDomain'), 'secure' => 0]);
        }

        return true;
    }

    public function onAfterDeleteUser($user, $succes, $msg)
    {
        $app = Joomla\CMS\Factory::getApplication();
        $db = Joomla\CMS\Factory::getDbo();
        $query = 'DELETE FROM #__k2_users WHERE userID='.$user['id'];
        $db->setQuery($query);
        $db->execute();
    }

    public function onBeforeStoreUser($user, $isNew)
    {
        $app = Joomla\CMS\Factory::getApplication();
        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        $session = Joomla\CMS\Factory::getSession();
        if ($params->get('K2UserProfile') && $isNew && $params->get('recaptchaOnRegistration') && $app->isClient('site') && !$session->get('socialConnectData')) {
            require_once JPATH_SITE.'/components/com_k2/helpers/utilities.php';
            if (!K2HelperUtilities::verifyRecaptcha()) {
                $url = K2_JVERSION != '15' ? 'index.php?option=com_users&view=registration' : 'index.php?option=com_user&view=register';

                $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_COULD_NOT_VERIFY_THAT_YOU_ARE_NOT_A_ROBOT'), 'error');
                $app->redirect($url);
            }
        }
    }

    public function getK2UserID($id)
    {
        $db = Joomla\CMS\Factory::getDbo();
        $query = 'SELECT id FROM #__k2_users WHERE userID='.$id;
        $db->setQuery($query);
        $result = $db->loadResult();

        return $result;
    }

    public function checkSpammer(&$user)
    {
        if (!$user['block']) {
            $ip = $_SERVER['REMOTE_ADDR'];
            $email = urlencode($user['email']);
            $username = urlencode($user['username']);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://www.stopforumspam.com/api?ip='.$ip.'&email='.$email.'&username='.$username.'&f=json');
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($httpCode == 200) {
                $response = json_decode($response);
                if ($response->ip->appears || $response->email->appears || $response->username->appears) {
                    $db = Joomla\CMS\Factory::getDbo();
                    $db->setQuery('UPDATE #__users SET block = 1 WHERE id = '.$user['id']);
                    $db->execute();
                    $user['notes'] = Joomla\CMS\Language\Text::_('K2_POSSIBLE_SPAMMER_DETECTED_BY_STOPFORUMSPAM');
                }
            }
        }
    }
}

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
jimport('joomla.filesystem.file');

class K2ControllerMedia extends K2Controller
{
    public function display($cachable = false, $urlparams = [])
    {
        K2Request::setVar('view', 'media');
        parent::display();
    }

    public function connector()
    {
        // Check token
        $method = ($_POST !== []) ? 'post' : 'get';
        if (version_compare(JVERSION, '2.5', 'ge')) {
            Joomla\CMS\Session\Session::checkToken($method) || jexit(Joomla\CMS\Language\Text::_('JINVALID_TOKEN'));
        } else {
            K2Request::checkToken($method) || jexit(Joomla\CMS\Language\Text::_('JINVALID_TOKEN'));
        }

        $app = Joomla\CMS\Factory::getApplication();
        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_media');
        $root = $params->get('file_path', 'media');
        $folder = K2Request::getVar('folder', $root, 'default', 'path');
        $type = K2Request::getCmd('type', 'video');

        if (JString::trim($folder) == '') {
            $folder = $root;
        } elseif (!str_starts_with($folder, $root)) {
            // Ensure that we are always below the root directory
            $folder = $root;
        }

        // Disable debug
        K2Request::setVar('debug', false);

        $url = Joomla\CMS\Uri\Uri::root(true).'/'.$folder;
        $path = JPATH_SITE.'/'.Joomla\CMS\Filesystem\Path::clean($folder);

        Joomla\CMS\Filesystem\Path::check($path);

        // Disallow force downloading sensitive file types
        $disallowedFileTypes = ['php', 'ini', 'sql', 'htaccess'];
        $target = K2Request::getCmd('target');
        $download = K2Request::getCmd('download');
        if ($target && $download) {
            $filePath = base64_decode(substr($target, 2));
            $fileExtension = strtolower(pathinfo(basename($filePath), PATHINFO_EXTENSION));
            if (in_array($fileExtension, $disallowedFileTypes)) {
                return;
            }
        }

        require_once JPATH_SITE.'/media/k2/assets/vendors/studio-42/elfinder/php/autoload.php';

        function access($attr, $path, $data, $volume)
        {
            $app = Joomla\CMS\Factory::getApplication();

            // Hide PHP files
            $ext = strtolower(Joomla\CMS\Filesystem\File::getExt(basename($path)));

            if ($ext === 'php') {
                return true;
            }

            // Hide files and folders starting with .
            if (str_starts_with(basename($path), '.') && $attr == 'hidden') {
                return true;
            }

            // Read only access for front-end. Full access for administration section.
            switch ($attr) {
                case 'read':
                    return true;
                    break;
                case 'write':
                    return !(bool) $app->isClient('site');
                    break;
                case 'locked':
                    return (bool) $app->isClient('site');
                    break;
                case 'hidden':
                    return false;
                    break;
            }

            return null;
        }

        $permissions = $app->isClient('administrator') ? ['read' => true, 'write' => true] : ['read' => true, 'write' => false];

        $options = [
            'debug' => false,
            'roots' => [
                [
                    'driver' => 'LocalFileSystem',
                    'path' => $path,
                    'URL' => $url,
                    'accessControl' => 'access',
                    'defaults' => $permissions,
                    'mimeDetect' => 'internal',
                    'mimefile' => JPATH_SITE.'/media/k2/assets/vendors/studio-42/elfinder/php/mime.types',
                    'uploadDeny' => ['all'],
                    'uploadAllow' => ['image', 'video', 'audio', 'text/plain', 'text/html', 'application/json', 'application/pdf', 'application/zip', 'application/x-7z-compressed', 'application/x-bzip', 'application/x-bzip2', 'text/css', 'application/msword', 'application/vnd.ms-excel', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
                    'uploadOrder' => ['deny', 'allow'],
                ],
            ],
        ];
        $connector = new elFinderConnector(new elFinder($options));
        $connector->run();
    }
}

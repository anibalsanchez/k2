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

class K2HelperStats
{
    public static function getScripts()
    {
        $data = self::getData();
        $token = version_compare(JVERSION, '2.5', 'ge') ? Joomla\CMS\Session\Session::getFormToken() : Joomla\CMS\Utility\Utility::getToken();

        if (version_compare(JVERSION, '1.6.0', 'ge')) {
            Joomla\CMS\HTML\HTMLHelper::_('behavior.framework');
        } else {
            Joomla\CMS\HTML\HTMLHelper::_('behavior.mootools');
        }

        if (version_compare(JVERSION, '3.0.0', 'ge')) {
            Joomla\CMS\HTML\HTMLHelper::_('jquery.framework');
        }

        $document = Joomla\CMS\Factory::getDocument();

        // For IE8/9 only (to be removed in K2 v3.x)
        $document->addScript('https://cdnjs.cloudflare.com/ajax/libs/jquery-ajaxtransport-xdomainrequest/1.0.4/jquery.xdomainrequest.min.js');

        $document->addScriptDeclaration("
	    	/* K2 - Metrics */
	        (function(\$) {
				function K2LogResult(xhr) {
					\$.ajax({
						type: 'POST',
						url: 'index.php',
						data: {
							'option': 'com_k2',
							'view': 'items',
							'task': 'logStats',
							'".$token."': '1',
							'status': xhr.status,
							'response': xhr.responseText
						}
					});
				}
		        \$(document).ready(function() {
					\$.ajax({
						crossDomain: true,
						type: 'POST',
						url: 'https://metrics.getk2.org/gather.php',
						data: ".$data.'
					}).done(function(response, result, xhr) {
						K2LogResult(xhr);
					}).fail(function(xhr, result, response) {
						K2LogResult(xhr);
					});
				});
			})(jQuery);
		');
    }

    public static function getData()
    {
        $data = new stdClass();
        $data->identifier = self::getIdentifier();
        $data->php = self::getPhpVersion();
        $data->databaseType = self::getDbType();
        $data->databaseVersion = self::getDbVersion();
        $data->server = self::getServer();
        $data->serverInterface = self::getServerInterface();
        $data->cms = self::getCmsVersion();
        $data->extensionName = 'K2';
        $data->extensionVersion = self::getExtensionVersion();
        $data->caching = self::getCaching();
        $data->cachingDriver = self::getCachingDriver();

        return json_encode($data);
    }

    public static function getIdentifier()
    {
        $configuration = Joomla\CMS\Factory::getConfig();
        $secret = version_compare(JVERSION, '2.5', 'ge') ? $configuration->get('secret') : $configuration->getValue('config.secret');

        return md5($secret.$_SERVER['SERVER_ADDR']);
    }

    public static function getPhpVersion()
    {
        return phpversion();
    }

    public static function getDbType()
    {
        $configuration = Joomla\CMS\Factory::getConfig();
        $type = version_compare(JVERSION, '2.5', 'ge') ? $configuration->get('dbtype') : $configuration->getValue('config.dbtype');
        if ($type == 'mysql' || $type == 'mysqli' || $type == 'pdomysql') {
            $db = Joomla\CMS\Factory::getDbo();
            $query = 'SELECT version();';
            $db->setQuery($query);
            $result = $db->loadResult();
            $result = strtolower($result);
            if (str_contains($result, 'mariadb')) {
                $type = 'mariadb';
            }
        }

        return $type;
    }

    public static function getDbVersion()
    {
        $db = Joomla\CMS\Factory::getDbo();

        return $db->getVersion();
    }

    public static function getServer()
    {
        return $_SERVER['SERVER_SOFTWARE'] ?? getenv('SERVER_SOFTWARE');
    }

    public static function getServerInterface()
    {
        return PHP_SAPI;
    }

    public static function getCmsVersion()
    {
        return JVERSION;
    }

    public static function getExtensionVersion()
    {
        return K2_CURRENT_VERSION;
    }

    public static function getCaching()
    {
        $configuration = Joomla\CMS\Factory::getConfig();

        return version_compare(JVERSION, '2.5', 'ge') ? $configuration->get('caching') : $configuration->getValue('config.caching');
    }

    public static function getCachingDriver()
    {
        $configuration = Joomla\CMS\Factory::getConfig();

        return version_compare(JVERSION, '2.5', 'ge') ? $configuration->get('cache_handler') : $configuration->getValue('config.cache_handler');
    }

    public static function shouldLog()
    {
        $db = Joomla\CMS\Factory::getDbo();
        $query = 'SELECT * FROM #__k2_log';
        $db->setQuery($query, 0, 1);
        $result = $db->loadObject();
        if (!$result) {
            return true;
        }

        $now = Joomla\CMS\Factory::getDate()->toUnix();
        $days = floor(($now - strtotime($result->timestamp)) / (60 * 60 * 24));

        return (bool) ($days >= 30 || $result->status != 200);
    }
}

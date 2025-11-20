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

require_once JPATH_SITE.'/components/com_k2/helpers/route.php';
require_once JPATH_SITE.'/components/com_k2/helpers/utilities.php';

class modK2UsersHelper
{
    public static function getUsers(&$params)
    {
        $app = Joomla\CMS\Factory::getApplication();
        $db = Joomla\CMS\Factory::getDbo();

        $jnow = Joomla\CMS\Factory::getDate();
        $now = (K2_JVERSION != '15') ? $jnow->toSql() : $jnow->toMySQL();
        $nullDate = $db->getNullDate();

        // Get ACL
        $user = Joomla\CMS\Factory::getUser();
        if (K2_JVERSION != '15') {
            $userLevels = array_unique($user->getAuthorisedViewLevels());
            $aclCheck = 'IN('.implode(',', $userLevels).')';
        } else {
            $aid = $user->get('aid');
            $aclCheck = '<= '.$user->get('aid');
        }

        // Get language on Joomla 2.5+
        $languageFilter = '';
        if (K2_JVERSION != '15' && $app->getLanguageFilter()) {
            $languageTag = Joomla\CMS\Factory::getLanguage()->getTag();
            $languageFilter = $db->Quote($languageTag).', '.$db->Quote('*');
        }

        $userObjects = [];

        if ($params->get('source') == 'specific' && $params->get('userIDs')) {
            $IDs = [];
            if (is_string($params->get('userIDs'))) {
                $IDs[] = $params->get('userIDs');
            } else {
                $IDs = $params->get('userIDs');
            }

            $query = 'SELECT users.name, users.email, users.id AS UID, profiles.*
                FROM #__users AS users
                LEFT JOIN #__k2_users AS profiles ON users.id=profiles.userID
                WHERE users.block=0 AND users.id IN ('.implode(',', $IDs).')';

            $db->setQuery($query);
            $userObjects = $db->loadObjectList();

            $newUserObjects = [];
            foreach ($IDs as $id) {
                foreach ($userObjects as $userObject) {
                    if ($userObject->UID == $id) {
                        $newUserObjects[] = $userObject;
                        break;
                    }
                }
            }

            $userObjects = $newUserObjects;
        } else {
            switch ($params->get('filter', 0)) {
                // By K2 user group
                case 0:
                    $query = 'SELECT users.name, users.email, users.id AS UID, profiles.*';

                    if ($params->get('ordering') == 'recent') {
                        $query .= ', MAX(i.created) AS counter';
                    }

                    $query .= ' FROM #__users AS users
                        LEFT JOIN #__k2_users AS profiles ON users.id=profiles.userID';

                    if ($params->get('ordering') == 'recent') {
                        $query .= ' LEFT JOIN #__k2_items AS i ON users.id=i.created_by LEFT JOIN #__k2_categories AS c ON i.catid=c.id';
                    }

                    $query .= ' WHERE users.block=0 AND profiles.`group`='.(int) $params->get('K2UserGroup');

                    if ($params->get('ordering') == 'recent') {
                        $query .= " AND i.published = 1
                            AND i.trash = 0
                            AND i.access {$aclCheck}
                            AND (i.publish_up = ".$db->Quote($nullDate).' OR i.publish_up <= '.$db->Quote($now).')
                            AND (i.publish_down = '.$db->Quote($nullDate).' OR i.publish_down >= '.$db->Quote($now).(')
                            AND i.created_by_alias=\'\'
                            AND c.published = 1
                            AND c.trash = 0
                            AND c.access '.$aclCheck);

                        if ($languageFilter !== '' && $languageFilter !== '0') {
                            $query .= sprintf(' AND i.language IN (%s) AND c.language IN (%s)', $languageFilter, $languageFilter);
                        }
                    }

                    switch ($params->get('ordering')) {
                        case 'alpha':
                            $orderby = 'users.name';
                            break;
                        case 'recent':
                            $orderby = 'counter DESC';
                            break;
                        case 'random':
                            $orderby = 'RAND()';
                            break;
                    }

                    $query .= ' GROUP BY users.id ORDER BY '.$orderby;
                    break;

                    // With most items
                case 1:
                    $query = "SELECT users.name, users.email, users.id AS UID, profiles.*, COUNT(i.id) AS counter
                        FROM #__users AS users
                        LEFT JOIN #__k2_users AS profiles ON users.id=profiles.userID
                        LEFT JOIN #__k2_items AS i ON users.id=i.created_by
                        LEFT JOIN #__k2_categories AS c ON i.catid=c.id
                        WHERE users.block=0
                            AND i.published = 1
                            AND i.trash = 0
                            AND i.access {$aclCheck}
                            AND (i.publish_up = ".$db->Quote($nullDate).' OR i.publish_up <= '.$db->Quote($now).')
                            AND (i.publish_down = '.$db->Quote($nullDate).' OR i.publish_down >= '.$db->Quote($now).(')
                            AND i.created_by_alias=\'\'
                            AND c.published = 1
                            AND c.trash = 0
                            AND c.access '.$aclCheck);

                    if ($languageFilter !== '' && $languageFilter !== '0') {
                        $query .= sprintf(' AND i.language IN (%s) AND c.language IN (%s)', $languageFilter, $languageFilter);
                    }

                    $query .= ' GROUP BY users.id ORDER BY counter DESC';
                    break;

                    // With most popular items
                case 2:
                    $query = "SELECT users.name, users.email, users.id AS UID, profiles.*, MAX(i.hits) AS counter
                        FROM #__users AS users
                        LEFT JOIN #__k2_users AS profiles ON users.id=profiles.userID
                        LEFT JOIN #__k2_items AS i ON users.id=i.created_by
                        LEFT JOIN #__k2_categories AS c ON i.catid=c.id
                        WHERE users.block=0
                            AND i.published = 1
                            AND i.trash = 0
                            AND i.access {$aclCheck}
                            AND (i.publish_up = ".$db->Quote($nullDate).' OR i.publish_up <= '.$db->Quote($now).')
                            AND (i.publish_down = '.$db->Quote($nullDate).' OR i.publish_down >= '.$db->Quote($now).(')
                            AND i.created_by_alias=\'\'
                            AND c.published = 1
                            AND c.trash = 0
                            AND c.access '.$aclCheck);

                    if ($languageFilter !== '' && $languageFilter !== '0') {
                        $query .= sprintf(' AND i.language IN (%s) AND c.language IN (%s)', $languageFilter, $languageFilter);
                    }

                    $query .= ' GROUP BY users.id ORDER BY counter DESC';
                    break;

                    // With most commented items
                case 3:
                    $query = "SELECT users.name, users.email, users.id AS UID, profiles.*, COUNT(comment.id) AS counter
                        FROM #__users AS users
                        LEFT JOIN #__k2_users AS profiles ON users.id=profiles.userID
                        LEFT JOIN #__k2_items AS i ON users.id=i.created_by
                        LEFT JOIN #__k2_categories AS c ON i.catid=c.id
                        LEFT JOIN #__k2_comments AS comment ON i.id=comment.itemID
                        WHERE users.block=0
                            AND i.published = 1
                            AND i.trash = 0
                            AND i.access {$aclCheck}
                            AND (i.publish_up = ".$db->Quote($nullDate).' OR i.publish_up <= '.$db->Quote($now).')
                            AND (i.publish_down = '.$db->Quote($nullDate).' OR i.publish_down >= '.$db->Quote($now).(')
                            AND i.created_by_alias=\'\'
                            AND c.published = 1
                            AND c.trash = 0
                            AND c.access '.$aclCheck);

                    if ($languageFilter !== '' && $languageFilter !== '0') {
                        $query .= sprintf(' AND i.language IN (%s) AND c.language IN (%s)', $languageFilter, $languageFilter);
                    }

                    $query .= ' GROUP BY users.id ORDER BY counter DESC';
                    break;
            }

            $db->setQuery($query, 0, $params->get('limit', 4));
            $userObjects = $db->loadObjectList();
        }

        // Render the query results
        if (count($userObjects) > 0) {
            foreach ($userObjects as $userObject) {
                $userObject->avatar = K2HelperUtilities::getAvatar($userObject->UID, $userObject->email, $params->get('userImageWidth'));
                $userObject->link = Joomla\CMS\Router\Route::_(K2HelperRoute::getUserRoute($userObject->UID));
                $userObject->feed = Joomla\CMS\Router\Route::_(K2HelperRoute::getUserRoute($userObject->UID).'&format=feed');
                $userObject->url = htmlspecialchars($userObject->url, ENT_QUOTES, 'UTF-8');

                if ($params->get('userItemCount')) {
                    $query = "SELECT i.*, c.name AS categoryname, c.id AS categoryid, c.alias AS categoryalias, c.params AS categoryparams
                        FROM #__k2_items AS i
                        LEFT JOIN #__k2_categories AS c ON c.id = i.catid
                        WHERE i.published = 1
                            AND i.trash = 0
                            AND i.access {$aclCheck}
                            AND (i.publish_up = ".$db->Quote($nullDate).' OR i.publish_up <= '.$db->Quote($now).')
                            AND (i.publish_down = '.$db->Quote($nullDate).' OR i.publish_down >= '.$db->Quote($now).')
                            AND i.created_by='.(int) $userObject->UID.('
                            AND i.created_by_alias=\'\'
                            AND c.published = 1
                            AND c.trash = 0
                            AND c.access '.$aclCheck);

                    if ($languageFilter !== '' && $languageFilter !== '0') {
                        $query .= sprintf(' AND i.language IN (%s) AND c.language IN (%s)', $languageFilter, $languageFilter);
                    }

                    $query .= ' ORDER BY i.created DESC';

                    $db->setQuery($query, 0, $params->get('userItemCount'));
                    $userObject->items = $db->loadObjectList();

                    if (count($userObject->items) > 0) {
                        foreach ($userObject->items as $item) {
                            $link = K2HelperRoute::getItemRoute($item->id.':'.urlencode($item->alias), $item->catid.':'.urlencode($item->categoryalias));
                            $item->link = urldecode(Joomla\CMS\Router\Route::_($link));
                            $item->categoryLink = urldecode(Joomla\CMS\Router\Route::_(K2HelperRoute::getCategoryRoute($item->catid.':'.urlencode($item->categoryalias))));
                        }
                    }
                } else {
                    $userObject->items = null;
                }
            }
        }

        return $userObjects;
    }
}

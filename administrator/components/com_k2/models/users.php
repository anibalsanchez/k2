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

class K2ModelUsers extends K2Model
{
    public function getData()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $option = JRequest::getCmd('option');
        $view = JRequest::getCmd('view');
        $db = Joomla\CMS\Factory::getDbo();
        $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'), 'int');
        $limitstart = $app->getUserStateFromRequest($option.$view.'.limitstart', 'limitstart', 0, 'int');
        $filter_order = $app->getUserStateFromRequest($option.$view.'filter_order', 'filter_order', 'juser.name', 'cmd');
        $filter_order_Dir = $app->getUserStateFromRequest($option.$view.'filter_order_Dir', 'filter_order_Dir', '', 'word');
        $filter_status = $app->getUserStateFromRequest($option.$view.'filter_status', 'filter_status', -1, 'int');
        $filter_group = $app->getUserStateFromRequest($option.$view.'filter_group', 'filter_group', '', 'string');
        $filter_group_k2 = $app->getUserStateFromRequest($option.$view.'filter_group_k2', 'filter_group_k2', '', 'string');
        $search = $app->getUserStateFromRequest($option.$view.'search', 'search', '', 'string');
        $search = JString::strtolower($search);
        $search = trim(preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $search));

        $query = 'SELECT juser.*, k2user.group, k2group.name AS groupname, k2user.image AS image
            FROM #__users AS juser
            LEFT JOIN #__k2_users AS k2user ON juser.id = k2user.userID
            LEFT JOIN #__k2_user_groups AS k2group ON k2user.group = k2group.id
        ';

        if (K2_JVERSION != '15' && $filter_group) {
            $query .= ' LEFT JOIN #__user_usergroup_map AS `map` ON juser.id = map.user_id';
        }

        $query .= ' WHERE juser.id > 0';

        if ($filter_status > -1) {
            $query .= ' AND juser.block = '.$filter_status;
        }

        if ($filter_group) {
            if (K2_JVERSION != '15') {
                $query .= ' AND `map`.group_id ='.(int) $filter_group;
            } else {
                switch ($filter_group) {
                    case 'Public Frontend':
                        $query .= " AND juser.usertype IN ('Registered', 'Author', 'Editor', 'Publisher')";
                        break;

                    case 'Public Backend':
                        $query .= " AND juser.usertype IN ('Manager', 'Administrator', 'Super Administrator')";
                        break;

                    default:
                        $filter_group = strtolower(trim($filter_group));
                        $query .= ' AND juser.usertype = '.$db->Quote($filter_group);
                }
            }
        }

        if ($filter_group_k2) {
            $query .= ' AND k2user.group = '.$db->Quote($filter_group_k2);
        }

        if ($search !== '' && $search !== '0') {
            $escaped = (K2_JVERSION == '15') ? $db->getEscaped($search, true) : $db->escape($search, true);
            $query .= ' AND (LOWER(juser.name) LIKE '.$db->Quote('%'.$escaped.'%', false).' OR LOWER(juser.email) LIKE '.$db->Quote('%'.$escaped.'%', false).')';
        }

        if (!$filter_order) {
            $filter_order = 'juser.name';
        }

        $query .= sprintf(' ORDER BY %s %s', $filter_order, $filter_order_Dir);
        $db->setQuery($query, $limitstart, $limit);
        $rows = $db->loadObjectList();

        if (K2_JVERSION != '15' && count($rows)) {
            foreach ($rows as $row) {
                $IDs[] = $row->id;
            }

            $query = "SELECT map.user_id, COUNT(map.group_id) AS group_count, GROUP_CONCAT(g2.title SEPARATOR '\n') AS group_names
                FROM #__user_usergroup_map AS map
                LEFT JOIN #__usergroups AS g2 ON g2.id = map.group_id
                WHERE map.user_id IN (".implode(',', $IDs).')
                GROUP BY map.user_id';
            $db->setQuery($query);
            $groups = $db->loadObjectList();
            foreach ($rows as $row) {
                foreach ($groups as $group) {
                    if ($row->id == $group->user_id) {
                        $row->usertype = nl2br($group->group_names);
                    }
                }
            }
        }

        return $rows;
    }

    public function getTotal()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $option = JRequest::getCmd('option');
        $view = JRequest::getCmd('view');
        $db = Joomla\CMS\Factory::getDbo();
        $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'), 'int');
        $limitstart = $app->getUserStateFromRequest($option.'.limitstart', 'limitstart', 0, 'int');
        $filter_status = $app->getUserStateFromRequest($option.$view.'filter_status', 'filter_status', -1, 'int');
        $filter_group = $app->getUserStateFromRequest($option.$view.'filter_group', 'filter_group', '', 'string');
        $filter_group_k2 = $app->getUserStateFromRequest($option.$view.'filter_group_k2', 'filter_group_k2', '', 'string');
        $search = $app->getUserStateFromRequest($option.$view.'search', 'search', '', 'string');
        $search = JString::strtolower($search);
        $search = trim(preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $search));

        $query = 'SELECT COUNT(DISTINCT juser.id) FROM #__users as juser '.'LEFT JOIN #__k2_users as k2user ON juser.id=k2user.userID '.'LEFT JOIN #__k2_user_groups as k2group ON k2user.group=k2group.id ';

        if (K2_JVERSION != '15' && $filter_group) {
            $query .= ' LEFT JOIN #__user_usergroup_map as `map` ON juser.id=map.user_id ';
        }

        $query .= ' WHERE juser.id>0';

        if ($filter_status > -1) {
            $query .= ' AND juser.block = '.$filter_status;
        }

        if ($filter_group) {
            if (K2_JVERSION != '15') {
                $query .= ' AND `map`.group_id ='.(int) $filter_group;
            } else {
                switch ($filter_group) {
                    case 'Public Frontend':
                        $query .= " AND juser.usertype IN ('Registered', 'Author', 'Editor', 'Publisher')";
                        break;

                    case 'Public Backend':
                        $query .= " AND juser.usertype IN ('Manager', 'Administrator', 'Super Administrator')";
                        break;

                    default:
                        $filter_group = strtolower(trim($filter_group));
                        $query .= ' AND juser.usertype = '.$db->Quote($filter_group);
                }
            }
        }

        if ($filter_group_k2) {
            $query .= ' AND k2user.group = '.$db->Quote($filter_group_k2);
        }

        if ($search !== '' && $search !== '0') {
            $escaped = K2_JVERSION == '15' ? $db->getEscaped($search, true) : $db->escape($search, true);
            $query .= ' AND (LOWER( juser.name ) LIKE '.$db->Quote('%'.$escaped.'%', false).' OR LOWER( juser.email ) LIKE '.$db->Quote('%'.$escaped.'%', false).')';
        }

        $db->setQuery($query);
        $total = $db->loadResult();

        return $total;
    }

    public function remove()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $cid = JRequest::getVar('cid');
        JArrayHelper::toInteger($cid);
        $db = Joomla\CMS\Factory::getDbo();
        $query = 'DELETE FROM #__k2_users WHERE userID IN('.implode(',', $cid).')';
        $db->setQuery($query);
        $db->query();

        $cache = Joomla\CMS\Factory::getCache('com_k2');
        $cache->clean();

        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_USER_PROFILE_DELETED'));
        $app->redirect('index.php?option=com_k2&view=users');
    }

    public function getUserGroups($type = 'joomla')
    {
        $db = Joomla\CMS\Factory::getDbo();

        if ($type == 'joomla') {
            $query = 'SELECT (lft - 3) AS lft, name AS value, name AS text FROM #__core_acl_aro_groups'.' WHERE name != "ROOT"'.' AND name != "USERS"'.' ORDER BY `lft` ASC';

            if (K2_JVERSION != '15') {
                $query = 'SELECT a.lft AS lft, a.id AS value, a.title AS text, COUNT(DISTINCT b.id) AS level
                    FROM #__usergroups AS a
                    LEFT JOIN #__usergroups AS b ON a.lft > b.lft AND a.rgt < b.rgt
                    GROUP BY a.id
                    ORDER BY a.lft ASC';
            }

            $db->setQuery($query);
            $groups = $db->loadObjectList();
            $userGroups = [];

            foreach ($groups as $group) {
                if ($group->lft >= 10) {
                    $group->lft = (int) $group->lft - 10;
                }

                if (K2_JVERSION != '15') {
                    $group->text = $this->indent($group->level, '- ').$group->text;
                } else {
                    $group->text = $this->indent($group->lft).$group->text;
                }

                $userGroups[] = $group;
            }
        } else {
            $query = 'SELECT * FROM #__k2_user_groups';
            $db->setQuery($query);
            $userGroups = $db->loadObjectList();
        }

        return $userGroups;
    }

    public function indent($times, $char = '&nbsp;&nbsp;&nbsp;&nbsp;', $start_char = '', $end_char = '')
    {
        $return = $start_char;
        for ($i = 0; $i < $times; $i++) {
            $return .= $char;
        }

        $return .= $end_char;

        return $return;
    }

    public function checkLogin($id)
    {
        $db = Joomla\CMS\Factory::getDbo();
        $query = 'SELECT COUNT(s.userid) FROM #__session AS s WHERE s.userid = '.(int) $id;
        $db->setQuery($query);
        $result = $db->loadResult();

        return $result;
    }

    public function hasProfile($id)
    {
        $db = Joomla\CMS\Factory::getDbo();
        $query = 'SELECT id FROM #__k2_users WHERE userID = '.(int) $id;
        $db->setQuery($query);
        $result = $db->loadResult();

        return $result;
    }

    public function enable()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $cid = JRequest::getVar('cid');
        JArrayHelper::toInteger($cid);
        $db = Joomla\CMS\Factory::getDbo();
        $query = 'UPDATE #__users SET block=0 WHERE id IN('.implode(',', $cid).')';
        $db->setQuery($query);
        $db->query();

        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_USERS_ENABLED'));
        if (JRequest::getCmd('context') == 'modalselector') {
            $app->redirect('index.php?option=com_k2&view=users&tmpl=component&context=modalselector');
        } else {
            $app->redirect('index.php?option=com_k2&view=users');
        }
    }

    public function disable()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $cid = JRequest::getVar('cid');
        JArrayHelper::toInteger($cid);
        $db = Joomla\CMS\Factory::getDbo();
        $query = 'UPDATE #__users SET block=1 WHERE id IN('.implode(',', $cid).')';
        $db->setQuery($query);
        $db->query();

        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_USERS_DISABLED'));
        if (JRequest::getCmd('context') == 'modalselector') {
            $app->redirect('index.php?option=com_k2&view=users&tmpl=component&context=modalselector');
        } else {
            $app->redirect('index.php?option=com_k2&view=users');
        }
    }

    public function delete()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $user = Joomla\CMS\Factory::getUser();
        $cid = JRequest::getVar('cid');
        JArrayHelper::toInteger($cid);
        $db = Joomla\CMS\Factory::getDbo();
        if (in_array($user->id, $cid)) {
            foreach ($cid as $key => $id) {
                if ($id == $user->id) {
                    unset($cid[$key]);
                }
            }

            $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_YOU_CANNOT_DELETE_YOURSELF'), 'notice');
        }

        if (count($cid) < 1) {
            $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_DELETE_COMPLETED'));
            $app->redirect('index.php?option=com_k2&view=users');
        }

        if (K2_JVERSION != '15') {
            Joomla\CMS\Plugin\PluginHelper::importPlugin('user');
            $dispatcher = JDispatcher::getInstance();
            $iAmSuperAdmin = $user->authorise('core.admin');
            foreach ($cid as $key => $id) {
                $table = Joomla\CMS\Table\Table::getInstance('user');
                $table->load($id);
                $allow = $user->authorise('core.delete', 'com_users');
                // Don't allow non-super-admin to delete a super admin
                $allow = (!$iAmSuperAdmin && Joomla\CMS\Access\Access::check($id, 'core.admin')) ? false : $allow;
                if ($allow) {
                    // Get users data for the users to delete.
                    $user_to_delete = Joomla\CMS\Factory::getUser($id);
                    // Fire the onUserBeforeDelete event.
                    $dispatcher->trigger('onUserBeforeDelete', [$table->getProperties()]);
                    if (!$table->delete($id)) {
                        $this->setError($table->getError());

                        return false;
                    }

                    // Trigger the onUserAfterDelete event.
                    $dispatcher->trigger('onUserAfterDelete', [$user_to_delete->getProperties(), true, $this->getError()]);
                } else {
                    // Prune items that you can't change.
                    unset($cid[$key]);
                    JError::raiseWarning(403, Joomla\CMS\Language\Text::_('JERROR_CORE_DELETE_NOT_PERMITTED'));
                }
            }

            $IDsToDelete = $cid;
        } else {
            $query = 'SELECT * FROM #__users WHERE id IN('.implode(',', $cid).(') AND gid<='.$user->gid);
            $db->setQuery($query);
            $IDsToDelete = K2_JVERSION == '30' ? $db->loadColumn() : $db->loadResultArray();

            $query = 'DELETE FROM #__users WHERE id IN('.implode(',', $IDsToDelete).(') AND id!='.$user->id);
            $db->setQuery($query);
            $db->query();
        }

        $query = 'DELETE FROM #__k2_users WHERE userID IN('.implode(',', $IDsToDelete).(') AND userID!='.$user->id);
        $db->setQuery($query);
        $db->query();

        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_DELETE_COMPLETED'));
        $app->redirect('index.php?option=com_k2&view=users');

        return null;
    }

    public function saveMove()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $db = Joomla\CMS\Factory::getDbo();
        $cid = JRequest::getVar('cid');
        JArrayHelper::toInteger($cid);
        $group = JRequest::getVar('group');
        $k2group = JRequest::getInt('k2group');
        if (K2_JVERSION != '15') {
            JArrayHelper::toInteger($group);
            $group = array_filter($group);
            if ($group !== []) {
                foreach ($cid as $id) {
                    $query = 'DELETE FROM #__user_usergroup_map WHERE user_id = '.$id;
                    $db->setQuery($query);
                    $db->query();
                    $query = 'INSERT INTO #__user_usergroup_map VALUES ('.$id.', '.implode('), ('.$id.', ', $group).')';
                    $db->setQuery($query);
                    $db->query();
                }
            }
        } elseif ($group) {
            $query = 'SELECT id FROM #__core_acl_aro_groups WHERE name='.$db->Quote($group);
            $db->setQuery($query);
            $gid = $db->loadResult();
            $query = sprintf('UPDATE #__users SET gid=%s, usertype=', $gid).$db->Quote($group).' WHERE id IN('.implode(',', $cid).')';
            $db->setQuery($query);
            $db->query();
        }

        if ($k2group) {
            foreach ($cid as $id) {
                $query = 'SELECT COUNT(*) FROM #__k2_users WHERE userID = '.$id;
                $db->setQuery($query);
                $result = $db->loadResult();
                if ($result) {
                    $query = sprintf('UPDATE #__k2_users SET `group`=%s WHERE userID = ', $k2group).$id;
                } else {
                    $user = Joomla\CMS\Factory::getUser($id);
                    $query = sprintf("INSERT INTO #__k2_users VALUES ('', %s, %s, '', '', '', '', %s, '', '', '', '')", $id, $db->Quote($user->username), $k2group);
                }

                $db->setQuery($query);
                $db->query();
            }
        }

        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_MOVE_COMPLETED'));
        $app->redirect('index.php?option=com_k2&view=users');
    }

    public function import()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $db = Joomla\CMS\Factory::getDbo();
        if (K2_JVERSION != '15') {
            $db->setQuery('SELECT id, title AS name FROM #__usergroups');
            $usergroups = $db->loadObjectList();
            $xml = new JXMLElement(Joomla\CMS\Filesystem\File::read(JPATH_COMPONENT.'/models/usergroup.xml'));
            $permissions = class_exists('JParameter') ? new JParameter('') : new JRegistry('');
            foreach ($xml->params as $paramGroup) {
                foreach ($paramGroup->param as $param) {
                    $attribute = K2_JVERSION == '30' ? $param->attributes()->type : $param->getAttribute('type');
                    if ($attribute != 'spacer') {
                        if (K2_JVERSION == '30') {
                            $permissions->set((string) $param->attributes()->name, (string) $param->attributes()->default);
                        } else {
                            $permissions->set($param->getAttribute('name'), $param->getAttribute('default'));
                        }
                    }
                }
            }
        } else {
            $acl = Joomla\CMS\Factory::getACL();
            $frontEndGroups = $acl->_getBelow('#__core_acl_aro_groups', 'g1.id, g1.name, COUNT(g2.name) AS level', 'g1.name', false, 'Public Frontend', false);
            $backEndGroups = $acl->_getBelow('#__core_acl_aro_groups', 'g1.id, g1.name, COUNT(g2.name) AS level', 'g1.name', false, 'Public Backend', false);
            $usergroups = array_merge($frontEndGroups, $backEndGroups);

            $xml = new JSimpleXML();
            $xml->loadFile(JPATH_COMPONENT.'/models/usergroup.xml');
            $permissions = class_exists('JParameter') ? new JParameter('') : new JRegistry('');
            foreach ($xml->document->params as $paramGroup) {
                foreach ($paramGroup->param as $param) {
                    if ($param->attributes('type') != 'spacer') {
                        $permissions->set($param->attributes('name'), $param->attributes('default'));
                    }
                }
            }
        }

        $permissions->set('inheritance', 0);
        $permissions->set('categories', 'all');
        $permissions = $permissions->toString();

        foreach ($usergroups as $usergroup) {
            $K2UserGroup = Joomla\CMS\Table\Table::getInstance('K2UserGroup', 'Table');
            $K2UserGroup->name = JString::trim($usergroup->name).' (Imported from Joomla)';
            $K2UserGroup->permissions = $permissions;
            $K2UserGroup->store();

            if (K2_JVERSION != '15') {
                $query = 'SELECT * FROM #__users AS user JOIN #__user_usergroup_map AS map ON user.id = map.user_id
                WHERE map.group_id = '.$usergroup->id;
            } else {
                $query = 'SELECT * FROM #__users WHERE gid='.$usergroup->id;
            }

            $db->setQuery($query);
            $users = $db->loadObjectList();

            foreach ($users as $user) {
                $query = 'SELECT COUNT(*) FROM #__k2_users WHERE userID='.$user->id;
                $db->setQuery($query);
                $result = $db->loadResult();
                if (!$result) {
                    $K2User = Joomla\CMS\Table\Table::getInstance('K2User', 'Table');
                    $K2User->userID = $user->id;
                    $K2User->group = $K2UserGroup->id;
                    $K2User->store();
                }
            }
        }

        $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_IMPORT_COMPLETED'));
        $app->redirect('index.php?option=com_k2&view=users');
    }
}

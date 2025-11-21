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
require_once JPATH_SITE.'/media/k2/assets/vendors/cascade/calendar/calendar.php';

class modK2ToolsHelper
{
    public static $paths = [];

    public static function getAuthors(&$params)
    {
        $app = Joomla\CMS\Factory::getApplication();
        $componentParams = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        $where = '';
        $cid = $params->get('authors_module_category');
        if ($cid > 0) {
            $categories = self::getCategoryChildren($cid);
            $categories[] = $cid;
            JArrayHelper::toInteger($categories);
            $where = ' catid IN('.implode(',', $categories).') AND ';
        }

        $user = Joomla\CMS\Factory::getUser();
        $aid = (int) $user->get('aid');
        $db = Joomla\CMS\Factory::getDbo();

        $jnow = Joomla\CMS\Factory::getDate();
        $now = K2_JVERSION == '15' ? $jnow->toMySQL() : $jnow->toSql();
        $nullDate = $db->getNullDate();

        if (K2_JVERSION != '15') {
            $languageCheck = '';
            if ($app->getLanguageFilter()) {
                $languageTag = Joomla\CMS\Factory::getLanguage()->getTag();
                $languageCheck = 'AND language IN ('.$db->Quote($languageTag).', '.$db->Quote('*').')';
            }

            $query = "SELECT created_by
                FROM #__k2_items
                WHERE {$where} published=1
                    AND ( publish_up = ".$db->Quote($nullDate).' OR publish_up <= '.$db->Quote($now).' )
                    AND ( publish_down = '.$db->Quote($nullDate).' OR publish_down >= '.$db->Quote($now).' )
                    AND trash=0
                    AND access IN('.implode(',', $user->getAuthorisedViewLevels()).")
                    AND created_by_alias=''
                    {$languageCheck}
                    AND EXISTS (SELECT * FROM #__k2_categories WHERE id= #__k2_items.catid AND published=1 AND trash=0 AND access IN(".implode(',', $user->getAuthorisedViewLevels()).") {$languageCheck})
                GROUP BY created_by";
        } else {
            $query = "SELECT created_by
                FROM #__k2_items
                WHERE {$where} published=1
                    AND ( publish_up = ".$db->Quote($nullDate).' OR publish_up <= '.$db->Quote($now).' )
                    AND ( publish_down = '.$db->Quote($nullDate).' OR publish_down >= '.$db->Quote($now)." )
                    AND trash=0
                    AND access<={$aid}
                    AND created_by_alias=''
                    AND EXISTS (SELECT * FROM #__k2_categories WHERE id= #__k2_items.catid AND published=1 AND trash=0 AND access<={$aid})
                GROUP BY created_by";
        }

        $db->setQuery($query);
        $rows = $db->loadObjectList();

        $authors = [];
        if (count($rows) > 0) {
            foreach ($rows as $row) {
                $author = Joomla\CMS\Factory::getUser($row->created_by);
                $author->link = Joomla\CMS\Router\Route::_(K2HelperRoute::getUserRoute($author->id));

                $query = 'SELECT id, gender, description, image, url, `group`, plugins FROM #__k2_users WHERE userID='.(int) $author->id;
                $db->setQuery($query);
                $author->profile = $db->loadObject();

                if ($params->get('authorAvatar')) {
                    $author->avatar = K2HelperUtilities::getAvatar($author->id, $author->email, $componentParams->get('userImageWidth'));
                }

                if (K2_JVERSION != '15') {
                    $languageCheck = '';
                    if ($app->getLanguageFilter()) {
                        $languageTag = Joomla\CMS\Factory::getLanguage()->getTag();
                        $languageCheck = 'AND i.language IN ('.$db->Quote($languageTag).', '.$db->Quote('*').') AND c.language IN ('.$db->Quote($languageTag).', '.$db->Quote('*').')';
                    }

                    $query = 'SELECT i.*, c.alias as categoryalias FROM #__k2_items as i
                    LEFT JOIN #__k2_categories c ON c.id = i.catid
                    WHERE i.created_by = '.(int) $author->id.'
                    AND i.published = 1
                    AND i.access IN('.implode(',', $user->getAuthorisedViewLevels()).')
                    AND ( i.publish_up = '.$db->Quote($nullDate).' OR i.publish_up <= '.$db->Quote($now).' )
                    AND ( i.publish_down = '.$db->Quote($nullDate).' OR i.publish_down >= '.$db->Quote($now)." )
                    AND i.trash = 0 AND created_by_alias='' AND c.published = 1 AND c.access IN(".implode(',', $user->getAuthorisedViewLevels()).sprintf(') AND c.trash = 0 %s ORDER BY created DESC', $languageCheck);
                } else {
                    $query = 'SELECT i.*, c.alias as categoryalias FROM #__k2_items as i
                    LEFT JOIN #__k2_categories c ON c.id = i.catid
                    WHERE i.created_by = '.(int) $author->id."
                    AND i.published = 1
                    AND i.access <= {$aid}
                    AND ( i.publish_up = ".$db->Quote($nullDate).' OR i.publish_up <= '.$db->Quote($now).' )
                    AND ( i.publish_down = '.$db->Quote($nullDate).' OR i.publish_down >= '.$db->Quote($now)." )
                    AND i.trash = 0 AND created_by_alias='' AND c.published = 1 AND c.access <= {$aid} AND c.trash = 0 ORDER BY created DESC";
                }

                $db->setQuery($query, 0, 1);
                $author->latest = $db->loadObject();
                $author->latest->id = (int) $author->latest->id;
                $author->latest->link = urldecode(Joomla\CMS\Router\Route::_(K2HelperRoute::getItemRoute($author->latest->id.':'.urlencode($author->latest->alias), $author->latest->catid.':'.urlencode($author->latest->categoryalias))));

                $query = 'SELECT COUNT(*) FROM #__k2_comments WHERE published=1 AND itemID='.$author->latest->id;
                $db->setQuery($query);
                $author->latest->numOfComments = $db->loadResult();

                if ($params->get('authorItemsCounter')) {
                    if (K2_JVERSION != '15') {
                        $languageCheck = '';
                        if ($app->getLanguageFilter()) {
                            $languageTag = Joomla\CMS\Factory::getLanguage()->getTag();
                            $languageCheck = 'AND language IN ('.$db->Quote($languageTag).', '.$db->Quote('*').')';
                        }

                        $query = sprintf('SELECT COUNT(*) FROM #__k2_items  WHERE %s published=1 AND ( publish_up = ', $where).$db->Quote($nullDate).' OR publish_up <= '.$db->Quote($now).' ) AND ( publish_down = '.$db->Quote($nullDate).' OR publish_down >= '.$db->Quote($now).' ) AND trash=0 AND access IN('.implode(',', $user->getAuthorisedViewLevels()).sprintf(") AND created_by_alias='' AND created_by=%s %s AND EXISTS (SELECT * FROM #__k2_categories WHERE id= #__k2_items.catid AND published=1 AND trash=0 AND access IN(", $row->created_by, $languageCheck).implode(',', $user->getAuthorisedViewLevels()).sprintf(') %s)', $languageCheck);
                    } else {
                        $query = sprintf('SELECT COUNT(*) FROM #__k2_items  WHERE %s published=1 AND ( publish_up = ', $where).$db->Quote($nullDate).' OR publish_up <= '.$db->Quote($now).' ) AND ( publish_down = '.$db->Quote($nullDate).' OR publish_down >= '.$db->Quote($now).sprintf(" ) AND trash=0 AND access<=%d AND created_by_alias='' AND created_by=%s AND EXISTS (SELECT * FROM #__k2_categories WHERE id= #__k2_items.catid AND published=1 AND trash=0 AND access<=%d )", $aid, $row->created_by, $aid);
                    }

                    $db->setQuery($query);
                    $numofitems = $db->loadResult();
                    $author->items = $numofitems;
                }

                $authors[] = $author;
            }
        }

        return $authors;
    }

    public static function getArchive(&$params)
    {
        $app = Joomla\CMS\Factory::getApplication();
        $user = Joomla\CMS\Factory::getUser();
        $aid = (int) $user->get('aid');
        $db = Joomla\CMS\Factory::getDbo();

        $jnow = Joomla\CMS\Factory::getDate();
        $now = K2_JVERSION == '15' ? $jnow->toMySQL() : $jnow->toSql();

        $nullDate = $db->getNullDate();

        $query = 'SELECT DISTINCT MONTH(created) as m, YEAR(created) as y FROM #__k2_items WHERE published=1 AND ( publish_up = '.$db->Quote($nullDate).' OR publish_up <= '.$db->Quote($now).' ) AND ( publish_down = '.$db->Quote($nullDate).' OR publish_down >= '.$db->Quote($now).' ) AND trash=0';
        if (K2_JVERSION != '15') {
            $query .= ' AND access IN('.implode(',', $user->getAuthorisedViewLevels()).') ';
            if ($app->getLanguageFilter()) {
                $languageTag = Joomla\CMS\Factory::getLanguage()->getTag();
                $query .= ' AND language IN ('.$db->Quote($languageTag).', '.$db->Quote('*').') ';
            }
        } else {
            $query .= sprintf(' AND access<=%d ', $aid);
        }

        $catid = $params->get('archiveCategory', 0);
        if ($catid > 0) {
            $query .= ' AND catid='.(int) $catid;
        }

        $query .= ' ORDER BY created DESC';

        $db->setQuery($query, 0, 12);
        $rows = $db->loadObjectList();
        $months = [
            Joomla\CMS\Language\Text::_('K2_JANUARY'),
            Joomla\CMS\Language\Text::_('K2_FEBRUARY'),
            Joomla\CMS\Language\Text::_('K2_MARCH'),
            Joomla\CMS\Language\Text::_('K2_APRIL'),
            Joomla\CMS\Language\Text::_('K2_MAY'),
            Joomla\CMS\Language\Text::_('K2_JUNE'),
            Joomla\CMS\Language\Text::_('K2_JULY'),
            Joomla\CMS\Language\Text::_('K2_AUGUST'),
            Joomla\CMS\Language\Text::_('K2_SEPTEMBER'),
            Joomla\CMS\Language\Text::_('K2_OCTOBER'),
            Joomla\CMS\Language\Text::_('K2_NOVEMBER'),
            Joomla\CMS\Language\Text::_('K2_DECEMBER'),
        ];
        if (count($rows) > 0) {
            foreach ($rows as $row) {
                $row->numOfItems = $params->get('archiveItemsCounter') ? self::countArchiveItems($row->m, $row->y, $catid) : '';

                $row->name = $months[($row->m) - 1];

                if ($params->get('archiveCategory', 0) > 0) {
                    $row->link = Joomla\CMS\Router\Route::_(K2HelperRoute::getDateRoute($row->y, $row->m, null, $params->get('archiveCategory')));
                } else {
                    $row->link = Joomla\CMS\Router\Route::_(K2HelperRoute::getDateRoute($row->y, $row->m));
                }

                $archives[] = $row;
            }

            return $archives;
        }

        return null;
    }

    public static function tagCloud(&$params)
    {
        $app = Joomla\CMS\Factory::getApplication();
        $user = Joomla\CMS\Factory::getUser();
        $aid = (int) $user->get('aid');
        $db = Joomla\CMS\Factory::getDbo();

        $jnow = Joomla\CMS\Factory::getDate();
        $now = K2_JVERSION == '15' ? $jnow->toMySQL() : $jnow->toSql();

        $nullDate = $db->getNullDate();

        $query = 'SELECT i.id FROM #__k2_items as i';
        $query .= ' LEFT JOIN #__k2_categories c ON c.id = i.catid';
        $query .= ' WHERE i.published=1 ';
        $query .= ' AND ( i.publish_up = '.$db->Quote($nullDate).' OR i.publish_up <= '.$db->Quote($now).' ) ';
        $query .= ' AND ( i.publish_down = '.$db->Quote($nullDate).' OR i.publish_down >= '.$db->Quote($now).' )';
        $query .= ' AND i.trash=0 ';
        if (K2_JVERSION != '15') {
            $query .= ' AND i.access IN('.implode(',', $user->getAuthorisedViewLevels()).') ';
        } else {
            $query .= sprintf(' AND i.access <= %d ', $aid);
        }

        $query .= ' AND c.published=1 ';
        $query .= ' AND c.trash=0 ';
        if (K2_JVERSION != '15') {
            $query .= ' AND c.access IN('.implode(',', $user->getAuthorisedViewLevels()).') ';
        } else {
            $query .= sprintf(' AND c.access <= %d ', $aid);
        }

        $cloudCategory = $params->get('cloud_category');
        if (is_array($cloudCategory)) {
            $cloudCategory = array_filter($cloudCategory);
        }

        if ($cloudCategory) {
            if (!is_array($cloudCategory)) {
                $cloudCategory = (array) $cloudCategory;
            }

            foreach ($cloudCategory as $cloudCategoryID) {
                $categories[] = $cloudCategoryID;
                if ($params->get('cloud_category_recursive')) {
                    $children = self::getCategoryChildren($cloudCategoryID);
                    $categories = @array_merge($categories, $children);
                }
            }

            $categories = @array_unique($categories);
            JArrayHelper::toInteger($categories);
            if (count($categories) == 1) {
                $query .= ' AND i.catid='.$categories[0];
            } else {
                $query .= ' AND i.catid IN('.implode(',', $categories).')';
            }
        }

        if (K2_JVERSION != '15' && $app->getLanguageFilter()) {
            $languageTag = Joomla\CMS\Factory::getLanguage()->getTag();
            $query .= ' AND c.language IN ('.$db->Quote($languageTag).', '.$db->Quote('*').') AND i.language IN ('.$db->Quote($languageTag).', '.$db->Quote('*').') ';
        }

        $db->setQuery($query);
        $IDs = K2_JVERSION == '30' ? $db->loadColumn() : $db->loadResultArray();

        if (!is_array($IDs) || $IDs === []) {
            return [];
        }

        $query = 'SELECT tag.name, tag.id
            FROM #__k2_tags as tag
            LEFT JOIN #__k2_tags_xref AS xref ON xref.tagID = tag.id
            WHERE xref.itemID IN ('.implode(',', $IDs).')
                AND tag.published = 1';
        $db->setQuery($query);
        $rows = $db->loadObjectList();

        $cloud = [];
        if (count($rows) > 0) {
            foreach ($rows as $row) {
                if (@array_key_exists($row->name, $cloud)) {
                    $cloud[$row->name]++;
                } else {
                    $cloud[$row->name] = 1;
                }
            }

            $max_size = $params->get('max_size');
            $min_size = $params->get('min_size');
            $max_qty = max(array_values($cloud));
            $min_qty = min(array_values($cloud));
            $spread = $max_qty - $min_qty;
            if (0 == $spread) {
                $spread = 1;
            }

            $step = ($max_size - $min_size) / ($spread);

            $counter = 0;
            arsort($cloud, SORT_NUMERIC);
            $cloud = @array_slice($cloud, 0, $params->get('cloud_limit'), true);
            uksort($cloud, 'strnatcasecmp');

            foreach ($cloud as $key => $value) {
                $size = $min_size + (($value - $min_qty) * $step);
                $size = ceil($size);
                $tmp = new stdClass();
                $tmp->tag = $key;
                $tmp->count = $value;
                $tmp->size = $size;
                $tmp->link = urldecode(Joomla\CMS\Router\Route::_(K2HelperRoute::getTagRoute($key)));
                $tags[$counter] = $tmp;
                $counter++;
            }

            return $tags;
        }

        return null;
    }

    public static function getSearchCategoryFilter(&$params)
    {
        $result = '';
        $cid = $params->get('category_id', null);
        if ($params->get('catfilter') && !is_null($cid)) {
            if (is_array($cid)) {
                if ($params->get('getChildren')) {
                    $itemListModel = K2Model::getInstance('Itemlist', 'K2Model');
                    $categories = $itemListModel->getCategoryTree($cid);
                    $result = @implode(',', $categories);
                } else {
                    JArrayHelper::toInteger($cid);
                    $result = implode(',', $cid);
                }
            } elseif ($params->get('getChildren')) {
                $itemListModel = K2Model::getInstance('Itemlist', 'K2Model');
                $categories = $itemListModel->getCategoryTree($cid);
                $result = @implode(',', $categories);
            } else {
                $result = (int) $cid;
            }
        }

        return $result;
    }

    public static function hasChildren($id)
    {
        $app = Joomla\CMS\Factory::getApplication();
        $user = Joomla\CMS\Factory::getUser();
        $aid = (int) $user->get('aid');
        $id = (int) $id;
        $db = Joomla\CMS\Factory::getDbo();
        $query = sprintf('SELECT * FROM #__k2_categories  WHERE parent=%d AND published=1 AND trash=0 ', $id);
        if (K2_JVERSION != '15') {
            $query .= ' AND access IN('.implode(',', $user->getAuthorisedViewLevels()).') ';
            if ($app->getLanguageFilter()) {
                $languageTag = Joomla\CMS\Factory::getLanguage()->getTag();
                $query .= ' AND language IN ('.$db->Quote($languageTag).', '.$db->Quote('*').') ';
            }
        } else {
            $query .= ' AND access <= '.$aid;
        }

        $db->setQuery($query);
        $rows = $db->loadObjectList();
        if ($db->getErrorNum()) {
            echo $db->stderr();

            return false;
        }

        return (bool) (count($rows));
    }

    public static function treerecurse(&$params, $id = 0, $level = 0, $begin = false)
    {
        static $output;
        if ($begin) {
            $output = '';
        }

        $app = Joomla\CMS\Factory::getApplication();
        $root_id = (int) $params->get('root_id');
        $end_level = $params->get('end_level', null);
        $id = (int) $id;
        $catid = K2Request::getInt('id');
        $option = K2Request::getCmd('option');
        $view = K2Request::getCmd('view');

        $user = Joomla\CMS\Factory::getUser();
        $aid = (int) $user->get('aid');
        $db = Joomla\CMS\Factory::getDbo();

        switch ($params->get('categoriesListOrdering')) {
            case 'alpha':
                $orderby = 'name';
                break;

            case 'ralpha':
                $orderby = 'name DESC';
                break;

            case 'order':
                $orderby = 'ordering';
                break;

            case 'reversedefault':
                $orderby = 'id DESC';
                break;

            default:
                $orderby = 'id ASC';
                break;
        }

        if (($root_id != 0) && ($level == 0)) {
            $query = sprintf('SELECT * FROM #__k2_categories WHERE parent=%s AND published=1 AND trash=0 ', $root_id);
        } else {
            $query = sprintf('SELECT * FROM #__k2_categories WHERE parent=%d AND published=1 AND trash=0 ', $id);
        }

        if (K2_JVERSION != '15') {
            $query .= ' AND access IN('.implode(',', $user->getAuthorisedViewLevels()).') ';
            if ($app->getLanguageFilter()) {
                $languageTag = Joomla\CMS\Factory::getLanguage()->getTag();
                $query .= ' AND language IN ('.$db->Quote($languageTag).', '.$db->Quote('*').') ';
            }
        } else {
            $query .= ' AND access <= '.$aid;
        }

        $query .= ' ORDER BY '.$orderby;

        $db->setQuery($query);
        $rows = $db->loadObjectList();
        if ($db->getErrorNum()) {
            echo $db->stderr();

            return false;
        }

        if ($level < intval($end_level) || is_null($end_level)) {
            $output .= '<ul class="level'.$level.'">';
            foreach ($rows as $row) {
                $row->numOfItems = $params->get('categoriesListItemsCounter') ? ' ('.self::countCategoryItems($row->id).')' : '';

                $active = $option == 'com_k2' && $view == 'itemlist' && $catid == $row->id ? ' class="activeCategory"' : '';

                if (self::hasChildren($row->id)) {
                    $output .= '<li'.$active.'><a href="'.urldecode(Joomla\CMS\Router\Route::_(K2HelperRoute::getCategoryRoute($row->id.':'.urlencode($row->alias)))).'"><span class="catTitle">'.$row->name.'</span><span class="catCounter">'.$row->numOfItems.'</span></a>';
                    self::treerecurse($params, $row->id, $level + 1);
                    $output .= '</li>';
                } else {
                    $output .= '<li'.$active.'><a href="'.urldecode(Joomla\CMS\Router\Route::_(K2HelperRoute::getCategoryRoute($row->id.':'.urlencode($row->alias)))).'"><span class="catTitle">'.$row->name.'</span><span class="catCounter">'.$row->numOfItems.'</span></a></li>';
                }
            }

            $output .= '</ul>';
        }

        return $output;
    }

    public static function treeselectbox(&$params, $id = 0, $level = 0)
    {
        $app = Joomla\CMS\Factory::getApplication();
        $root_id = (int) $params->get('root_id2');
        $option = K2Request::getCmd('option');
        $view = K2Request::getCmd('view');
        $category = K2Request::getInt('id');
        $id = (int) $id;
        $user = Joomla\CMS\Factory::getUser();
        $aid = (int) $user->get('aid');
        $db = Joomla\CMS\Factory::getDbo();
        if (($root_id != 0) && ($level == 0)) {
            $query = sprintf('SELECT * FROM #__k2_categories WHERE parent=%s AND published=1 AND trash=0 ', $root_id);
        } else {
            $query = sprintf('SELECT * FROM #__k2_categories WHERE parent=%d AND published=1 AND trash=0 ', $id);
        }

        if (K2_JVERSION != '15') {
            $query .= ' AND access IN('.implode(',', $user->getAuthorisedViewLevels()).') ';
            if ($app->getLanguageFilter()) {
                $languageTag = Joomla\CMS\Factory::getLanguage()->getTag();
                $query .= ' AND language IN ('.$db->Quote($languageTag).', '.$db->Quote('*').') ';
            }
        } else {
            $query .= ' AND access <= '.$aid;
        }

        $query .= ' ORDER BY ordering';

        $db->setQuery($query);
        $rows = $db->loadObjectList();
        if ($db->getErrorNum()) {
            echo $db->stderr();

            return false;
        }

        if ($level == 0) {
            echo '
<div class="k2CategorySelectBlock '.$params->get('moduleclass_sfx').'">
    <form action="'.Joomla\CMS\Router\Route::_('index.php').'" method="get">
        <select name="category" onchange="window.location=this.form.category.value;">
            <option value="'.Joomla\CMS\Uri\Uri::base(true).'/">'.Joomla\CMS\Language\Text::_('K2_SELECT_CATEGORY').'</option>
            ';
        }

        $indent = '';
        for ($i = 0; $i < $level; $i++) {
            $indent .= '&ndash; ';
        }

        foreach ($rows as $row) {
            $selected = $option == 'com_k2' && $category == $row->id ? ' selected="selected"' : '';

            if (self::hasChildren($row->id)) {
                echo '<option value="'.urldecode(Joomla\CMS\Router\Route::_(K2HelperRoute::getCategoryRoute($row->id.':'.urlencode($row->alias)))).'"'.$selected.'>'.$indent.$row->name.'</option>';
                self::treeselectbox($params, $row->id, $level + 1);
            } else {
                echo '<option value="'.urldecode(Joomla\CMS\Router\Route::_(K2HelperRoute::getCategoryRoute($row->id.':'.urlencode($row->alias)))).'"'.$selected.'>'.$indent.$row->name.'</option>';
            }
        }

        if ($level == 0) {
            echo '
            </select>
            <input name="option" value="com_k2" type="hidden" />
            <input name="view" value="itemlist" type="hidden" />
            <input name="task" value="category" type="hidden" />
            <input name="Itemid" value="'.K2Request::getInt('Itemid').'" type="hidden" />';

            // For Joom!Fish compatibility
            if (K2Request::getCmd('lang')) {
                echo '<input name="lang" value="'.K2Request::getCmd('lang').'" type="hidden" />';
            }

            echo '
    </form>
</div>
            ';
        }

        return null;
    }

    public static function breadcrumbs($params)
    {
        $app = Joomla\CMS\Factory::getApplication();
        $array = [];
        $view = K2Request::getCmd('view');
        $id = K2Request::getInt('id');
        $option = K2Request::getCmd('option');
        $task = K2Request::getCmd('task');

        $db = Joomla\CMS\Factory::getDbo();
        $user = Joomla\CMS\Factory::getUser();
        $aid = (int) $user->get('aid');

        $menu = $app->getMenu();
        $active = $menu->getActive();

        if ($option == 'com_k2') {
            switch ($view) {
                case 'item':
                    if (K2_JVERSION != '15') {
                        $languageCheck = '';
                        if ($app->getLanguageFilter()) {
                            $languageTag = Joomla\CMS\Factory::getLanguage()->getTag();
                            $languageCheck = ' AND language IN ('.$db->Quote($languageTag).', '.$db->Quote('*').') ';
                        }

                        $query = sprintf('SELECT * FROM #__k2_items  WHERE id=%s AND published=1 AND trash=0 AND access IN(', $id).implode(',', $user->getAuthorisedViewLevels()).sprintf(') %s AND EXISTS (SELECT * FROM #__k2_categories WHERE #__k2_categories.id= #__k2_items.catid AND published=1 AND access IN(', $languageCheck).implode(',', $user->getAuthorisedViewLevels()).sprintf(') %s)', $languageCheck);
                    } else {
                        $query = sprintf('SELECT * FROM #__k2_items  WHERE id=%s AND published=1 AND trash=0 AND access<=%d AND EXISTS (SELECT * FROM #__k2_categories WHERE #__k2_categories.id= #__k2_items.catid AND published=1 AND access<=%d)', $id, $aid, $aid);
                    }

                    $db->setQuery($query);
                    $row = $db->loadObject();
                    if ($db->getErrorNum()) {
                        echo $db->stderr();

                        return false;
                    }

                    $matchItem = !is_null($active) && @$active->query['view'] == 'item' && @$active->query['id'] == $id;
                    $matchCategory = !is_null($active) && @$active->query['view'] == 'itemlist' && @$active->query['task'] == 'category' && @$active->query['id'] == $row->catid;

                    if ($matchItem || $matchCategory) {
                        $title = ($matchCategory) ? $row->title : '';
                        $path = self::getSitePath();

                        return [$path, $title];
                    }

                    $title = $row->title;
                    $path = self::getCategoryPath($row->catid);

                    break;

                case 'itemlist':
                    if ($task == 'category') {
                        $match = !is_null($active) && @$active->query['view'] == 'itemlist' && @$active->query['task'] == 'category' && @$active->query['id'] == $id;
                        if ($match) {
                            $title = '';
                            $path = self::getSitePath();

                            return [$path, $title];
                        }

                        $query = sprintf('SELECT * FROM #__k2_categories  WHERE id=%s AND published=1 AND trash=0 ', $id);
                        if (K2_JVERSION != '15') {
                            $query .= ' AND access IN('.implode(',', $user->getAuthorisedViewLevels()).') ';
                            if ($app->getLanguageFilter()) {
                                $languageTag = Joomla\CMS\Factory::getLanguage()->getTag();
                                $query .= ' AND language IN ('.$db->Quote($languageTag).', '.$db->Quote('*').') ';
                            }
                        } else {
                            $query .= ' AND access <= '.$aid;
                        }

                        $db->setQuery($query);
                        $row = $db->loadObject();
                        if ($db->getErrorNum()) {
                            echo $db->stderr();

                            return false;
                        }

                        $title = $row->name;
                        $path = self::getCategoryPath($row->parent);
                    } else {
                        $document = Joomla\CMS\Factory::getDocument();
                        $title = $document->getTitle();
                        $path = self::getSitePath();
                    }

                    break;

                case 'latest':
                    $document = Joomla\CMS\Factory::getDocument();
                    $title = $document->getTitle();
                    $path = self::getSitePath();
                    break;
            }
        } else {
            $document = Joomla\CMS\Factory::getDocument();
            $title = $document->getTitle();
            $path = self::getSitePath();
        }

        return [
            $path,
            $title,
        ];
    }

    public static function getSitePath()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $pathway = $app->getPathway();
        $items = $pathway->getPathway();
        $count = count($items);
        $path = [];
        for ($i = 0; $i < $count; $i++) {
            if (!empty($items[$i]->link)) {
                $items[$i]->name = stripslashes(htmlspecialchars($items[$i]->name, ENT_QUOTES, 'UTF-8'));
                $items[$i]->link = Joomla\CMS\Router\Route::_($items[$i]->link);
                $path[] = '<a href="'.Joomla\CMS\Router\Route::_($items[$i]->link).'">'.$items[$i]->name.'</a>';
            }
        }

        return $path;
    }

    public static function getCategoryPath($catid, &$array = [])
    {
        if (isset(self::$paths[$catid])) {
            return self::$paths[$catid];
        }

        $app = Joomla\CMS\Factory::getApplication();
        $user = Joomla\CMS\Factory::getUser();
        $aid = (int) $user->get('aid');
        $catid = (int) $catid;
        $db = Joomla\CMS\Factory::getDbo();
        $query = sprintf('SELECT * FROM #__k2_categories WHERE id=%d AND published=1 AND trash=0 ', $catid);

        if (K2_JVERSION != '15') {
            $query .= ' AND access IN('.implode(',', $user->getAuthorisedViewLevels()).') ';
            if ($app->getLanguageFilter()) {
                $languageTag = Joomla\CMS\Factory::getLanguage()->getTag();
                $query .= ' AND language IN ('.$db->Quote($languageTag).', '.$db->Quote('*').') ';
            }
        } else {
            $query .= ' AND access <= '.$aid;
        }

        $db->setQuery($query);
        $rows = $db->loadObjectList();
        if ($db->getErrorNum()) {
            echo $db->stderr();

            return false;
        }

        foreach ($rows as $row) {
            $array[] = '<a href="'.urldecode(Joomla\CMS\Router\Route::_(K2HelperRoute::getCategoryRoute($row->id.':'.urlencode($row->alias)))).'">'.$row->name.'</a>';
            self::getCategoryPath($row->parent, $array);
        }

        $return = array_reverse($array);
        self::$paths[$catid] = $return;

        return $return;
    }

    public static function getCategoryChildren($catid)
    {
        static $array = [];
        $app = Joomla\CMS\Factory::getApplication();
        $user = Joomla\CMS\Factory::getUser();
        $aid = (int) $user->get('aid');
        $catid = (int) $catid;
        $db = Joomla\CMS\Factory::getDbo();
        $query = sprintf('SELECT * FROM #__k2_categories WHERE parent=%d AND published=1 AND trash=0 ', $catid);
        if (K2_JVERSION != '15') {
            $query .= ' AND access IN('.implode(',', $user->getAuthorisedViewLevels()).') ';
            if ($app->getLanguageFilter()) {
                $languageTag = Joomla\CMS\Factory::getLanguage()->getTag();
                $query .= ' AND language IN ('.$db->Quote($languageTag).', '.$db->Quote('*').') ';
            }
        } else {
            $query .= ' AND access <= '.$aid;
        }

        $query .= ' ORDER BY ordering ';

        $db->setQuery($query);
        $rows = $db->loadObjectList();
        if ($db->getErrorNum()) {
            echo $db->stderr();

            return false;
        }

        foreach ($rows as $row) {
            $array[] = $row->id;
            if (self::hasChildren($row->id)) {
                self::getCategoryChildren($row->id);
            }
        }

        return $array;
    }

    public static function countArchiveItems($month, $year, $catid = 0)
    {
        $app = Joomla\CMS\Factory::getApplication();
        $user = Joomla\CMS\Factory::getUser();
        $aid = (int) $user->get('aid');
        $month = (int) $month;
        $year = (int) $year;
        $db = Joomla\CMS\Factory::getDbo();

        $jnow = Joomla\CMS\Factory::getDate();
        $now = K2_JVERSION == '15' ? $jnow->toMySQL() : $jnow->toSql();

        $nullDate = $db->getNullDate();

        $query = sprintf('SELECT COUNT(*) FROM #__k2_items WHERE MONTH(created)=%d AND YEAR(created)=%d AND published=1 AND ( publish_up = ', $month, $year).$db->Quote($nullDate).' OR publish_up <= '.$db->Quote($now).' ) AND ( publish_down = '.$db->Quote($nullDate).' OR publish_down >= '.$db->Quote($now).' ) AND trash=0 ';
        if (K2_JVERSION != '15') {
            $query .= ' AND access IN('.implode(',', $user->getAuthorisedViewLevels()).') ';
            if ($app->getLanguageFilter()) {
                $languageTag = Joomla\CMS\Factory::getLanguage()->getTag();
                $query .= ' AND language IN ('.$db->Quote($languageTag).', '.$db->Quote('*').') ';
            }
        } else {
            $query .= ' AND access <= '.$aid;
        }

        if ($catid > 0) {
            $query .= ' AND catid='.$catid;
        }

        $db->setQuery($query);
        $total = $db->loadResult();

        return $total;
    }

    public static function countCategoryItems($id)
    {
        $app = Joomla\CMS\Factory::getApplication();
        $user = Joomla\CMS\Factory::getUser();
        $aid = (int) $user->get('aid');
        $id = (int) $id;
        $db = Joomla\CMS\Factory::getDbo();

        $jnow = Joomla\CMS\Factory::getDate();
        $now = K2_JVERSION == '15' ? $jnow->toMySQL() : $jnow->toSql();

        $nullDate = $db->getNullDate();

        $query = sprintf('SELECT COUNT(*) FROM #__k2_items WHERE catid=%d AND published=1 AND ( publish_up = ', $id).$db->Quote($nullDate).' OR publish_up <= '.$db->Quote($now).' ) AND ( publish_down = '.$db->Quote($nullDate).' OR publish_down >= '.$db->Quote($now).' ) AND trash=0 ';
        if (K2_JVERSION != '15') {
            $query .= ' AND access IN('.implode(',', $user->getAuthorisedViewLevels()).') ';
            if ($app->getLanguageFilter()) {
                $languageTag = Joomla\CMS\Factory::getLanguage()->getTag();
                $query .= ' AND language IN ('.$db->Quote($languageTag).', '.$db->Quote('*').') ';
            }
        } else {
            $query .= ' AND access <= '.$aid;
        }

        $db->setQuery($query);
        $total = $db->loadResult();

        return $total;
    }

    public static function calendar($params)
    {
        $month = K2Request::getInt('month');
        $year = K2Request::getInt('year');

        $months = [
            Joomla\CMS\Language\Text::_('K2_JANUARY'),
            Joomla\CMS\Language\Text::_('K2_FEBRUARY'),
            Joomla\CMS\Language\Text::_('K2_MARCH'),
            Joomla\CMS\Language\Text::_('K2_APRIL'),
            Joomla\CMS\Language\Text::_('K2_MAY'),
            Joomla\CMS\Language\Text::_('K2_JUNE'),
            Joomla\CMS\Language\Text::_('K2_JULY'),
            Joomla\CMS\Language\Text::_('K2_AUGUST'),
            Joomla\CMS\Language\Text::_('K2_SEPTEMBER'),
            Joomla\CMS\Language\Text::_('K2_OCTOBER'),
            Joomla\CMS\Language\Text::_('K2_NOVEMBER'),
            Joomla\CMS\Language\Text::_('K2_DECEMBER'),
        ];
        $days = [
            Joomla\CMS\Language\Text::_('K2_SUN'),
            Joomla\CMS\Language\Text::_('K2_MON'),
            Joomla\CMS\Language\Text::_('K2_TUE'),
            Joomla\CMS\Language\Text::_('K2_WED'),
            Joomla\CMS\Language\Text::_('K2_THU'),
            Joomla\CMS\Language\Text::_('K2_FRI'),
            Joomla\CMS\Language\Text::_('K2_SAT'),
        ];

        $myCalendar = new MyCalendar();
        $myCalendar->category = $params->get('calendarCategory', 0);
        $myCalendar->setStartDay(1);
        $myCalendar->setMonthNames($months);
        $myCalendar->setDayNames($days);

        if (($month) && ($year)) {
            return $myCalendar->getMonthView($month, $year);
        }

        return $myCalendar->getCurrentMonthView();
    }

    public function calendarNavigation()
    {
        $app = Joomla\CMS\Factory::getApplication();

        $month = K2Request::getInt('month');
        $year = K2Request::getInt('year');

        $months = [Joomla\CMS\Language\Text::_('K2_JANUARY'), Joomla\CMS\Language\Text::_('K2_FEBRUARY'), Joomla\CMS\Language\Text::_('K2_MARCH'), Joomla\CMS\Language\Text::_('K2_APRIL'), Joomla\CMS\Language\Text::_('K2_MAY'), Joomla\CMS\Language\Text::_('K2_JUNE'), Joomla\CMS\Language\Text::_('K2_JULY'), Joomla\CMS\Language\Text::_('K2_AUGUST'), Joomla\CMS\Language\Text::_('K2_SEPTEMBER'), Joomla\CMS\Language\Text::_('K2_OCTOBER'), Joomla\CMS\Language\Text::_('K2_NOVEMBER'), Joomla\CMS\Language\Text::_('K2_DECEMBER')];
        $days = [Joomla\CMS\Language\Text::_('K2_SUN'), Joomla\CMS\Language\Text::_('K2_MON'), Joomla\CMS\Language\Text::_('K2_TUE'), Joomla\CMS\Language\Text::_('K2_WED'), Joomla\CMS\Language\Text::_('K2_THU'), Joomla\CMS\Language\Text::_('K2_FRI'), Joomla\CMS\Language\Text::_('K2_SAT')];

        $myCalendar = new MyCalendar();
        $myCalendar->setMonthNames($months);
        $myCalendar->setDayNames($days);
        $myCalendar->category = K2Request::getInt('catid');
        $myCalendar->setStartDay(1);
        if (($month) && ($year)) {
            echo $myCalendar->getMonthView($month, $year);
        } else {
            echo $myCalendar->getCurrentMonthView();
        }

        $app->close();
    }

    public static function renderCustomCode($params)
    {
        jimport('joomla.filesystem.file');
        $document = Joomla\CMS\Factory::getDocument();
        if ($params->get('parsePhp')) {
            $customCode = $params->get('customCode');
            ob_start();
            eval(' ?>'.$customCode.'<?php ');
            $output = ob_get_contents();
            ob_end_clean();
        } else {
            $output = $params->get('customCode');
        }

        if ($document->getType() != 'feed') {
            $dispatcher = K2Dispatcher::getInstance();
            if ($params->get('JPlugins')) {
                Joomla\CMS\Plugin\PluginHelper::importPlugin('content');
                $row = new stdClass();
                $row->text = $output;
                if (K2_JVERSION != '15') {
                    $dispatcher->trigger('onContentPrepare', [
                        'mod_k2_tools',
                        &$row,
                        &$params,
                    ]);
                } else {
                    $dispatcher->trigger('onPrepareContent', [
                        &$row,
                        &$params,
                    ]);
                }

                $output = $row->text;
            }

            if ($params->get('K2Plugins')) {
                Joomla\CMS\Plugin\PluginHelper::importPlugin('k2');
                $row = new stdClass();
                $row->text = $output;
                $dispatcher->trigger('onK2PrepareContent', [
                    &$row,
                    &$params,
                ]);
                $output = $row->text;
            }
        }

        return $output;
    }
}

class MyCalendar extends Calendar
{
    public $category = null;

    public $cache = null;

    public function getDateLink($day, $month, $year)
    {
        if (is_null($this->cache)) {
            $this->cache = [];
            $app = Joomla\CMS\Factory::getApplication();
            $user = Joomla\CMS\Factory::getUser();
            $aid = $user->get('aid');
            $db = Joomla\CMS\Factory::getDbo();

            $jnow = Joomla\CMS\Factory::getDate();
            $now = K2_JVERSION == '15' ? $jnow->toMySQL() : $jnow->toSql();

            $nullDate = $db->getNullDate();

            $languageCheck = '';
            if (K2_JVERSION != '15') {
                $accessCheck = ' access IN('.implode(',', $user->getAuthorisedViewLevels()).') ';
                if ($app->getLanguageFilter()) {
                    $languageTag = Joomla\CMS\Factory::getLanguage()->getTag();
                    $languageCheck = ' AND language IN ('.$db->Quote($languageTag).', '.$db->Quote('*').') ';
                }
            } else {
                $accessCheck = ' access <= '.$aid;
            }

            $query = sprintf('SELECT DAY(created) AS day, COUNT(*) AS counter FROM #__k2_items WHERE YEAR(created)=%s AND MONTH(created)=%s AND published=1 AND ( publish_up = ', $year, $month).$db->Quote($nullDate).' OR publish_up <= '.$db->Quote($now).' ) AND ( publish_down = '.$db->Quote($nullDate).' OR publish_down >= '.$db->Quote($now).sprintf(' ) AND trash=0 AND %s %s AND EXISTS(SELECT * FROM #__k2_categories WHERE id= #__k2_items.catid AND published=1 AND trash=0 AND %s %s)', $accessCheck, $languageCheck, $accessCheck, $languageCheck);

            $catid = $this->category;
            if ($catid > 0) {
                $query .= ' AND catid='.$catid;
            }

            $query .= ' GROUP BY day';

            $db->setQuery($query);
            $objects = $db->loadObjectList();
            if ($db->getErrorNum()) {
                echo $db->stderr();

                return false;
            }

            foreach ($objects as $object) {
                $this->cache[$object->day] = $object->counter;
            }
        }

        $result = $this->cache[$day] ?? 0;

        if ($result > 0) {
            if ($this->category > 0) {
                return Joomla\CMS\Router\Route::_(K2HelperRoute::getDateRoute($year, $month, $day, $this->category));
            }

            return Joomla\CMS\Router\Route::_(K2HelperRoute::getDateRoute($year, $month, $day));
        }

        return false;
    }

    public function getCalendarLink($month, $year)
    {
        $itemID = K2Request::getInt('Itemid');
        if ($this->category > 0) {
            return Joomla\CMS\Uri\Uri::root(true).sprintf('/index.php?option=com_k2&amp;view=itemlist&amp;task=calendar&amp;month=%s&amp;year=%s&amp;catid=%s&amp;Itemid=%s', $month, $year, $this->category, $itemID);
        }

        return Joomla\CMS\Uri\Uri::root(true).sprintf('/index.php?option=com_k2&amp;view=itemlist&amp;task=calendar&amp;month=%s&amp;year=%s&amp;Itemid=%s', $month, $year, $itemID);
    }
}

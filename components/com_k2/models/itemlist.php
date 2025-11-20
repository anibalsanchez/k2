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

Joomla\CMS\Table\Table::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2/tables');

class K2ModelItemlist extends K2Model
{
    private $getTotal;

    public function getData($ordering = null)
    {
        $user = Joomla\CMS\Factory::getUser();
        $aid = $user->get('aid');
        $db = Joomla\CMS\Factory::getDbo();
        $task = JRequest::getCmd('task');
        $limitstart = JRequest::getInt('limitstart', 0);
        $limit = JRequest::getInt('limit', 10);
        $config = Joomla\CMS\Factory::getConfig();

        $params = K2HelperUtilities::getParams('com_k2');

        if ($task == 'search') {
            $params->set('googleSearch', 0);
        }

        // For Falang
        $falang_driver = Joomla\CMS\Plugin\PluginHelper::getPlugin('system', 'falangdriver');

        $jnow = Joomla\CMS\Factory::getDate();
        $now = (K2_JVERSION == '15') ? $jnow->toMySQL() : $jnow->toSql();
        /*
        if (version_compare(JVERSION, '3.3', 'ge')) {
            $now = $jnow->format('%Y-%m-%d %H:%M:00');
        } else {
            $now = $jnow->toFormat('%Y-%m-%d %H:%M:00');
        }
        */
        $nullDate = $db->getNullDate();

        // --- Query containing initial SELECT ---
        $queryStart = '/* Frontend / K2 / Items */ SELECT /*+ MAX_EXECUTION_TIME(60000) */ i.*,';

        if ($task == 'search') {
            $queryStart = '/* Frontend / K2 / Items */ SELECT /*+ MAX_EXECUTION_TIME(90000) */ i.*,';
        }

        if ($ordering == 'modified') {
            $queryStart .= ' CASE WHEN i.modified = 0 THEN i.created ELSE i.modified END AS lastChanged,';
        }

        $queryStart .= ' c.name AS categoryname, c.id AS categoryid, c.alias AS categoryalias, c.params AS categoryparams';

        if ($ordering == 'best') {
            $queryStart .= ', (r.rating_sum/r.rating_count) AS rating';
        }

        // --- Query containing FROM to WHERE ---
        $query = ' FROM #__k2_items AS i INNER JOIN #__k2_categories AS c ON c.id = i.catid';

        if ($ordering == 'best') {
            $query .= ' LEFT JOIN #__k2_rating AS r ON r.itemID = i.id';
        }

        if ($task == 'user' && !$user->guest && $user->id == JRequest::getInt('id')) {
            $query .= ' WHERE';
        } else {
            $query .= ' WHERE i.published = 1 AND';
        }

        if (K2_JVERSION != '15') {
            $userACL = array_unique($user->getAuthorisedViewLevels());
            $query .= ' i.access IN('.implode(',', $userACL).') AND i.trash = 0 AND c.published = 1 AND c.access IN('.implode(',', $userACL).') AND c.trash = 0';

            $app = Joomla\CMS\Factory::getApplication();
            $languageFilter = $app->getLanguageFilter();
            if ($languageFilter) {
                $languageTag = Joomla\CMS\Factory::getLanguage()->getTag();
                $query .= ' AND c.language IN('.$db->quote($languageTag).', '.$db->quote('*').') AND i.language IN('.$db->quote($languageTag).', '.$db->quote('*').')';
            }
        } else {
            $query .= sprintf(' i.access <= %s AND i.trash = 0 AND c.published = 1 AND c.access <= %s AND c.trash = 0', $aid, $aid);
        }

        if (!($task == 'user' && !$user->guest && $user->id == JRequest::getInt('id'))) {
            $query .= ' AND (i.publish_up = '.$db->Quote($nullDate).' OR i.publish_up <= '.$db->Quote($now).')';
            $query .= ' AND (i.publish_down = '.$db->Quote($nullDate).' OR i.publish_down >= '.$db->Quote($now).')';
        }

        // Build query depending on task
        switch ($task) {
            case 'category':
                $id = JRequest::getInt('id');

                $category = Joomla\CMS\Table\Table::getInstance('K2Category', 'Table');
                $category->load($id);
                $cparams = class_exists('JParameter') ? new JParameter($category->params) : new JRegistry($category->params);

                if ($cparams->get('inheritFrom')) {
                    $parent = Joomla\CMS\Table\Table::getInstance('K2Category', 'Table');
                    $parent->load($cparams->get('inheritFrom'));
                    $cparams = class_exists('JParameter') ? new JParameter($parent->params) : new JRegistry($parent->params);
                }

                if ($cparams->get('catCatalogMode')) {
                    $query .= sprintf(' AND c.id=%s ', $id);
                } else {
                    $categories = $this->getCategoryTree($id);
                    sort($categories);
                    $sql = @implode(',', $categories);
                    $query .= sprintf(' AND c.id IN(%s)', $sql);
                }

                break;

            case 'user':
                $id = JRequest::getInt('id');
                $query .= sprintf(" AND i.created_by=%s AND i.created_by_alias=''", $id);
                $categories = $params->get('userCategoriesFilter', null);
                if (is_array($categories) && count($categories)) {
                    sort($categories);
                    $query .= ' AND c.id IN('.implode(',', $categories).')';
                }

                if (is_string($categories) && $categories > 0) {
                    $query .= ' AND c.id = '.$categories;
                }

                break;

            case 'search':
                $badchars = [
                    '#',
                    '>',
                    '<',
                    '\\',
                ];
                $search = JString::trim(JString::str_ireplace($badchars, '', JRequest::getString('searchword', null)));
                $sql = $this->prepareSearch($search);
                if (!empty($sql)) {
                    $query .= $sql;
                } else {
                    $rows = [];

                    return $rows;
                }

                break;

            case 'date':
                if ((JRequest::getInt('month')) && (JRequest::getInt('year'))) {
                    $month = JRequest::getInt('month');
                    $year = JRequest::getInt('year');
                    $query .= sprintf(' AND MONTH(i.created) = %s AND YEAR(i.created)=%s', $month, $year);
                    if (JRequest::getInt('day')) {
                        $day = JRequest::getInt('day');
                        $query .= ' AND DAY(i.created) = '.$day;
                    }

                    if (JRequest::getInt('catid')) {
                        $catid = JRequest::getInt('catid');
                        $query .= ' AND c.id='.$catid;
                    }
                }

                break;

            case 'tag':
                $tag = JRequest::getString('tag');

                jimport('joomla.filesystem.file');

                if (Joomla\CMS\Filesystem\File::exists(JPATH_ADMINISTRATOR.'/components/com_joomfish/joomfish.php') && $task == 'tag') {
                    $lang = (K2_JVERSION == '30') ? $config->get('jflang') : $config->getValue('config.jflang');

                    $sql = 'SELECT reference_id
                        FROM #__jf_content AS jfc
                        LEFT JOIN #__languages AS jfl ON jfc.language_id = jfl.'.K2_JF_ID.'
                        WHERE jfc.value = '.$db->Quote($tag)."
                            AND jfc.reference_table = 'k2_tags'
                            AND jfc.reference_field = 'name'
                            AND jfc.published=1";
                    $db->setQuery($sql, 0, 1);
                    $result = $db->loadResult();
                }

                if (Joomla\CMS\Filesystem\File::exists(JPATH_ADMINISTRATOR.'/components/com_falang/falang.php') && $task == 'tag') {
                    $lang = (K2_JVERSION == '30') ? $config->get('jflang') : $config->getValue('config.jflang');

                    $sql = 'SELECT reference_id
                        FROM #__falang_content AS fc
                        LEFT JOIN #__languages AS fl ON fc.language_id = fl.lang_id
                        WHERE fc.value = '.$db->Quote($tag)."
                            AND fc.reference_table = 'k2_tags'
                            AND fc.reference_field = 'name'
                            AND fc.published=1";
                    $db->setQuery($sql, 0, 1);
                    $result = $db->loadResult();
                }

                if (!isset($result) || $result < 1) {
                    $sql = 'SELECT id FROM #__k2_tags WHERE name='.$db->Quote($tag);
                    $db->setQuery($sql, 0, 1);
                    $result = $db->loadResult();
                }

                $query .= ' AND i.id IN(SELECT itemID FROM #__k2_tags_xref WHERE tagID='.(int) $result.')';

                $categories = $params->get('categoriesFilter', null);
                if (is_array($categories)) {
                    sort($categories);
                    $query .= ' AND c.id IN('.implode(',', $categories).')';
                }

                if (is_string($categories)) {
                    $query .= ' AND c.id = '.$categories;
                }

                break;

            default:
                $searchIDs = $params->get('categories');
                if (is_array($searchIDs) && count($searchIDs)) {
                    if ($params->get('catCatalogMode')) {
                        sort($searchIDs);
                        $sql = @implode(',', $searchIDs);
                        $query .= sprintf(' AND c.id IN(%s)', $sql);
                    } else {
                        $result = $this->getCategoryTree($searchIDs);
                        if (count($result) > 0) {
                            sort($result);
                            $sql = @implode(',', $result);
                            $query .= sprintf(' AND c.id IN(%s)', $sql);
                        }
                    }
                }

                break;
        }

        // Set featured flag
        if ($task == 'category' || empty($task)) {
            if (JRequest::getInt('featured') == '0') {
                $query .= ' AND i.featured != 1';
            } elseif (JRequest::getInt('featured') == '2') {
                $query .= ' AND i.featured = 1';
            }
        }

        // --- Query containing GROUP BY and ORDER BY ---
        $queryEnd = '';

        if ($task == 'tag') {
            $queryEnd .= ' GROUP BY i.id';
        }

        // Set ordering
        switch ($ordering) {
            case 'date':
                $orderby = 'i.created ASC';
                break;

            case 'rdate':
                $orderby = 'i.created DESC';
                break;

            case 'alpha':
                $orderby = 'i.title';
                break;

            case 'ralpha':
                $orderby = 'i.title DESC';
                break;

            case 'order':
                $orderby = JRequest::getInt('featured') == '2' ? 'i.featured_ordering' : 'c.ordering, i.ordering';

                break;

            case 'rorder':
                $orderby = JRequest::getInt('featured') == '2' ? 'i.featured_ordering DESC' : 'c.ordering DESC, i.ordering DESC';

                break;

            case 'featured':
                $orderby = 'i.featured DESC, i.created DESC';
                break;

            case 'hits':
                $orderby = 'i.hits DESC';
                break;

            case 'rand':
                $orderby = 'RAND()';
                break;

            case 'best':
                $orderby = 'rating DESC';
                break;

            case 'modified':
                $orderby = 'lastChanged DESC';
                break;

            case 'publishUp':
                $orderby = 'i.publish_up DESC';
                break;

            case 'id':
            default:
                $orderby = 'i.id DESC';
                break;
        }

        $queryEnd .= ' ORDER BY '.$orderby;

        // --- Final query ---
        $combinedQuery = $queryStart.$query.$queryEnd;

        Joomla\CMS\Plugin\PluginHelper::importPlugin('k2');
        $dispatcher = JDispatcher::getInstance();
        $dispatcher->trigger('onK2BeforeSetQuery', [&$combinedQuery]);

        $db->setQuery($combinedQuery, $limitstart, $limit);
        $rows = $db->loadObjectList();

        // --- Row counter ---
        if (count($rows) > 0) {
            if ($task == 'tag') {
                $countQuery = '/* Frontend / K2 / Items Count */ SELECT COUNT(DISTINCT i.id)'.$query;
            } else {
                $countQuery = '/* Frontend / K2 / Items Count */ SELECT COUNT(*)'.$query;
            }

            $db->setQuery($countQuery);
            $this->getTotal = $db->loadResult();
        }

        return $rows;
    }

    public function getTotal()
    {
        return $this->getTotal;
    }

    public function getCategoryTree($categories, $associations = false)
    {
        $app = Joomla\CMS\Factory::getApplication();
        $db = Joomla\CMS\Factory::getDbo();
        $user = Joomla\CMS\Factory::getUser();
        $aid = (int) $user->get('aid');
        if (!is_array($categories)) {
            $categories = (array) $categories;
        }

        JArrayHelper::toInteger($categories);
        $categories = array_unique($categories);
        sort($categories);
        $key = implode('|', $categories);
        $clientID = $app->getClientId();
        static $K2CategoryTreeInstances = [];
        if (isset($K2CategoryTreeInstances[$clientID]) && array_key_exists($key, $K2CategoryTreeInstances[$clientID])) {
            return $K2CategoryTreeInstances[$clientID][$key];
        }

        $array = $categories;
        while (count($array)) {
            $query = 'SELECT id
                        FROM #__k2_categories
                        WHERE parent IN('.implode(',', $array).')
                            AND id NOT IN('.implode(',', $array).')';
            if ($app->isSite()) {
                $query .= ' AND published=1 AND trash=0';
                if (K2_JVERSION != '15') {
                    $query .= ' AND access IN('.implode(',', $user->getAuthorisedViewLevels()).')';
                    if ($app->getLanguageFilter()) {
                        $query .= ' AND language IN('.$db->Quote(Joomla\CMS\Factory::getLanguage()->getTag()).', '.$db->Quote('*').')';
                    }
                } else {
                    $query .= ' AND access<='.$aid;
                }
            }

            $db->setQuery($query);
            $array = (K2_JVERSION == '30') ? $db->loadColumn() : $db->loadResultArray();
            $categories = array_merge($categories, $array);
        }

        JArrayHelper::toInteger($categories);
        $categories = array_unique($categories);
        $K2CategoryTreeInstances[$clientID][$key] = $categories;

        return $categories;
    }

    public function getCategoryFirstChildren($catid, $ordering = null)
    {
        $app = Joomla\CMS\Factory::getApplication();
        $user = Joomla\CMS\Factory::getUser();
        $aid = $user->get('aid');
        $db = Joomla\CMS\Factory::getDbo();
        $query = sprintf('SELECT * FROM #__k2_categories WHERE parent=%s AND published=1 AND trash=0', $catid);

        if (K2_JVERSION != '15') {
            $query .= ' AND access IN('.implode(',', $user->getAuthorisedViewLevels()).') ';
            if ($app->getLanguageFilter()) {
                $query .= ' AND language IN('.$db->Quote(Joomla\CMS\Factory::getLanguage()->getTag()).', '.$db->Quote('*').')';
            }
        } else {
            $query .= sprintf(' AND access<=%s ', $aid);
        }

        switch ($ordering) {
            case 'order':
                $order = ' ordering ASC';
                break;

            case 'alpha':
                $order = ' name ASC';
                break;

            case 'ralpha':
                $order = ' name DESC';
                break;

            case 'reversedefault':
                $order = ' id DESC';
                break;

            default:
                $order = ' id ASC';
                break;
        }

        $query .= ' ORDER BY '.$order;

        $db->setQuery($query);
        $rows = $db->loadObjectList();
        if ($db->getErrorNum()) {
            echo $db->stderr();

            return false;
        }

        return $rows;
    }

    public function countCategoryItems($id)
    {
        $app = Joomla\CMS\Factory::getApplication();
        $user = Joomla\CMS\Factory::getUser();
        $aid = (int) $user->get('aid');
        $id = (int) $id;
        $db = Joomla\CMS\Factory::getDbo();

        $jnow = Joomla\CMS\Factory::getDate();
        $now = (K2_JVERSION == '15') ? $jnow->toMySQL() : $jnow->toSql();
        $nullDate = $db->getNullDate();

        $categories = $this->getCategoryTree($id);
        $query = 'SELECT COUNT(*) FROM #__k2_items WHERE catid IN('.implode(',', $categories).') AND published=1 AND trash=0';

        if (K2_JVERSION != '15') {
            $query .= ' AND access IN('.implode(',', $user->getAuthorisedViewLevels()).')';
            if ($app->getLanguageFilter()) {
                $query .= ' AND language IN('.$db->Quote(Joomla\CMS\Factory::getLanguage()->getTag()).', '.$db->Quote('*').')';
            }
        } else {
            $query .= ' AND access<='.$aid;
        }

        $query .= ' AND (publish_up = '.$db->Quote($nullDate).' OR publish_up <= '.$db->Quote($now).') AND (publish_down = '.$db->Quote($nullDate).' OR publish_down >= '.$db->Quote($now).')';
        $db->setQuery($query);
        $total = $db->loadResult();

        return $total;
    }

    public function getUserProfile($id = null)
    {
        $db = Joomla\CMS\Factory::getDbo();
        $id = is_null($id) ? JRequest::getInt('id') : (int) $id;

        $query = 'SELECT id, gender, description, image, url, `group`, plugins FROM #__k2_users WHERE userID='.$id;
        $db->setQuery($query);
        $row = $db->loadObject();

        return $row;
    }

    public function getAuthorLatest($itemID, $limit, $userID)
    {
        $app = Joomla\CMS\Factory::getApplication();
        $user = Joomla\CMS\Factory::getUser();
        $aid = (int) $user->get('aid');
        $itemID = (int) $itemID;
        $userID = (int) $userID;
        $limit = (int) $limit;
        $db = Joomla\CMS\Factory::getDbo();

        $params = K2HelperUtilities::getParams('com_k2');

        $jnow = Joomla\CMS\Factory::getDate();
        $now = (K2_JVERSION == '15') ? $jnow->toMySQL() : $jnow->toSql();
        $nullDate = $db->getNullDate();

        $query = "SELECT i.*, c.alias AS categoryalias
            FROM #__k2_items AS i
            LEFT JOIN #__k2_categories c ON c.id = i.catid
            WHERE i.id != {$itemID}
                AND i.published = 1
                AND (i.publish_up = ".$db->Quote($nullDate).' OR i.publish_up <= '.$db->Quote($now).')
                AND (i.publish_down = '.$db->Quote($nullDate).' OR i.publish_down >= '.$db->Quote($now).')';

        if (K2_JVERSION != '15') {
            $query .= ' AND i.access IN('.implode(',', $user->getAuthorisedViewLevels()).')';
            if ($app->getLanguageFilter()) {
                $query .= ' AND i.language IN('.$db->Quote(Joomla\CMS\Factory::getLanguage()->getTag()).', '.$db->Quote('*').')';
            }
        } else {
            $query .= ' AND i.access <= '.$aid;
        }

        $query .= " AND i.trash = 0
            AND i.created_by = {$userID}
            AND i.created_by_alias=''
            AND c.published = 1";

        if (K2_JVERSION != '15') {
            $query .= ' AND c.access IN('.implode(',', $user->getAuthorisedViewLevels()).')';
            if ($app->getLanguageFilter()) {
                $query .= ' AND c.language IN('.$db->Quote(Joomla\CMS\Factory::getLanguage()->getTag()).', '.$db->Quote('*').')';
            }
        } else {
            $query .= ' AND c.access <= '.$aid;
        }

        $query .= ' AND c.trash = 0
            ORDER BY i.created DESC';

        $db->setQuery($query, 0, $limit);
        $rows = $db->loadObjectList();

        foreach ($rows as $row) {
            // Image
            $row->imageXSmall = '';
            $row->imageSmall = '';
            $row->imageMedium = '';
            $row->imageLarge = '';
            $row->imageXLarge = '';

            $imageTimestamp = '';
            $dateModified = ((int) $row->modified !== 0) ? $row->modified : '';
            if ($params->get('imageTimestamp', 1) && $dateModified) {
                $imageTimestamp = '?t='.strftime('%Y%m%d_%H%M%S', strtotime($dateModified));
            }

            $imageFilenamePrefix = md5('Image'.$row->id);
            $imagePathPrefix = Joomla\CMS\Uri\Uri::base(true).'/media/k2/items/cache/'.$imageFilenamePrefix;

            // Check if the "generic" variant exists
            if (Joomla\CMS\Filesystem\File::exists(JPATH_SITE.'/media/k2/items/cache/'.$imageFilenamePrefix.'_Generic.jpg')) {
                $row->imageGeneric = $imagePathPrefix.'_Generic.jpg'.$imageTimestamp;
                $row->imageXSmall = $imagePathPrefix.'_XS.jpg'.$imageTimestamp;
                $row->imageSmall = $imagePathPrefix.'_S.jpg'.$imageTimestamp;
                $row->imageMedium = $imagePathPrefix.'_M.jpg'.$imageTimestamp;
                $row->imageLarge = $imagePathPrefix.'_L.jpg'.$imageTimestamp;
                $row->imageXLarge = $imagePathPrefix.'_XL.jpg'.$imageTimestamp;

                $row->imageProperties = new stdClass();
                $row->imageProperties->filenamePrefix = $imageFilenamePrefix;
                $row->imageProperties->pathPrefix = $imagePathPrefix;
            }
        }

        return $rows;
    }

    public function getRelatedItems($itemID, $tags, $params)
    {
        $app = Joomla\CMS\Factory::getApplication();
        $limit = $params->get('itemRelatedLimit', 10);
        $itemID = (int) $itemID;

        foreach ($tags as $tag) {
            $tagIDs[] = $tag->id;
        }

        JArrayHelper::toInteger($tagIDs);
        sort($tagIDs);
        $sql = implode(',', $tagIDs);

        $user = Joomla\CMS\Factory::getUser();
        $aid = (int) $user->get('aid');
        $db = Joomla\CMS\Factory::getDbo();

        $jnow = Joomla\CMS\Factory::getDate();
        $now = (K2_JVERSION == '15') ? $jnow->toMySQL() : $jnow->toSql();
        $nullDate = $db->getNullDate();

        $query = "SELECT itemID
            FROM #__k2_tags_xref
            WHERE tagID IN({$sql})
                AND itemID != {$itemID}
            GROUP BY itemID";
        $db->setQuery($query);

        $itemsIDs = (K2_JVERSION == '30') ? $db->loadColumn() : $db->loadResultArray();

        if (count($itemsIDs) === 0) {
            return [];
        }

        sort($itemsIDs);
        $sql = implode(',', $itemsIDs);

        $query = 'SELECT i.*, c.alias AS categoryalias
            FROM #__k2_items AS i
            LEFT JOIN #__k2_categories AS c ON c.id = i.catid
            WHERE i.published = 1
                AND i.trash = 0
                AND (i.publish_up = '.$db->Quote($nullDate).' OR i.publish_up <= '.$db->Quote($now).')
                AND (i.publish_down = '.$db->Quote($nullDate).' OR i.publish_down >= '.$db->Quote($now).')';

        if (K2_JVERSION != '15') {
            $query .= ' AND i.access IN('.implode(',', $user->getAuthorisedViewLevels()).')';
            if ($app->getLanguageFilter()) {
                $query .= ' AND i.language IN('.$db->Quote(Joomla\CMS\Factory::getLanguage()->getTag()).', '.$db->Quote('*').')';
            }
        } else {
            $query .= ' AND i.access <= '.$aid;
        }

        if (K2_JVERSION != '15') {
            $query .= ' AND c.access IN('.implode(',', $user->getAuthorisedViewLevels()).')';
            if ($app->getLanguageFilter()) {
                $query .= ' AND c.language IN('.$db->Quote(Joomla\CMS\Factory::getLanguage()->getTag()).', '.$db->Quote('*').')';
            }
        } else {
            $query .= ' AND c.access <= '.$aid;
        }

        $query .= sprintf(' AND c.published = 1 AND c.trash = 0 AND i.id IN(%s) ORDER BY i.id DESC', $sql);

        $db->setQuery($query, 0, $limit);
        $rows = $db->loadObjectList();
        K2Model::addIncludePath(JPATH_COMPONENT.'/models');
        $model = K2Model::getInstance('Item', 'K2Model');
        $counter = count($rows);
        for ($key = 0; $key < $counter; $key++) {
            if (!$params->get('itemRelatedMedia')) {
                $rows[$key]->video = null;
            }

            if (!$params->get('itemRelatedImageGallery')) {
                $rows[$key]->gallery = null;
            }

            $rows[$key] = $model->prepareItem($rows[$key], 'relatedByTag', '');
            $rows[$key] = $model->execPlugins($rows[$key], 'relatedByTag', '');
            K2HelperUtilities::setDefaultImage($rows[$key], 'relatedByTag', $params);
        }

        return $rows;
    }

    public function prepareSearch($search)
    {
        jimport('joomla.filesystem.file');
        $db = Joomla\CMS\Factory::getDbo();
        $language = Joomla\CMS\Factory::getLanguage();
        $defaultLang = $language->getDefault();
        $currentLang = $language->getTag();

        $search = trim($search);
        $length = JString::strlen($search);

        $sql = '';

        if (JRequest::getVar('categories')) {
            $categories = @explode(',', JRequest::getVar('categories'));
            JArrayHelper::toInteger($categories);
            sort($categories);
            $sql .= ' AND c.id IN('.@implode(',', $categories).')';
        }

        if ($search === '' || $search === '0') {
            return $sql;
        }

        $type = JString::substr($search, 0, 1) == '"' && JString::substr($search, $length - 1, 1) == '"' ? 'exact' : 'any';

        if (Joomla\CMS\Filesystem\File::exists(JPATH_ADMINISTRATOR.'/components/com_joomfish/joomfish.php') && $currentLang != $defaultLang) {
            $conditions = [];
            $search_ignore = [];
            $ignoreFile = $language->getLanguagePath().'/'.$currentLang.'/'.$currentLang.'.ignore.php';
            if (Joomla\CMS\Filesystem\File::exists($ignoreFile)) {
                include $ignoreFile;
            }

            if ($type === 'exact') {
                $word = JString::substr($search, 1, $length - 2);

                if (JString::strlen($word) > 3 && !in_array($word, $search_ignore)) {
                    $escaped = (K2_JVERSION == '15') ? $db->getEscaped($word, true) : $db->escape($word, true);
                    $langField = (K2_JVERSION == '15') ? 'code' : 'lang_code';
                    $word = $db->Quote('%'.$escaped.'%', false);

                    $jfQuery = 'SELECT reference_id
                        FROM #__jf_content AS jfc
                        LEFT JOIN #__languages AS jfl ON jfc.language_id = jfl.'.K2_JF_ID."
                        WHERE jfc.reference_table = 'k2_items'
                            AND jfl.".$langField.' = '.$db->Quote($currentLang).'
                            AND jfc.published = 1
                            AND jfc.value LIKE '.$word."
                            AND (
                                jfc.reference_field = 'title'
                                OR jfc.reference_field = 'introtext'
                                OR jfc.reference_field = 'fulltext'
                                OR jfc.reference_field = 'image_caption'
                                OR jfc.reference_field = 'image_credits'
                                OR jfc.reference_field = 'video_caption'
                                OR jfc.reference_field = 'video_credits'
                                OR jfc.reference_field = 'extra_fields_search'
                                OR jfc.reference_field = 'metadesc'
                                OR jfc.reference_field = 'metakey'
                            )";
                    $db->setQuery($jfQuery);
                    $result = (K2_JVERSION == '30') ? $db->loadColumn() : $db->loadResultArray();
                    $result = @array_unique($result);
                    JArrayHelper::toInteger($result);
                    if ($result !== []) {
                        $conditions[] = 'i.id IN('.implode(',', $result).')';
                    }
                }
            } else {
                $search = explode(' ', JString::strtolower($search));
                foreach ($search as $searchword) {
                    if (JString::strlen($searchword) > 3 && !in_array($searchword, $search_ignore)) {
                        $escaped = (K2_JVERSION == '15') ? $db->getEscaped($searchword, true) : $db->escape($searchword, true);
                        $word = $db->Quote('%'.$escaped.'%', false);
                        $langField = (K2_JVERSION == '15') ? 'code' : 'lang_code';

                        $jfQuery = 'SELECT reference_id
                            FROM #__jf_content AS jfc
                            LEFT JOIN #__languages AS jfl ON jfc.language_id = jfl.'.K2_JF_ID."
                            WHERE jfc.reference_table = 'k2_items'
                                AND jfl.".$langField.' = '.$db->Quote($currentLang).'
                                AND jfc.published = 1
                                AND jfc.value LIKE '.$word."
                                AND (
                                    jfc.reference_field = 'title'
                                    OR jfc.reference_field = 'introtext'
                                    OR jfc.reference_field = 'fulltext'
                                    OR jfc.reference_field = 'image_caption'
                                    OR jfc.reference_field = 'image_credits'
                                    OR jfc.reference_field = 'video_caption'
                                    OR jfc.reference_field = 'video_credits'
                                    OR jfc.reference_field = 'extra_fields_search'
                                    OR jfc.reference_field = 'metadesc'
                                    OR jfc.reference_field = 'metakey'
                                )";
                        $db->setQuery($jfQuery);
                        $result = (K2_JVERSION == '30') ? $db->loadColumn() : $db->loadResultArray();
                        $result = @array_unique($result);
                        foreach ($result as $id) {
                            $allIDs[] = $id;
                        }

                        if (Joomla\CMS\Filesystem\File::exists(JPATH_ADMINISTRATOR.'/components/com_joomfish/joomfish.php') && $currentLang != $defaultLang && (isset($allIDs) && count($allIDs))) {
                            JArrayHelper::toInteger($allIDs);
                            $conditions[] = 'i.id IN('.implode(',', $allIDs).')';
                        }
                    }
                }
            }

            if ($conditions !== []) {
                $sql .= ' AND ('.implode(' OR ', $conditions).')';
            }
        } elseif ($type === 'exact') {
            $search = JString::trim($search, '"');
            $escaped = (K2_JVERSION == '15') ? $db->getEscaped($search, true) : $db->escape($search, true);
            $quoted = $db->Quote('%'.$escaped.'%', false);
            $sql .= ' AND (
                    LOWER(i.title) LIKE '.$quoted.' OR
                    LOWER(i.introtext) LIKE '.$quoted.' OR
                    LOWER(i.`fulltext`) LIKE '.$quoted.' OR
                    LOWER(i.extra_fields_search) LIKE '.$quoted.' OR
                    LOWER(i.image_caption) LIKE '.$quoted.' OR
                    LOWER(i.image_credits) LIKE '.$quoted.' OR
                    LOWER(i.video_caption) LIKE '.$quoted.' OR
                    LOWER(i.video_credits) LIKE '.$quoted.' OR
                    LOWER(i.metadesc) LIKE '.$quoted.' OR
                    LOWER(i.metakey) LIKE '.$quoted.'
                )';
        } else {
            $search = strtolower(trim(preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $search)));

            $searchwords = explode(' ', $search);
            if ($searchwords !== []) {
                // Already an array
            } else {
                $searchwords = [$search];
            }

            $searchPerTerm = [];
            $sql .= ' AND (';
            foreach ($searchwords as $searchword) {
                if (strlen($searchword) > 2) {
                    $escaped = (K2_JVERSION == '15') ? $db->getEscaped($searchword, true) : $db->escape($searchword, true);
                    $quoted = $db->Quote('%'.$escaped.'%', false);
                    $searchPerTerm[] = '
                            LOWER(i.title) LIKE '.$quoted.' OR
                            LOWER(i.introtext) LIKE '.$quoted.' OR
                            LOWER(i.`fulltext`) LIKE '.$quoted.' OR
                            LOWER(i.extra_fields_search) LIKE '.$quoted.' OR
                            LOWER(i.image_caption) LIKE '.$quoted.' OR
                            LOWER(i.image_credits) LIKE '.$quoted.' OR
                            LOWER(i.video_caption) LIKE '.$quoted.' OR
                            LOWER(i.video_credits) LIKE '.$quoted.' OR
                            LOWER(i.metadesc) LIKE '.$quoted.' OR
                            LOWER(i.metakey) LIKE '.$quoted.'
                        ';
                }
            }

            $sql .= implode(' OR ', $searchPerTerm);
            $sql .= ')';
        }

        return $sql;
    }

    public function getModuleItems($moduleID)
    {
        $db = Joomla\CMS\Factory::getDbo();
        $query = sprintf('SELECT * FROM #__modules WHERE id=%s AND published=1 AND client_id=0', $moduleID);
        $db->setQuery($query, 0, 1);
        $module = $db->loadObject();
        $format = JRequest::getWord('format');
        if (is_null($module)) {
            JError::raiseError(404, Joomla\CMS\Language\Text::_('K2_NOT_FOUND'));
        } else {
            $params = class_exists('JParameter') ? new JParameter($module->params) : new JRegistry($module->params);
            switch ($module->module) {
                case 'mod_k2_content':
                    require_once JPATH_SITE.'/modules/mod_k2_content/helper.php';
                    $helper = new modK2ContentHelper();
                    $items = $helper->getItems($params, $format);
                    break;

                case 'mod_k2_comments':
                    if ($params->get('module_usage') == 1) {
                        JError::raiseError(404, Joomla\CMS\Language\Text::_('K2_NOT_FOUND'));
                    }

                    require_once JPATH_SITE.'/modules/mod_k2_comments/helper.php';
                    $helper = new modK2CommentsHelper();
                    $items = $helper->getLatestComments($params);

                    foreach ($items as $item) {
                        $item->title = $item->userName.' '.Joomla\CMS\Language\Text::_('K2_COMMENTED_ON').' '.$item->title;
                        $item->introtext = $item->commentText;
                        $item->created = $item->commentDate;
                        $item->id = $item->itemID;
                    }

                    break;

                default:
                    JError::raiseError(404, Joomla\CMS\Language\Text::_('K2_NOT_FOUND'));
            }

            $result = new stdClass();
            $result->items = $items;
            $result->title = $module->title;
            $result->module = $module->module;
            $result->params = $module->params;

            return $result;
        }

        return null;
    }

    public function getCategoriesTree()
    {
        $app = Joomla\CMS\Factory::getApplication();
        $clientID = $app->getClientId();
        $db = Joomla\CMS\Factory::getDbo();
        $user = Joomla\CMS\Factory::getUser();
        $aid = (int) $user->get('aid');

        $query = 'SELECT id, name, parent FROM #__k2_categories';
        if ($app->isSite()) {
            $query .= ' WHERE published=1 AND trash=0';
            if (K2_JVERSION != '15') {
                $query .= ' AND access IN('.implode(',', $user->getAuthorisedViewLevels()).')';
                if ($app->getLanguageFilter()) {
                    $query .= ' AND language IN('.$db->Quote(Joomla\CMS\Factory::getLanguage()->getTag()).', '.$db->Quote('*').')';
                }
            } else {
                $query .= ' AND access<='.$aid;
            }
        }

        $query .= ' ORDER BY parent';
        $db->setQuery($query);

        $categories = $db->loadObjectList();
        $tree = [];

        return $this->buildTree($categories);
    }

    public function buildTree(array &$categories, $parent = 0)
    {
        $branch = [];
        foreach ($categories as &$category) {
            if ($category->parent == $parent) {
                $children = $this->buildTree($categories, $category->id);
                if ($children) {
                    $category->children = $children;
                }

                $branch[$category->id] = $category;
            }
        }

        return $branch;
    }

    public function getTreePath($tree, $id)
    {
        if (array_key_exists($id, $tree)) {
            return [$id];
        }

        foreach ($tree as $key => $root) {
            if (isset($root->children) && is_array($root->children)) {
                $retry = $this->getTreePath($root->children, $id);

                if ($retry) {
                    $retry[] = $key;

                    return $retry;
                }
            }
        }

        return null;
    }

    // Deprecated function, left for compatibility reasons
    public function getCategoryChildren($catid, $clear = false)
    {
        static $array = [];
        if ($clear) {
            $array = [];
        }

        $user = Joomla\CMS\Factory::getUser();
        $aid = (int) $user->get('aid');
        $catid = (int) $catid;
        $db = Joomla\CMS\Factory::getDbo();
        $query = sprintf('SELECT * FROM #__k2_categories WHERE parent=%d AND published=1 AND trash=0 AND access<=%d ORDER BY ordering', $catid, $aid);
        $db->setQuery($query);
        $rows = $db->loadObjectList();

        foreach ($rows as $row) {
            $array[] = $row->id;
            if ($this->hasChildren($row->id)) {
                $this->getCategoryChildren($row->id);
            }
        }

        return $array;
    }

    // Deprecated function, left for compatibility reasons
    public function hasChildren($id)
    {
        $user = Joomla\CMS\Factory::getUser();
        $aid = (int) $user->get('aid');
        $id = (int) $id;
        $db = Joomla\CMS\Factory::getDbo();
        $query = sprintf('SELECT * FROM #__k2_categories WHERE parent=%d AND published=1 AND trash=0 AND access<=%d ', $id, $aid);
        $db->setQuery($query);
        $rows = $db->loadObjectList();

        return (bool) (count($rows));
    }
}

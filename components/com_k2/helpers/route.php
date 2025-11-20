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

jimport('joomla.application.component.helper');

class K2HelperRoute
{
    private static $cache = [
        'item' => [],
        'category' => [],
        'date' => [],
        'tag' => [],
        'user' => [],
        'menu_items' => null,
        'fallback_menu_items' => [],
        'multicat_menu_items' => [],
        'category_tree' => null,
        'itemlist_model' => null,
    ];

    public static function getItemRoute($id, $catid = 0)
    {
        $key = (int) $id;
        if (isset(self::$cache['item'][$key])) {
            return self::$cache['item'][$key];
        }

        $needles = [
            'item' => (int) $id,
            'category' => (int) $catid,
        ];
        $link = 'index.php?option=com_k2&view=item&id='.$id;
        if ($item = self::findMenuItem($needles)) {
            $link .= '&Itemid='.$item->id;
        }

        self::$cache['item'][$key] = $link;

        return $link;
    }

    public static function getCategoryRoute($catid)
    {
        $key = (int) $catid;
        if (isset(self::$cache['category'][$key])) {
            return self::$cache['category'][$key];
        }

        $needles = ['category' => (int) $catid];
        $link = 'index.php?option=com_k2&view=itemlist&task=category&id='.$catid;
        if ($item = self::findMenuItem($needles)) {
            $link .= '&Itemid='.$item->id;
        }

        self::$cache['category'][$key] = $link;

        return $link;
    }

    public static function getTagRoute($tag)
    {
        $key = hash('md5', $tag);
        if (isset(self::$cache['tag'][$key])) {
            return self::$cache['tag'][$key];
        }

        $needles = ['tag' => $tag];
        $link = 'index.php?option=com_k2&view=itemlist&task=tag&tag='.urlencode($tag);
        if ($item = self::findMenuItem($needles)) {
            $link .= '&Itemid='.$item->id;
        }

        self::$cache['tag'][$key] = $link;

        return $link;
    }

    public static function getUserRoute($userID)
    {
        $key = (int) $userID;
        if (isset(self::$cache['user'][$key])) {
            return self::$cache['user'][$key];
        }

        $needles = ['user' => (int) $userID];
        $user = Joomla\CMS\Factory::getUser($userID);
        if (K2_JVERSION != '15' && Joomla\CMS\Factory::getConfig()->get('unicodeslugs') == 1) {
            $alias = JApplication::stringURLSafe($user->name);
        } elseif (Joomla\CMS\Plugin\PluginHelper::isEnabled('system', 'unicodeslug') || Joomla\CMS\Plugin\PluginHelper::isEnabled('system', 'jw_unicodeSlugsExtended')) {
            $alias = Joomla\CMS\Filter\OutputFilter::stringURLSafe($user->name);
        } else {
            $alias = preg_replace('/[^\p{L}\p{N}]/u', '', trim($user->name));
            $alias = mb_strtolower($alias, 'UTF-8');
            $params = K2HelperUtilities::getParams('com_k2');
            $processedSEFReplacements = [];
            $SEFReplacements = explode(',', $params->get('SEFReplacements', null));
            foreach ($SEFReplacements as $SEFReplacement) {
                if ($SEFReplacement !== '' && $SEFReplacement !== '0') {
                    @[$src, $dst] = explode('|', trim($SEFReplacement));
                    $processedSEFReplacements[trim($src)] = trim($dst);
                }
            }

            foreach ($processedSEFReplacements as $key => $value) {
                $alias = str_replace($key, $value, $alias);
            }

            $alias = preg_replace('/[^\p{L}\p{N}]/u', '', $alias);
            if (trim($alias) === '') {
                // I mean, what are the freaking odds, right?
                $alias = hash('md5', $user->name);
            }
        }

        $link = 'index.php?option=com_k2&view=itemlist&task=user&id='.$userID.':'.$alias;
        if ($item = self::findMenuItem($needles)) {
            $link .= '&Itemid='.$item->id;
        }

        self::$cache['user'][$key] = $link;

        return $link;
    }

    public static function getDateRoute($year, $month, $day = 0, $catid = 0)
    {
        $key = (int) $year.$month.$day.$catid;
        if (isset(self::$cache['date'][$key])) {
            return self::$cache['date'][$key];
        }

        $needles = ['date' => (int) $year.$month.$day];
        $link = 'index.php?option=com_k2&view=itemlist&task=date&year='.$year.'&month='.$month;
        if ($day) {
            $link .= '&day='.$day;
        }

        if ($catid) {
            $link .= '&catid='.$catid;
        }

        if ($item = self::findMenuItem($needles)) {
            $link .= '&Itemid='.$item->id;
        }

        self::$cache['date'][$key] = $link;

        return $link;
    }

    public static function getSearchRoute($Itemid = '')
    {
        $needles = ['search' => 'search'];
        $link = 'index.php?option=com_k2&view=itemlist&task=search';
        if ($Itemid) {
            $link .= '&Itemid='.$Itemid;
        } elseif ($item = self::findMenuItem($needles)) {
            $link .= '&Itemid='.$item->id;
        }

        return $link;
    }

    private static function findMenuItem($needles)
    {
        $app = Joomla\CMS\Factory::getApplication();
        $menu = $app->getMenu('site', []);
        $component = Joomla\CMS\Component\ComponentHelper::getComponent('com_k2');

        if (!is_null(self::$cache['menu_items'])) {
            $items = self::$cache['menu_items'];
        } else {
            if (K2_JVERSION == '15') {
                $items = $menu->getItems('componentid', $component->id);
            } else {
                $items = $menu->getItems('component_id', $component->id);
            }

            self::$cache['menu_items'] = $items;
        }

        $parsedItems = [];

        if (count($items) > 0) {
            foreach ($items as $item) {
                // Find K2 menu items pointing to multiple K2 categories
                if (@$item->query['view'] == 'itemlist' && @$item->query['task'] == '' && !isset(self::$cache['multicat_menu_items'][$item->id])) {
                    if (K2_JVERSION == '15') {
                        $menuparams = explode("\n", $item->params);
                        foreach ($menuparams as $menuparam) {
                            if (str_starts_with($menuparam, 'categories=')) {
                                $array = explode('categories=', $menuparam);
                                $item->K2Categories = explode('|', $array[1]);
                            }
                        }

                        if (!isset($item->K2Categories)) {
                            $item->K2Categories = [];
                        }
                    } else {
                        $menuparams = json_decode($item->params);
                        $item->K2Categories = $menuparams->categories ?? [];
                    }

                    self::$cache['multicat_menu_items'][$item->id] = $item->K2Categories;
                    if (is_array($item->K2Categories) && $item->K2Categories === []) {
                        // Push all K2 itemlist menu items without specific categories assigned into an array
                        // Later we pick the one with the highest menu item ID [TBC with static selection under SEO settings]
                        self::$cache['fallback_menu_items'][$item->id] = $item;
                    }
                }

                $parsedItems[] = $item;
            }
        }

        $match = null;

        foreach ($needles as $needle => $id) {
            if ($parsedItems !== []) {
                foreach ($parsedItems as $item) {
                    if ($needle == 'category' || $needle == 'user') {
                        if ((@$item->query['task'] == $needle) && (@$item->query['id'] == $id)) {
                            $match = $item;
                            break;
                        }
                    } elseif ($needle == 'tag') {
                        if ((@$item->query['task'] == $needle) && (@$item->query['tag'] == $id)) {
                            $match = $item;
                            break;
                        }
                    } elseif ((@$item->query['view'] == $needle) && (@$item->query['id'] == $id)) {
                        $match = $item;
                        break;
                    }

                    if (!is_null($match)) {
                        break;
                    }
                }

                // Second pass for K2 menu items pointing to multiple K2 categories - attempt to find menu item that includes a given category's ID
                if (is_null($match) && $needle == 'category') {
                    foreach ($parsedItems as $parsedItem) {
                        if (@$parsedItem->query['view'] == 'itemlist' && @$parsedItem->query['task'] == '') {
                            if (!empty(self::$cache['multicat_menu_items'][$parsedItem->id]) && is_array(self::$cache['multicat_menu_items'][$parsedItem->id]) && count(self::$cache['multicat_menu_items'][$parsedItem->id])) {
                                foreach (self::$cache['multicat_menu_items'][$parsedItem->id] as $catid) {
                                    if ($id == (int) $catid) {
                                        $match = $parsedItem;
                                        break;
                                    }
                                }
                            }

                            if (!is_null($match)) {
                                break;
                            }
                        }
                    }
                }
            }

            if (!is_null($match)) {
                break;
            }
        }

        if (is_null($match)) {
            // Try to detect any parent category menu item
            if ($needle == 'category') {
                if (is_null(self::$cache['category_tree'])) {
                    K2Model::addIncludePath(JPATH_SITE.'/components/com_k2/models');
                    $model = K2Model::getInstance('Itemlist', 'K2Model');
                    self::$cache['category_tree'] = $model->getCategoriesTree();
                    self::$cache['itemlist_model'] = $model;
                }

                $parents = self::$cache['itemlist_model']->getTreePath(self::$cache['category_tree'], $id);
                if (is_array($parents) && count($parents)) {
                    foreach ($parents as $parent) {
                        if ($parent != $id) {
                            // Recursively check if a menu item exists with the parent category ID
                            $match = self::findMenuItem(['category' => $parent]);
                            if (!is_null($match)) {
                                break;
                            }
                        }
                    }
                }
            }

            if (is_null($match) && count(self::$cache['fallback_menu_items'])) {
                // We can't find any match so we pick the K2 itemlist menu item with the highest ID that points to no specific categories
                rsort(self::$cache['fallback_menu_items']);
                $match = self::$cache['fallback_menu_items'][0];
            }
        }

        return $match;
    }
}

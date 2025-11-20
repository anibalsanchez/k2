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

require_once JPATH_ADMINISTRATOR.'/components/com_k2/tables/table.php';

class TableK2Item extends K2Table
{
    public $_db;

    public $_tbl_key;

    public $_tbl;

    public $id = null;

    public $title = null;

    public $alias = null;

    public $catid = null;

    public $published = null;

    public $introtext = null;

    public $fulltext = null;

    public $image_caption = null;

    public $image_credits = null;

    public $video = null;

    public $video_caption = null;

    public $video_credits = null;

    public $gallery = null;

    public $extra_fields = null;

    public $extra_fields_search = null;

    public $created = null;

    public $created_by = null;

    public $created_by_alias = null;

    public $modified = null;

    public $modified_by = null;

    public $publish_up = null;

    public $publish_down = null;

    public $checked_out = null;

    public $checked_out_time = null;

    public $trash = null;

    public $access = null;

    public $ordering = null;

    public $featured = null;

    public $featured_ordering = null;

    public $hits = null;

    public $metadata = null;

    public $metadesc = null;

    public $metakey = null;

    public $params = null;

    public $plugins = null;

    public $language = null;

    public function __construct(&$db)
    {
        parent::__construct('#__k2_items', 'id', $db);
    }

    public function check()
    {
        jimport('joomla.filter.output');
        $app = Joomla\CMS\Factory::getApplication();
        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        $this->title = JString::trim($this->title);
        if ($this->title == '') {
            $this->setError(Joomla\CMS\Language\Text::_('K2_ITEM_MUST_HAVE_A_TITLE'));

            return false;
        }

        if (!$this->catid) {
            $this->setError(Joomla\CMS\Language\Text::_('K2_ITEM_MUST_HAVE_A_CATEGORY'));

            return false;
        }

        if (empty($this->alias)) {
            $this->alias = $this->title;
        }

        /* Offload the alias processing block to a simplified external function/method call */
        if (K2_JVERSION == '15') {
            if (Joomla\CMS\Plugin\PluginHelper::isEnabled('system', 'unicodeslug') || Joomla\CMS\Plugin\PluginHelper::isEnabled('system', 'jw_unicodeSlugsExtended')) {
                $this->alias = Joomla\CMS\Filter\OutputFilter::stringURLSafe($this->alias);
            } else {
                mb_internal_encoding('UTF-8');
                mb_regex_encoding('UTF-8');
                $this->alias = trim(mb_strtolower($this->alias));
                $this->alias = str_replace('-', ' ', $this->alias);
                $this->alias = str_replace('/', '-', $this->alias);
                $this->alias = mb_ereg_replace('[[:space:]]+', ' ', $this->alias);
                $this->alias = trim(str_replace(' ', '-', $this->alias));
                $this->alias = str_replace('.', '', $this->alias);
                $this->alias = str_replace('"', '', $this->alias);
                $this->alias = str_replace("'", '', $this->alias);
                $stripthese = ',|~|!|@|%|^|(|)|<|>|:|;|{|}|[|]|&|`|â€ž|â€¹|â€™|â€˜|â€œ|â€�|â€¢|â€º|Â«|Â´|Â»|Â°|«|»|…';
                $strips = explode('|', $stripthese);
                foreach ($strips as $strip) {
                    $this->alias = str_replace($strip, '', $this->alias);
                }

                if (trim(str_replace('-', '', $this->alias)) === '') {
                    $datenow = Joomla\CMS\Factory::getDate();
                    $this->alias = $datenow->toFormat('%Y-%m-%d-%H-%M-%S');
                }

                $this->alias = trim($this->alias, '-.');
            }
        } elseif (Joomla\CMS\Factory::getConfig()->get('unicodeslugs') == 1) {
            $this->alias = Joomla\CMS\Filter\OutputFilter::stringURLUnicodeSlug($this->alias);
        } else {
            // Detect the site language we will transliterate
            if ($this->language == '*') {
                $langParams = Joomla\CMS\Component\ComponentHelper::getParams('com_languages');
                $languageTag = $langParams->get('site');
            } else {
                $languageTag = $this->language;
            }

            $language = Joomla\CMS\Language\Language::getInstance($languageTag);
            $this->alias = $language->transliterate($this->alias);
            $this->alias = Joomla\CMS\Filter\OutputFilter::stringURLSafe($this->alias);
            if (trim(str_replace('-', '', $this->alias)) === '') {
                $this->alias = Joomla\CMS\Factory::getDate()->format('Y-m-d-H-i-s');
            }
        }

        if (K2_JVERSION == '15' || $params->get('enforceSEFReplacements')) {
            $SEFReplacements = [];
            $items = explode(',', $params->get('SEFReplacements'));
            foreach ($items as $item) {
                if ($item !== '' && $item !== '0') {
                    @[$src, $dst] = explode('|', trim($item));
                    $SEFReplacements[trim($src)] = trim($dst);
                }
            }

            foreach ($SEFReplacements as $key => $value) {
                $this->alias = str_replace($key, $value, $this->alias);
            }

            $this->alias = trim($this->alias, '-.');
        }

        if (K2_JVERSION == '15' && trim(str_replace('-', '', $this->alias)) === '') {
            $datenow = Joomla\CMS\Factory::getDate();
            $this->alias = $datenow->toFormat('%Y-%m-%d-%H-%M-%S');
        }

        // Check if the item alias already exists, warn the user if it does and append the item ID to it.
        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        if ($params->get('k2Sef') && !$params->get('k2SefInsertItemId')) {
            $db = Joomla\CMS\Factory::getDbo();
            if ($this->id) {
                $db->setQuery('SELECT id FROM #__k2_items WHERE alias = '.$db->quote($this->alias).' AND id != '.(int) $this->id);
                $result = count($db->loadObjectList());
                if ($result > 0) {
                    $this->alias .= '-'.(int) $this->id;
                    $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_WARNING_DUPLICATE_TITLE_ALIAS_DETECTED'), 'notice');
                }
            } else {
                $db->setQuery('SELECT id FROM #__k2_items WHERE alias = '.$db->quote($this->alias));
                $result = count($db->loadObjectList());
                if ($result > 0) {
                    $this->alias .= '-'.date('YmdHi');
                    $app->enqueueMessage(Joomla\CMS\Language\Text::_('K2_WARNING_DUPLICATE_TITLE_ALIAS_DETECTED'), 'notice');
                }
            }
        }

        return true;
    }

    public function bind($array, $ignore = '')
    {
        if (array_key_exists('params', $array) && is_array($array['params'])) {
            $registry = new JRegistry();
            $registry->loadArray($array['params']);
            $array['params'] = $registry->toString();
        }

        if (array_key_exists('plugins', $array) && is_array($array['plugins'])) {
            $registry = new JRegistry();
            $registry->loadArray($array['plugins']);
            $array['plugins'] = $registry->toString();
        }

        return parent::bind($array, $ignore);
    }

    public function getNextOrder($where = '', $column = 'ordering')
    {
        $query = sprintf('SELECT MAX(%s) FROM #__k2_items', $column);
        $query .= ($where ? ' WHERE '.$where : '');
        $this->_db->setQuery($query);
        $maxord = $this->_db->loadResult();
        if ($this->_db->getErrorNum()) {
            $this->setError($this->_db->getErrorMsg());

            return false;
        }

        return $maxord + 1;
    }

    public function reorder($where = '', $column = 'ordering')
    {
        $w = ($where) ? ' AND '.$where : '';
        $k = $this->_tbl_key;
        $query = sprintf('SELECT %s, %s FROM #__k2_items WHERE %s > 0 %s ORDER BY %s', $this->_tbl_key, $column, $column, $w, $column);
        $this->_db->setQuery($query);
        if (!($orders = $this->_db->loadObjectList())) {
            $this->setError($this->_db->getErrorMsg());

            return false;
        }

        for ($i = 0, $n = count($orders); $i < $n; $i++) {
            if ($orders[$i]->$column >= 0 && $orders[$i]->{$column} != $i + 1) {
                $orders[$i]->$column = $i + 1;
                $query = sprintf('UPDATE #__k2_items SET %s = ', $column).(int) $orders[$i]->$column.sprintf(' WHERE %s = ', $k).$this->_db->Quote($orders[$i]->$k);
                $this->_db->setQuery($query);
                $this->_db->query();
            }
        }

        return true;
    }

    public function move($dirn, $where = '', $column = 'ordering')
    {
        $k = $this->_tbl_key;

        $sql = sprintf('SELECT %s, %s FROM %s', $this->_tbl_key, $column, $this->_tbl);

        if ($dirn < 0) {
            $sql .= ' WHERE '.$column.' < '.(int) $this->$column;
            $sql .= ($where ? ' AND '.$where : '');
            $sql .= ' ORDER BY '.$column.' DESC';
        } elseif ($dirn > 0) {
            $sql .= ' WHERE '.$column.' > '.(int) $this->$column;
            $sql .= ($where ? ' AND '.$where : '');
            $sql .= ' ORDER BY '.$column;
        } else {
            $sql .= ' WHERE '.$column.' = '.(int) $this->$column;
            $sql .= ($where ? ' AND '.$where : '');
            $sql .= ' ORDER BY '.$column;
        }

        $this->_db->setQuery($sql, 0, 1);

        $row = null;
        $row = $this->_db->loadObject();

        if (isset($row)) {
            $query = 'UPDATE '.$this->_tbl.' SET '.$column.' = '.(int) $row->$column.' WHERE '.$this->_tbl_key.' = '.$this->_db->Quote($this->$k);
            $this->_db->setQuery($query);

            if (!$this->_db->query()) {
                $err = $this->_db->getErrorMsg();
                JError::raiseError(500, $err);
            }

            $query = 'UPDATE '.$this->_tbl.' SET '.$column.' = '.(int) $this->$column.' WHERE '.$this->_tbl_key.' = '.$this->_db->Quote($row->$k);
            $this->_db->setQuery($query);

            if (!$this->_db->query()) {
                $err = $this->_db->getErrorMsg();
                JError::raiseError(500, $err);
            }

            $this->$column = $row->$column;
        } else {
            $query = 'UPDATE '.$this->_tbl.' SET '.$column.' = '.(int) $this->$column.' WHERE '.$this->_tbl_key.' = '.$this->_db->Quote($this->$k);
            $this->_db->setQuery($query);

            if (!$this->_db->query()) {
                $err = $this->_db->getErrorMsg();
                JError::raiseError(500, $err);
            }
        }

        return true;
    }
}

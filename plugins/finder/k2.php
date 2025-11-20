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

// Load the base adapter.
require_once JPATH_ADMINISTRATOR.'/components/com_finder/helpers/indexer/adapter.php';

class plgFinderK2 extends Joomla\Component\Finder\Administrator\Indexer\Adapter
{
    public $old_access;

    public $old_cataccess;

    public $params;

    public $db;

    protected $context = 'K2';

    protected $extension = 'com_k2';

    protected $layout = 'item';

    protected $type_title = 'K2 Item';

    protected $table = '#__k2_items';

    protected $state_field = 'published';

    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
        if (PHP_SAPI === 'cli') {
            Joomla\CMS\Plugin\PluginHelper::importPlugin('system', 'k2');
            JEventDispatcher::getInstance()->trigger('onAfterInitialise');
        }

        $this->loadLanguage();
    }

    protected function setup()
    {
        // Load dependent classes.
        include_once JPATH_SITE.'/components/com_k2/helpers/route.php';

        return true;
    }

    public function onFinderAfterDelete($context, $table)
    {
        if ($context == 'com_k2.item') {
            $id = $table->id;
        } elseif ($context == 'com_finder.index') {
            $id = $table->link_id;
        } else {
            return true;
        }

        // Remove the items.
        return $this->remove($id);
    }

    public function onFinderAfterSave($context, $row, $isNew)
    {
        // We only want to handle items here
        if ($context == 'com_k2.item') {
            // Check if the access levels are different
            if (!$isNew && $this->old_access != $row->access) {
                // Process the change.
                $this->itemAccessChange($row);
            }

            // Reindex the item
            $this->reindex($row->id);
        }

        // Check for access changes in the category
        if ($context == 'com_k2.category') {
            // Update the state
            $this->categoryStateChange([$row->id], $row->published);

            // Check if the access levels are different
            if (!$isNew && $this->old_cataccess != $row->access) {
                $this->categoryAccessChange($row);
            }
        }

        return true;
    }

    public function onFinderBeforeSave($context, $row, $isNew)
    {
        // We only want to handle items here
        // Query the database for the old access level if the item isn't new
        if ($context == 'com_k2.item' && !$isNew) {
            $this->checkItemAccess($row);
        }

        // Check for access levels from the category
        // Query the database for the old access level if the item isn't new
        if ($context == 'com_k2.category' && !$isNew) {
            $this->checkCategoryAccess($row);
        }

        return true;
    }

    public function onFinderChangeState($context, $pks, $value)
    {
        // Items
        if ($context == 'com_k2.item') {
            $this->itemStateChange($pks, $value);
        }

        // Categories
        if ($context == 'com_k2.category') {
            $this->categoryStateChange($pks, $value);
        }
    }

    protected function index(FinderIndexerResult $finderIndexerResult, $format = 'html')
    {
        // Check if the extension is enabled
        if (Joomla\CMS\Component\ComponentHelper::isEnabled($this->extension) == false) {
            return;
        }

        // Initialize the item parameters.
        $registry = new JRegistry();
        $registry->loadString($finderIndexerResult->params);

        $finderIndexerResult->params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2', true);
        $finderIndexerResult->params->merge($registry);

        $registry = new JRegistry();
        $registry->loadString($finderIndexerResult->metadata);

        $finderIndexerResult->metadata = $registry;

        // Trigger the onContentPrepare event.
        $finderIndexerResult->summary = Joomla\Component\Finder\Administrator\Indexer\Helper::prepareContent($finderIndexerResult->summary, $finderIndexerResult->params);
        $finderIndexerResult->body = Joomla\Component\Finder\Administrator\Indexer\Helper::prepareContent($finderIndexerResult->body, $finderIndexerResult->params);

        // Build the necessary route and path information.
        $finderIndexerResult->url = $this->getURL($finderIndexerResult->id, $this->extension, $this->layout);
        $finderIndexerResult->route = K2HelperRoute::getItemRoute($finderIndexerResult->slug, $finderIndexerResult->catslug);
        $finderIndexerResult->path = Joomla\Component\Finder\Administrator\Indexer\Helper::getContentPath($finderIndexerResult->route);

        // Get the menu title if it exists.
        $title = $this->getItemMenuTitle($finderIndexerResult->url);

        // Adjust the title if necessary.
        if (!empty($title) && $this->params->get('use_menu_title', true)) {
            $finderIndexerResult->title = $title;
        }

        // Add the meta-author.
        $finderIndexerResult->metaauthor = $finderIndexerResult->metadata->get('author');

        // Add the meta-data processing instructions.
        $finderIndexerResult->addInstruction(Joomla\Component\Finder\Administrator\Indexer\Indexer::META_CONTEXT, 'metakey');
        $finderIndexerResult->addInstruction(Joomla\Component\Finder\Administrator\Indexer\Indexer::META_CONTEXT, 'metadesc');
        $finderIndexerResult->addInstruction(Joomla\Component\Finder\Administrator\Indexer\Indexer::META_CONTEXT, 'metaauthor');
        $finderIndexerResult->addInstruction(Joomla\Component\Finder\Administrator\Indexer\Indexer::META_CONTEXT, 'author');
        $finderIndexerResult->addInstruction(Joomla\Component\Finder\Administrator\Indexer\Indexer::META_CONTEXT, 'created_by_alias');
        $finderIndexerResult->addInstruction(Joomla\Component\Finder\Administrator\Indexer\Indexer::META_CONTEXT, 'extra_fields_search');

        // Translate the state. Items should only be published if the category is published.
        $finderIndexerResult->state = $this->translateState($finderIndexerResult->state, $finderIndexerResult->cat_state);

        // Translate the trash state. Items should only be accesible if the category is accessible.
        if ($finderIndexerResult->trash || $finderIndexerResult->cat_trash) {
            $finderIndexerResult->state = 0;
        }

        // Add the type taxonomy data.
        $finderIndexerResult->addTaxonomy('Type', 'K2 Item');

        // Add the author taxonomy data.
        if (!empty($finderIndexerResult->author) || !empty($finderIndexerResult->created_by_alias)) {
            $finderIndexerResult->addTaxonomy('Author', empty($finderIndexerResult->created_by_alias) ? $finderIndexerResult->author : $finderIndexerResult->created_by_alias);
        }

        // Add the category taxonomy data.
        $finderIndexerResult->addTaxonomy('K2 Category', $finderIndexerResult->category, $finderIndexerResult->cat_state, $finderIndexerResult->cat_access);

        // Add the language taxonomy data.
        $finderIndexerResult->addTaxonomy('Language', $finderIndexerResult->language);

        // Add the extra_fields data.
        $finderIndexerResult->addTaxonomy('Extra fields', $finderIndexerResult->extra_fields);

        // Get content extras.
        Joomla\Component\Finder\Administrator\Indexer\Helper::getContentExtras($finderIndexerResult);

        // Index the item.
        if (method_exists('FinderIndexer', 'getInstance')) {
            Joomla\Component\Finder\Administrator\Indexer\Indexer::getInstance()->index($finderIndexerResult);
        } else {
            Joomla\Component\Finder\Administrator\Indexer\Indexer::index($finderIndexerResult);
        }
    }

    protected function getListQuery($sql = null)
    {
        $db = Joomla\CMS\Factory::getDbo();
        // Check if we can use the supplied SQL query.
        $sql = is_a($sql, 'JDatabaseQuery') ? $sql : $db->getQuery(true);
        $sql->select('a.id, a.title, a.alias, a.introtext AS summary, a.fulltext AS body');
        $sql->select('a.published as state, a.catid, a.created AS start_date, a.created_by');
        $sql->select('a.created_by_alias, a.modified, a.modified_by, a.params');
        $sql->select('a.metakey, a.metadesc, a.metadata, a.language, a.access, a.ordering');
        $sql->select('a.publish_up AS publish_start_date, a.publish_down AS publish_end_date');
        $sql->select('a.trash, c.trash AS cat_trash');
        $sql->select('c.name AS category, c.published AS cat_state, c.access AS cat_access');
        $sql->select('a.extra_fields_search, a.extra_fields');

        // Handle the alias CASE WHEN portion of the query
        $case_when_item_alias = ' CASE WHEN ';
        $case_when_item_alias .= $sql->charLength('a.alias');
        $case_when_item_alias .= ' THEN ';
        $a_id = $sql->castAsChar('a.id');
        $case_when_item_alias .= $sql->concatenate([$a_id, 'a.alias'], ':');
        $case_when_item_alias .= ' ELSE ';
        $case_when_item_alias .= $a_id.' END as slug';
        $sql->select($case_when_item_alias);

        $case_when_category_alias = ' CASE WHEN ';
        $case_when_category_alias .= $sql->charLength('c.alias');
        $case_when_category_alias .= ' THEN ';
        $c_id = $sql->castAsChar('c.id');
        $case_when_category_alias .= $sql->concatenate([$c_id, 'c.alias'], ':');
        $case_when_category_alias .= ' ELSE ';
        $case_when_category_alias .= $c_id.' END as catslug';
        $sql->select($case_when_category_alias);

        $sql->select('u.name AS author');
        $sql->from('#__k2_items AS a');
        $sql->join('LEFT', '#__k2_categories AS c ON c.id = a.catid');
        $sql->join('LEFT', '#__users AS u ON u.id = a.created_by');

        return $sql;
    }

    protected function checkCategoryAccess($row)
    {
        $query = $this->db->getQuery(true);
        $query->select($this->db->quoteName('access'));
        $query->from($this->db->quoteName('#__k2_categories'));
        $query->where($this->db->quoteName('id').' = '.(int) $row->id);

        $this->db->setQuery($query);

        // Store the access level to determine if it changes
        $this->old_cataccess = $this->db->loadResult();
    }

    protected function categoryAccessChange($row)
    {
        $sql = clone $this->getStateQuery();
        $sql->where('c.id = '.(int) $row->id);

        // Get the access level.
        $this->db->setQuery($sql);
        $items = $this->db->loadObjectList();

        // Adjust the access level for each item within the category.
        foreach ($items as $item) {
            // Set the access level.
            $temp = max($item->access, $row->access);

            // Update the item.
            $this->change((int) $item->id, 'access', $temp);

            // Reindex the item
            $this->reindex($item->id);
        }
    }
}

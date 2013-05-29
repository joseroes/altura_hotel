<?php
/**
 * EJB - Easy Joomla Backup for Joomal! 2.5
 * License: GNU/GPL - http://www.gnu.org/licenses/gpl.html
 * Author: Viktor Vogel
 * Project page: http://joomla-extensions.kubik-rubik.de/ejb-easy-joomla-backup
 *
 * @license GNU/GPL
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.model');

class EasyJoomlaBackupModelEasyJoomlaBackup extends JModelLegacy
{
    protected $_total;
    protected $_pagination;

    function __construct()
    {
        parent::__construct();
        $mainframe = JFactory::getApplication();

        $limit = $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int');
        $limitstart = $mainframe->getUserStateFromRequest('easyjoomlabackup.limitstart', 'limitstart', 0, 'int');
        $limitstart = ($limit != 0 ? (floor($limitstart / $limit) * $limit) : 0);
        $search = $mainframe->getUserStateFromRequest('easyfrontendseo.filter.search', 'filter_search', NULL);

        $this->setState('limit', $limit);
        $this->setState('limitstart', $limitstart);
        $this->setState('filter.search', $search);
    }

    /**
     * Loads all or filtered entries from the database
     *
     * @return array
     */
    function getData()
    {
        if(empty($this->_data))
        {
            $query = $this->_db->getQuery(true);

            $query->select('*');
            $query->from('#__easyjoomlabackup AS a');

            $search = $this->getState('filter.search');

            if(!empty($search))
            {
                $search = $this->_db->Quote('%'.$this->_db->escape($search, true).'%');
                $query->where('(a.date LIKE '.$search.') OR (a.comment LIKE '.$search.') OR (a.type LIKE '.$search.') OR (a.size LIKE '.$search.') OR (a.name LIKE '.$search.')');
            }

            $query->order($this->_db->escape('date DESC'));

            $this->_data = $this->_getList($query, $this->getState('limitstart'), $this->getState('limit'));
        }

        return $this->_data;
    }

    /**
     * Creates the pagination in the footer of the list
     *
     * @return JPagination
     */
    function getPagination()
    {
        if(empty($this->_pagination))
        {
            jimport('joomla.html.pagination');
            $this->_pagination = new JPagination($this->getTotal(), $this->getState('limitstart'), $this->getState('limit'));
        }

        return $this->_pagination;
    }

    /**
     * Calculates the total number of all loaded entries
     *
     * @return int
     */
    function getTotal()
    {
        if(empty($this->_total))
        {
            $query = $this->_db->getQuery(true);

            $query->select('*');
            $query->from('#__easyjoomlabackup AS a');

            $search = $this->getState('filter.search');

            if(!empty($search))
            {
                $search = $this->_db->Quote('%'.$this->_db->escape($search, true).'%');
                $query->where('(a.date LIKE '.$search.') OR (a.comment LIKE '.$search.') OR (a.type LIKE '.$search.') OR (a.size LIKE '.$search.') OR (a.name LIKE '.$search.')');
            }

            $query->order($this->_db->escape('date ASC'));

            $this->_total = $this->_getListCount($query);
        }

        return $this->_total;
    }

    /**
     * Finds backup files without entry in the database or entries without backup files
     *
     * @return boolean
     */
    function discover()
    {
        // Get all backup files
        $backup_files = array();
        $dir = @opendir(JPATH_ADMINISTRATOR.'/components/com_easyjoomlabackup/backups/');

        while($file = readdir($dir))
        {
            if(substr(strtolower($file), -3) == 'zip')
            {
                $backup_files[] = $file;
            }
        }

        closedir($dir);

        // Get all entries
        $entries = $this->getData();

        if(empty($backup_files) AND empty($entries))
        {
            return false;
        }
        else
        {
            $entries_array = array();

            // Check whether an entry has to be removed - case: backup archive does not exist but entry in the database does
            foreach($entries as $entry)
            {
                $entries_array[] = $entry->name;

                if(!in_array($entry->name, $backup_files))
                {
                    $this->removeEntry($entry->id);
                    continue;
                }
            }

            // Check whether an entry has to be added - case: entry in the database does not exist but backup archive does
            foreach($backup_files as $backup_file)
            {
                if(!in_array($backup_file, $entries_array))
                {
                    $this->addEntry($backup_file);
                    continue;
                }
            }
        }

        return true;
    }

    /**
     * Removes an entry from the database if the backup file does not exist
     *
     * @param int $id
     * @return boolean
     */
    private function removeEntry($id)
    {
        $query = "DELETE FROM ".$this->_db->quoteName('#__easyjoomlabackup')." WHERE ".$this->_db->quoteName('id')." = ".$this->_db->quote($id);
        $this->_db->setQuery($query);
        $this->_db->query();

        return;
    }

    /**
     * Adds an entry if the backup file does exist without a corresponding entry in the database
     *
     * @param string $file_name
     * @return boolean
     */
    private function addEntry($file_name)
    {
        $date = date("Y-m-d H:i:s.", filemtime(JPATH_ADMINISTRATOR.'/components/com_easyjoomlabackup/backups/'.$file_name));
        $size = filesize(JPATH_ADMINISTRATOR.'/components/com_easyjoomlabackup/backups/'.$file_name);

        $query = "INSERT INTO ".$this->_db->quoteName('#__easyjoomlabackup')." (".$this->_db->quoteName('date').", ".$this->_db->quoteName('comment').", ".$this->_db->quoteName('type').", ".$this->_db->quoteName('size').", ".$this->_db->quoteName('duration').", ".$this->_db->quoteName('name').") VALUES (".$this->_db->quote($date).", '', ".$this->_db->quote('discovered').", ".$this->_db->quote($size).", '', ".$this->_db->quote($file_name).")";
        $this->_db->setQuery($query);
        $this->_db->query();

        return;
    }

}

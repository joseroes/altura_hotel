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

class EasyJoomlaBackupControllerCreatebackup extends JControllerLegacy
{
    protected $_input;

    function __construct()
    {
        parent::__construct();

        $this->_input = JFactory::getApplication()->input;
    }

    /**
     * Loads the full backup template
     *
     * @return
     */
    public function fullbackup()
    {
        if(!JFactory::getUser()->authorise('easyjoomlabackup.fullbackup', 'com_easyjoomlabackup'))
        {
            return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
        }

        $this->_input->set('view', 'createbackup');
        $this->_input->set('hidemainmenu', 1);
        parent::display();
    }

    /**
     * Loads the database backup template
     *
     * @return
     */
    public function databasebackup()
    {
        if(!JFactory::getUser()->authorise('easyjoomlabackup.databasebackup', 'com_easyjoomlabackup'))
        {
            return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
        }

        $this->_input->set('view', 'createbackup');
        $this->_input->set('hidemainmenu', 1);
        parent::display();
    }

    /**
     * Loads the file backup template
     *
     * @return
     */
    public function filebackup()
    {
        if(!JFactory::getUser()->authorise('easyjoomlabackup.filebackup', 'com_easyjoomlabackup'))
        {
            return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
        }

        $this->_input->set('view', 'createbackup');
        $this->_input->set('hidemainmenu', 1);
        parent::display();
    }

    /**
     * Starts the full backup process with an ACL check
     *
     * @return
     */
    public function backup_create_fullbackup()
    {
        if(!JFactory::getUser()->authorise('easyjoomlabackup.fullbackup', 'com_easyjoomlabackup'))
        {
            return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
        }

        $this->backup_create('fullbackup');
    }

    /**
     * Starts the database backup process with an ACL check
     *
     * @return
     */
    public function backup_create_databasebackup()
    {
        if(!JFactory::getUser()->authorise('easyjoomlabackup.databasebackup', 'com_easyjoomlabackup'))
        {
            return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
        }

        $this->backup_create('databasebackup');
    }

    /**
     * Starts the file backup process with an ACL check
     *
     * @return
     */
    public function backup_create_filebackup()
    {
        if(!JFactory::getUser()->authorise('easyjoomlabackup.filebackup', 'com_easyjoomlabackup'))
        {
            return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
        }

        $this->backup_create('filebackup');
    }

    /**
     * Creates the backup archive in dependence on the submitted type
     *
     * @param string $type
     */
    private function backup_create($type)
    {
        JSession::checkToken() OR jexit('Invalid Token');

        // Try to increase all relevant settings to prevent timeouts on big sites
        ini_set('memory_limit', '128M');
        ini_set('error_reporting', 0);
        @set_time_limit(3600);

        $model = $this->getModel('createbackup');

        if($model->createBackup($type))
        {
            $msg = JText::_('COM_EASYJOOMLABACKUP_BACKUP_SAVED');
            $type = 'message';
        }
        else
        {
            $msg = JText::_('COM_EASYJOOMLABACKUP_BACKUP_SAVED_ERROR');
            $type = 'error';
        }

        $this->setRedirect('index.php?option=com_easyjoomlabackup', $msg, $type);
    }

    /**
     * Discovers backup files without database entries or database entries without corresponding backup archives
     */
    public function discover()
    {
        JSession::checkToken() OR jexit('Invalid Token');

        if(!JFactory::getUser()->authorise('easyjoomlabackup.discover', 'com_easyjoomlabackup'))
        {
            return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
        }

        $model = $this->getModel('easyjoomlabackup');

        if(!$model->discover())
        {
            $msg = JText::_('COM_EASYJOOMLABACKUP_DISCOVER_NOTICE');
            $type = 'notice';
        }
        else
        {
            $msg = JText::_('COM_EASYJOOMLABACKUP_DISCOVER_SUCCESS');
            $type = 'message';
        }

        $this->setRedirect(JRoute::_('index.php?option=com_easyjoomlabackup', false), $msg, $type);
    }

    /**
     * Deletes selected entries and the corresponding backup archives
     */
    public function remove()
    {
        JSession::checkToken() OR jexit('Invalid Token');

        if(!JFactory::getUser()->authorise('core.delete', 'com_easyjoomlabackup'))
        {
            return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
        }

        $model = $this->getModel('createbackup');

        if(!$model->delete())
        {
            $msg = JText::_('COM_EASYJOOMLABACKUP_BACKUP_DELETED_ERROR');
            $type = 'error';
        }
        else
        {
            $msg = JText::_('COM_EASYJOOMLABACKUP_BACKUP_DELETED');
            $type = 'message';
        }

        $this->setRedirect(JRoute::_('index.php?option=com_easyjoomlabackup', false), $msg, $type);
    }

    /**
     * Calls the download screen for the selected backup entry
     */
    public function download()
    {
        if(!JFactory::getUser()->authorise('easyjoomlabackup.download', 'com_easyjoomlabackup'))
        {
            return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
        }

        $model = $this->getModel('createbackup');

        if(!$model->download())
        {
            $msg = JText::_('COM_EASYJOOMLABACKUP_DOWNLOAD_ERROR');
            $type = 'error';
            $this->setRedirect(JRoute::_('index.php?option=com_easyjoomlabackup', false), $msg, $type);
        }
    }

    /**
     * Aborts the selected backup process
     */
    public function cancel()
    {
        $msg = JText::_('COM_EASYJOOMLABACKUP_BACKUP_CANCELLED');
        $this->setRedirect('index.php?option=com_easyjoomlabackup', $msg, 'notice');
    }

}

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

class EasyJoomlaBackupViewEasyJoomlaBackup extends JViewLegacy
{
    protected $_state;

    function display($tpl = null)
    {
        JToolBarHelper::title(JText::_('COM_EASYJOOMLABACKUP')." - ".JText::_('COM_EASYJOOMLABACKUP_SUBMENU_ENTRIES'), 'easyjoomlabackup');

        if(JFactory::getUser()->authorise('easyjoomlabackup.fullbackup', 'com_easyjoomlabackup'))
        {
            JToolBarHelper::custom('fullbackup', 'new', 'new', JText::_('COM_EASYJOOMLABACKUP_FULLBACKUP'), false);
        }

        if(JFactory::getUser()->authorise('easyjoomlabackup.databasebackup', 'com_easyjoomlabackup'))
        {
            JToolBarHelper::custom('databasebackup', 'new', 'new', JText::_('COM_EASYJOOMLABACKUP_DATABASEBACKUP'), false);
        }

        if(JFactory::getUser()->authorise('easyjoomlabackup.filebackup', 'com_easyjoomlabackup'))
        {
            JToolBarHelper::custom('filebackup', 'new', 'new', JText::_('COM_EASYJOOMLABACKUP_FILEBACKUP'), false);
        }

        if(JFactory::getUser()->authorise('easyjoomlabackup.discover', 'com_easyjoomlabackup'))
        {
            JToolBarHelper::custom('discover', 'refresh', 'refresh', JText::_('COM_EASYJOOMLABACKUP_DISCOVER'), false);
        }

        if(JFactory::getUser()->authorise('core.delete', 'com_easyjoomlabackup'))
        {
            JToolBarHelper::deleteList();
        }

        if(JFactory::getUser()->authorise('core.admin', 'com_easyjoomlabackup'))
        {
            JToolBarHelper::preferences('com_easyjoomlabackup', '500');
        }

        if(JFactory::getUser()->authorise('easyjoomlabackup.download', 'com_easyjoomlabackup'))
        {
            $download_allowed = true;
        }
        else
        {
            $download_allowed = false;
        }

        $items = $this->get('Data');
        $pagination = $this->get('Pagination');
        $this->_state = $this->get('State');

        $document = JFactory::getDocument();
        $document->addStyleSheet('components/com_easyjoomlabackup/css/easyjoomlabackup.css');

        $this->assignRef('items', $items);
        $this->assignRef('pagination', $pagination);
        $this->assignRef('download_allowed', $download_allowed);

        parent::display($tpl);
    }

}

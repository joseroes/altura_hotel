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
JHtml::_('behavior.tooltip');
JHtml::_('behavior.multiselect');
?>
<form action="<?php echo JRoute::_('index.php?option=com_easyjoomlabackup'); ?>" method="post" name="adminForm" id="adminForm">
    <fieldset id="filter-bar">
        <div class="filter-search fltlft">
            <label class="filter-search-lbl" for="filter_search"<?php echo JText::_('COM_EASYJOOMLABACKUP_FILTERSEARCH'); ?></label>
            <input type="text" name="filter_search" id="filter_search" value="<?php echo $this->escape($this->_state->get('filter.search')); ?>" title="<?php echo JText::_('COM_EASYJOOMLABACKUP_FILTERSEARCH'); ?>" />
            <button type="submit" class="btn"><?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?></button>
            <button type="button" onclick="document.id('filter_search').value = '';
                    this.form.submit();"><?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?></button>
        </div>
    </fieldset>
    <div class="clr"> </div>
    <div id="editcell">
        <table id="articleList" class="adminlist">
            <thead>
                <tr>
                    <th width="20">
                        <input type="checkbox" name="checkall-toggle" value="" title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
                    </th>
                    <th width="15%">
                        <?php echo JText::_('COM_EASYJOOMLABACKUP_DATE'); ?>
                    </th>
                    <th width="35%">
                        <?php echo JText::_('COM_EASYJOOMLABACKUP_COMMENT'); ?>
                    </th>
                    <th width="10%">
                        <?php echo JText::_('COM_EASYJOOMLABACKUP_TYPE'); ?>
                    </th>
                    <th width="6%">
                        <?php echo JText::_('COM_EASYJOOMLABACKUP_SIZE'); ?>
                    </th>
                    <th width="6%">
                        <?php echo JText::_('COM_EASYJOOMLABACKUP_DURATION'); ?>
                    </th>
                    <th>
                        <?php echo JText::_('COM_EASYJOOMLABACKUP_DOWNLOAD'); ?>
                    </th>
                </tr>
            </thead>
            <?php
            $k = 0;
            $n = count($this->items);

            for($i = 0; $i < $n; $i++)
            {
                $row = $this->items[$i];
                $checked = JHTML::_('grid.id', $i, $row->id, false, 'id');
                $download = JRoute::_('index.php?option=com_easyjoomlabackup&controller=createbackup&task=download&id='.$row->id);
                ?>
                <tr class="<?php echo 'row'.$k; ?>">
                    <td>
                        <?php echo $checked; ?>
                    </td>
                    <td>
                        <span class="hasTooltip" title="<?php echo $row->date; ?>">
                            <?php echo JHTML::_('date', $row->date, JText::_('DATE_FORMAT_LC2')); ?>
                        </span>
                    </td>
                    <td>
                        <span class="hasTooltip" title="<?php echo htmlspecialchars($row->comment); ?>">
                            <?php
                            if(strlen($row->comment) > 200) :
                                echo mb_substr($row->comment, 0, 200).'...';
                            else :
                                echo $row->comment;
                            endif;
                            ?>
                        </span>
                    </td>
                    <td>
                        <span class="hasTooltip" title="<?php echo htmlspecialchars($row->type); ?>">
                            <?php
                            if($row->type == 'fullbackup') :
                                echo JText::_('COM_EASYJOOMLABACKUP_FULLBACKUP');
                            elseif($row->type == 'databasebackup') :
                                echo JText::_('COM_EASYJOOMLABACKUP_DATABASEBACKUP');
                            elseif($row->type == 'filebackup') :
                                echo JText::_('COM_EASYJOOMLABACKUP_FILEBACKUP');
                            elseif($row->type == 'discovered') :
                                echo JText::_('COM_EASYJOOMLABACKUP_DISCOVERED_ARCHIVE');
                            endif;
                            ?>
                        </span>
                    </td>
                    <td>
                        <span class="hasTooltip" title="<?php echo htmlspecialchars($row->size); ?>">
                            <?php if($row->size > 1048576) : ?>
                                <?php echo number_format($row->size / 1048576, 2, ',', '.').' MB'; ?>
                            <?php else : ?>
                                <?php echo number_format($row->size / 1024, 2, ',', '.').' KB'; ?>
                            <?php endif; ?>
                        </span>
                    </td>
                    <td>
                        <span class="hasTooltip" title="<?php echo htmlspecialchars($row->duration); ?>">
                            <?php if($row->duration > 60) : ?>
                                <?php echo floor($row->duration / 60).' m '.($row->duration % 60).' s'; ?>
                            <?php elseif($row->duration <= 60 AND $row->duration > 0) : ?>
                                <?php echo $row->duration.' s'; ?>
                            <?php else : ?>
                                <?php echo JText::_('COM_EASYJOOMLABACKUP_UNKNOWN'); ?>
                            <?php endif; ?>
                        </span>
                    </td>
                    <td>
                        <span class="hasTooltip" title="<?php echo htmlspecialchars($row->name); ?>">
                            <?php if($this->download_allowed == true) : ?>
                                <a href="<?php echo $download; ?>">
                                    <?php
                                    if(strlen($row->name) > 100) :
                                        echo substr($row->name, 0, 100).'...';
                                    else :
                                        echo $row->name;
                                    endif;
                                    ?>
                                </a>
                            <?php else : ?>
                                <?php echo $row->name; ?>
                            <?php endif; ?>
                        </span>
                    </td>
                </tr>
                <?php
                $k = 1 - $k;
            }
            ?>
            <tfoot>
                <tr>
                    <td colspan="8">
                        <?php echo $this->pagination->getListFooter(); ?>
                        <p class="footer-tip">
                            <?php if(class_exists('ZipArchive')) : ?>
                                <span class="enabled"><?php echo JText::_('COM_EASYJOOMLABACKUP_ZIPARCHIVE_ACTIVATED'); ?></span>
                            <?php else : ?>
                                <span class="disabled"><?php echo JText::_('COM_EASYJOOMLABACKUP_ZIPARCHIVE_DEACTIVATED'); ?></span>
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <input type="hidden" name="option" value="com_easyjoomlabackup" />
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="controller" value="createbackup" />
    <?php echo JHTML::_('form.token'); ?>
</form>
<div style="text-align: center; margin-top: 10px;">
    <p><?php echo JText::sprintf('COM_EASYJOOMLABACKUP_VERSION', _EASYJOOMLABACKUP_VERSION) ?></p>
</div>
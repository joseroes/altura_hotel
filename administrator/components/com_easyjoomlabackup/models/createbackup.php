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

class EasyJoomlaBackupModelCreatebackup extends JModelLegacy
{
    protected $_input;
    protected $_error;
    protected $_params;
    protected $_backup_folder;
    protected $_backup_datetime;

    function __construct()
    {
        parent::__construct();

        $this->_input = JFactory::getApplication()->input;
        $this->_params = JComponentHelper::getParams('com_easyjoomlabackup');
        $this->_backup_folder = JPATH_ADMINISTRATOR.'/components/com_easyjoomlabackup/backups/';
        $this->_backup_datetime = JFactory::getDate('now', JFactory::getApplication()->getCfg('offset'));
    }

    /**
     * Main function for the backup process
     *
     * @param string $type
     * @return boolean
     */
    function createBackup($type)
    {
        // Check whether Zip class exists
        if(class_exists('ZipArchive'))
        {
            $start = microtime(true);
            $status = true;
            $status_db = true;

            // Create name of the new archive
            $file_name = $this->createFilename();

            // Get all files and folders
            if($type == 'filebackup' OR $type == 'fullbackup')
            {
                $status = $this->createBackupZipArchiveFiles($file_name);
            }

            if($type == 'databasebackup' OR $type == 'fullbackup')
            {
                $status_db = $this->createBackupZipArchivDatabase($file_name);
            }

            // Was the zip archive created successfully?
            if(empty($status) OR empty($status_db))
            {
                return false;
            }
            else
            {
                $table = $this->getTable('createbackup', 'EasyJoomlaBackupTable');
                $data = array();

                $data['date'] = $this->_backup_datetime->toMySQL();
                $data['comment'] = $this->_input->get('comment', '', 'STRING');
                $data['type'] = $type;
                $data['name'] = $file_name;
                $data['size'] = filesize($this->_backup_folder.$file_name);
                $data['duration'] = round(microtime(true) - $start, 2);

                if(!$table->bind($data))
                {
                    $this->setError($this->_db->getErrorMsg());
                    $this->_error = 'database';
                    return false;
                }

                if(!$table->check())
                {
                    $this->setError($this->_db->getErrorMsg());
                    $this->_error = 'database';
                    return false;
                }

                if(!$table->store())
                {
                    $this->setError($this->_db->getErrorMsg());
                    $this->_error = 'database';
                    return false;
                }

                return true;
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * Creates the archive file of all files from the Joomla! installation with a possible exclusion of files and folders
     *
     * @param string $file_name
     * @return boolean
     */
    function createBackupZipArchiveFiles($file_name)
    {
        // Prepare files which should be excluded
        $exclude_files = $this->_params->get('exclude_files');

        if(!empty($exclude_files))
        {
            $exclude_files = array_map('trim', explode("\n", $exclude_files));
        }

        // Prepare folders which should be excluded
        $exclude_folders = $this->_params->get('exclude_folders');

        if(!empty($exclude_folders))
        {
            $exclude_folders = array_map('trim', explode("\n", $exclude_folders));
        }

        if(!$dir = @opendir(JPATH_ROOT))
        {
            return false;
        }

        $files_array = array();

        while($file = readdir($dir))
        {
            if(is_dir(JPATH_ROOT.'/'.$file) AND $file != '.' AND $file != '..')
            {
                // Create for all folders an own Zip Archive object to avoid memory overflow
                $zip_folder = new ZipArchive();

                if($zip_folder->open($this->_backup_folder.$file_name, ZIPARCHIVE::CREATE) !== true)
                {
                    return false;
                }
                else
                {
                    $this->zipFoldersAndFilesRecursive($zip_folder, JPATH_ROOT.'/'.$file, $file, $exclude_files, $exclude_folders, $file);
                }

                $zip_folder->close();

                if($zip_folder->status != 0)
                {
                    return false;
                }
            }
            elseif(is_file(JPATH_ROOT.'/'.$file))
            {
                // First collect all files from the root in an array and add them at once to the archive later
                $files_array[] = $file;
            }
        }

        // Add all files from the root to the archive
        if(!empty($files_array))
        {
            $zip_file = new ZipArchive();

            if($zip_file->open($this->_backup_folder.$file_name, ZIPARCHIVE::CREATE) !== true)
            {
                return false;
            }
            else
            {
                foreach($files_array as $file)
                {
                    if(!empty($exclude_files))
                    {
                        if(in_array($file, $exclude_files))
                        {
                            continue;
                        }
                    }

                    // Add the files to the zip archive and set a correct local name
                    $zip_file->addFile(JPATH_ROOT.'/'.$file, $file);
                }
            }

            $zip_file->close();

            if($zip_file->status != 0)
            {
                return false;
            }
        }

        closedir($dir);
        unset($zip_folder);
        unset($zip_file);

        return true;
    }

    /**
     * Creates a complete dump of the Joomla! database
     *
     * @param string $file_name
     * @return boolean
     */
    function createBackupZipArchivDatabase($file_name)
    {
        // SQL Dump - Backup the whole database of the Joomla! website and write it into the archive file - only if the zip archive could be created
        $zip_database = new ZipArchive();

        if($zip_database->open($this->_backup_folder.$file_name, ZIPARCHIVE::CREATE) !== true)
        {
            return false;
        }
        else
        {
            // Set a correct extension for the database dump name
            $file_name_db = str_replace('.zip', '', $file_name).'.sql';
            $this->backupDatabase($file_name_db);

            // Add file which was created from the database export to the zip archive
            $zip_database->addFile($this->_backup_folder.$file_name_db, $file_name_db);

            $zip_database->close();

            // Delete the temporary database dump files
            unlink($this->_backup_folder.$file_name_db);
        }

        if($zip_database->status != 0)
        {
            return false;
        }

        return true;
    }

    /**
     * Loads all files and (sub-)folders for the zip archive recursively
     *
     * @param object $zip
     * @param string $folder
     * @param array $exclude_files
     * @param array $exclude_folders
     * @param boolean $folder_start
     * @return boolean
     */
    function zipFoldersAndFilesRecursive($zip, $folder, $folder_relative, $exclude_files = false, $exclude_folders = false, $folder_start = false)
    {
        // Do not zip the folders of the backup archives, the cache and temp folders - only create empty folders
        $exclude_folders_create_empty = array('administrator/components/com_easyjoomlabackup/backups', 'cache', 'tmp', 'administrator/cache');

        // First check whether a root folder has to be excluded
        if(!empty($folder_start))
        {
            if(in_array($folder_start, $exclude_folders_create_empty))
            {
                $zip->addEmptyDir($folder_start);
                $zip->addFromString($folder_start.'/index.html', '');

                return;
            }

            if(!empty($exclude_folders))
            {
                if(in_array($folder_start, $exclude_folders))
                {
                    return;
                }
            }

            // Add the called folder to the zip archive
            $zip->addEmptyDir($folder_start);
        }

        // Open the called folder path
        if(!$dir = @opendir($folder))
        {
            return false;
        }

        // Go through the current folder and add data to the zip object
        while($file = readdir($dir))
        {
            if(is_dir($folder.'/'.$file) AND $file != '.' AND $file != '..')
            {
                if(in_array($folder_relative.'/'.$file, $exclude_folders_create_empty))
                {
                    $zip->addEmptyDir($folder_relative.'/'.$file);
                    $zip->addFromString($folder_relative.'/'.$file.'/index.html', '');

                    // Add a .htaccess to the backup folder to protect the archive files
                    if($folder_relative.'/'.$file == 'administrator/components/com_easyjoomlabackup/backups')
                    {
                        $zip->addFromString($folder_relative.'/'.$file.'/.htaccess', 'Deny from all');
                    }

                    continue;
                }

                if(!empty($exclude_folders))
                {
                    if(in_array($folder_relative.'/'.$file, $exclude_folders))
                    {
                        continue;
                    }
                }

                $zip->addEmptyDir($folder_relative.'/'.$file);
                $this->zipFoldersAndFilesRecursive($zip, $folder.'/'.$file, $folder_relative.'/'.$file, $exclude_files, $exclude_folders);
            }
            elseif(is_file($folder.'/'.$file))
            {
                if(!empty($exclude_files))
                {

                    if(in_array($folder_relative.'/'.$file, $exclude_files))
                    {
                        continue;
                    }
                }

                // Add the files to the zip archive and set a correct local name
                $zip->addFile($folder.'/'.$file, $folder_relative.'/'.$file);
            }
        }

        closedir($dir);

        return true;
    }

    /**
     * Creates a filename for the backup archive from the URL and the date
     *
     * @return string
     */
    function createFilename()
    {
        $root = JURI::root();

        if(!empty($root))
        {
            $url = implode('-', array_filter(explode('/', str_replace('http://', '', $root))));
        }
        else
        {
            $url = JURI::getInstance()->getHost();
        }

        $file_name = $url.'_'.$this->_backup_datetime->format('Y-m-d_H-i-s', true).'.zip';

        // If name already exists try it with another one
        if(is_file($this->_backup_folder.$file_name))
        {
            $file_name = $this->createFilename();
        }

        return $file_name;
    }

    /**
     * Creates a SQL Dump of the Joomla! database and add it directly to the archive
     *
     * @param string $file_name_dump
     * @return boolean
     */
    function backupDatabase($file_name_dump)
    {
        $db = JFactory::getDbo();
        $db->setUTF();
        $tables = $db->getTableList();
        $db_prefix = $db->getPrefix();
        $add_drop_statement = $this->_params->get('add_drop_statement');

        // Add additional database tables
        $add_db_tables = $this->_params->get('add_db_tables');

        if(!empty($add_db_tables))
        {
            $add_db_tables = array_map('trim', explode("\n", $add_db_tables));
        }
        else
        {
            $add_db_tables = array();
        }

        // Create a temporary, empty dump file. This is required to avoid memory timeouts on large databases!
        file_put_contents($this->_backup_folder.$file_name_dump, '');

        foreach($tables as $table)
        {
            if(stripos($table, $db_prefix) !== false OR in_array($table, $add_db_tables))
            {
                $data = '';

                if(!empty($add_drop_statement))
                {
                    $data .= 'DROP TABLE '.$table.';'."\n\n";
                }

                // Set the query to get the table CREATE statement.
                $db->setQuery('SHOW CREATE table '.$table);
                $row_create = $db->loadRow();

                $data .= $row_create[1].";\n\n";

                $db->setQuery('SELECT * FROM '.$table);
                $result = $db->query();
                $num_fields = $result->field_count;
                $count = $result->num_rows;

                if($count > 0)
                {
                    $data .= "INSERT INTO `$table` VALUES \n";
                    $row_list = $db->loadRowList();

                    $count_entries = 0;

                    foreach($row_list as $row)
                    {
                        $count_entries++;

                        $data .= '(';

                        for($j = 0; $j < $num_fields; $j++)
                        {
                            // Prepare data for a correct syntax
                            $row[$j] = str_replace("\\", "\\\\", $row[$j]);
                            $row[$j] = str_replace("'", "''", $row[$j]);
                            $row[$j] = preg_replace("@\r\n@", '\r\n', $row[$j]);

                            if(isset($row[$j]))
                            {
                                if(is_numeric($row[$j]))
                                {
                                    $data .= $row[$j];
                                }
                                else
                                {
                                    $data .= '\''.$row[$j].'\'';
                                }
                            }
                            else
                            {
                                $data .= '\'\'';
                            }

                            if($j < ($num_fields - 1))
                            {
                                $data .= ', ';
                            }
                        }

                        if($count_entries < $count)
                        {
                            // Add a new INSERT INTO statement after every fiftieth entry to avoid timeouts
                            if($count_entries % 50 == 0)
                            {
                                $data .= ");\n";
                                $data .= "INSERT INTO `$table` VALUES \n";
                            }
                            else
                            {
                                $data .= "),\n";
                            }
                        }
                    }

                    $data .= ");\n";
                }

                $data .= "\n\n";

                // Add the data to the temporary dump file
                file_put_contents($this->_backup_folder.$file_name_dump, $data, FILE_APPEND);
            }
        }

        return true;
    }

    /**
     * Loads the correct backup archive and creates the download process
     */
    function download()
    {
        $id = $this->_input->get('id', 0, 'INTEGER');
        $table = $this->getTable('createbackup', 'EasyJoomlaBackupTable');

        // Get the file with the correct path
        $table->load($id);
        $file = $this->_backup_folder.$table->get('name');

        if(file_exists($file))
        {
            header('Pragma: public');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Cache-Control: public');
            header('Content-Description: File Transfer');
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename='.$table->get('name'));
            header('Content-Transfer-Encoding: binary');
            header('Content-Length:'.$table->get('size'));
            ob_end_flush();
            @readfile($file);

            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Deletes backup files from the server and the corresponding database entries
     *
     * @return boolean
     */
    function delete()
    {
        $ids = $this->_input->get('id', 0, 'ARRAY');
        $table = $this->getTable('createbackup', 'EasyJoomlaBackupTable');

        foreach($ids as $id)
        {
            // Delete the backup file from the server
            $table->load($id);
            unlink($this->_backup_folder.$table->get('name'));

            if(!$table->delete($id))
            {
                $this->setError($this->_db->getErrorMsg());
                return false;
            }
        }

        return true;
    }

}

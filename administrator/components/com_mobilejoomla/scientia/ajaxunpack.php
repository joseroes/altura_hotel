<?php
/**
 * Mobile Joomla!
 * http://www.mobilejoomla.com
 *
 * @version		1.2.5.1
 * @license		GNU/GPL v2 - http://www.gnu.org/licenses/gpl-2.0.html
 * @copyright	(C) 2008-2013 Kuneri Ltd.
 * @date		March 2013
 */
defined('_JEXEC') or die('Restricted access');

function _initStatus()
{
	JError::setErrorHandling(E_ERROR, 'Message');
	@set_time_limit(1200);
	@ini_set('max_execution_time', 1200);
}
function _sendStatus()
{
	$msg = array();
	/** @var JException $error */
	foreach(JError::getErrors() as $error)
		if($error->get('level'))
			$msg[] = $error->get('message');
	if(count($msg))
		$msg = '<p>'.implode('</p><p>', $msg).'</p>';
	else
		$msg = 'ok';
	echo $msg;
	jexit();
}

	jimport('joomla.installer.helper');
	jimport('joomla.installer.installer');
	$app = JFactory::getApplication();

	_initStatus();
	$filename = $app->getUserState( "com_mobilejoomla.scientiaupdatefilename", false );
	$config = JFactory::getConfig();
	if(substr(JVERSION,0,3)=='1.5')
		$path = $config->getValue('config.tmp_path');
	else
		$path = $config->get('tmp_path');
	$path .= '/'.$filename;
	if($path)
	{
		$result = JInstallerHelper::unpack($path);
		$app->setUserState( "com_mobilejoomla.scientiaupdatefilename", false );
		if($result!==false)
		{
			$app->setUserState( "com_mobilejoomla.scientiaupdatedir", $result['dir'] );
			JFile::delete($path);
		}
	}
	else
		JError::raiseWarning(1, JText::_('COM_MJ__UPDATE_UNKNOWN_PATH'));
	_sendStatus();

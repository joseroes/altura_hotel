<?php
/**
* plg_easylanguage
* @author		isApp.it Team
* @copyright	Copyright (C) 2011 isApp.it. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @link			http://wwww.isapp.it
*/
// no direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');
jimport('joomla.language.helper');

class plgSystemEasyLanguage extends JPlugin
{
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
	}
	
	function onAfterRender()
	{
		if (JFactory::getApplication()->isAdmin() ) return true;
	
		$buffer = JResponse::getBody();
	
		if ( strpos( $buffer, '{lang' ) === false ) return true;
	
		$regexTextarea = "#<textarea(.*?)>(.*?)<\/textarea>#is";
		$regexInput = "#<input(.*?)>#is";
	
		$matches = array();
		preg_match_all($regexTextarea, $buffer, $matches, PREG_SET_ORDER);
		$textarea = array();
		foreach ($matches as $key => $match) {
			if(strpos( $match[0], '{lang' ) !== false) {
				$textarea[$key] = $match[0];
				$buffer = str_replace($textarea[$key], '~^t'.$key.'~', $buffer);
			}
		}
	
		$matches = array();
		preg_match_all($regexInput, $buffer, $matches, PREG_SET_ORDER);
		$input = array();
		foreach ($matches as $key => $match) {
			if(
				(strpos( $match[0], 'type="password"' ) !== false || 
				strpos( $match[0], 'type="text"' ) !== false) && 
				strpos( $match[0], '{lang' ) !== false) {
				$input[$key] = $match[0];
				$buffer = str_replace($input[$key], '~^i'.$key.'~', $buffer);
			}
		}
	
		if (strpos( $buffer, '{lang' ) !== false) {
			$buffer = plgSystemEasyLanguage::filterText($buffer);
	
			if ($textarea) {
				foreach ($textarea as $key => $t) {
					$buffer = str_replace('~^t'.$key.'~', $t, $buffer);
				}
				unset($textarea);
			}
			if ($input) {
				foreach ($input as $key => $i) {
					$buffer = str_replace('~^i'.$key.'~', $i, $buffer);
				}
				unset($input);
			}
			JResponse::setBody($buffer);
		}
	
		unset($buffer);
	}
	
	static function getLagnCode() {
		$lang_codes = JLanguageHelper::getLanguages('lang_code');
		$lang_code 	= $lang_codes[JFactory::getLanguage()->getTag()]->sef;
		return $lang_code;
	}
	
	static function filterText($text) {
		if ( strpos( $text, '{lang' ) === false ) return $text;
		$lang_code = plgSystemEasyLanguage::getLagnCode();
		$regex = "#{lang ".$lang_code."}(.*?){\/lang}#is";
		$text = preg_replace($regex,'$1', $text);
		$regex = "#{lang [^}]+}.*?{\/lang}#is";
		$text = preg_replace($regex,'', $text);
		return $text;
	}
}
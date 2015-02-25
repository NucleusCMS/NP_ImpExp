<?php
// vim: tabstop=2:shiftwidth=2

/**
  * Template.php ($Revision: 1.6 $)
  * 
  * by hsur ( http://blog.cles.jp/np_cles )
  * $Id: Template.php,v 1.6 2006/05/05 14:07:49 hsur Exp $
*/

/*
  * Copyright (C) 2006 CLES. All rights reserved.
  *
  * This program is free software; you can redistribute it and/or
  * modify it under the terms of the GNU General Public License
  * as published by the Free Software Foundation; either version 2
  * of the License, or (at your option) any later version.
  * 
  * This program is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  * GNU General Public License for more details.
  * 
  * You should have received a copy of the GNU General Public License
  * along with this program; if not, write to the Free Software
  * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301 USA
  * 
  * In addition, as a special exception, cles( http://blog.cles.jp/np_cles ) gives
  * permission to link the code of this program with those files in the PEAR
  * library that are licensed under the PHP License (or with modified versions
  * of those files that use the same license as those files), and distribute
  * linked combinations including the two. You must obey the GNU General Public
  * License in all respects for all of the code used other than those files in
  * the PEAR library that are licensed under the PHP License. If you modify
  * this file, you may extend this exception to your version of the file,
  * but you are not obligated to do so. If you do not wish to do so, delete
  * this exception statement from your version.
*/

class cles_Template {
	var $defaultLang = 'japanese-utf8';
	var $defalutPattern = '#{{(.*?)(\|)?}}#ie';
	var $lang;
	var $templateDir;

	function cles_Template($templateDir) {
		global $CONF;
		$this->templateDir = $templateDir;
		$this->lang = str_replace( array('\\','/'), '', getLanguageName());
	}

	function fetch($name, $dir = null, $suffix = 'html') {
		$path = $this->templateDir.'/'.( $dir ? strtolower($dir) . '/' : '' ).strtolower($name).'_'.$this->lang.( $suffix ? '.'.strtolower($suffix) : '' );
		if ( ! file_exists($path) ){
			$path = $this->templateDir.'/'.( $dir ? strtolower($dir) . '/' : '' ).strtolower($name).'_'.$this->defaultLang.( $suffix ? '.'.strtolower($suffix) : '' );
			if ( ! file_exists($path) )
				return '';
		}
		
		$fsize = filesize($path);
		if ($fsize <= 0) return '';
		
		$fd = fopen($path, 'r');
		$contents = fread($fd, $fsize);
		fclose($fd);
		return $contents;
	}
	
	function fill($template, $values, $default = null) {
		if( $default )
			return preg_replace($this->defalutPattern, 'isset($values["$1"]) ? ("$2" ? htmlspecialchars($values["$1"], ENT_QUOTES) : $values["$1"]) : $default', $template);
		if( $default === null )
			return preg_replace($this->defalutPattern, '("$2") ? htmlspecialchars($values["$1"], ENT_QUOTES) : $values["$1"]', $template);
		return preg_replace($this->defalutPattern, 'isset($values["$1"]) ? ("$2" ? htmlspecialchars($values["$1"], ENT_QUOTES) : $values["$1"]) : "{{$1}}" ', $template);
	}
}

?>

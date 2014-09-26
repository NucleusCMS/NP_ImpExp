<?php
// vim: tabstop=2:shiftwidth=2

/**
  * sharedlibs.php ($Revision: 1.7 $)
  * 
  * by hsur ( http://blog.cles.jp/np_cles )
  * $Id: sharedlibs.php,v 1.7 2006/03/19 07:06:44 hsur Exp $
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

if (!defined('NP_SHAREDLIBS_LOADED')) {
	if (!defined('PATH_SEPARATOR')) {
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			define('PATH_SEPARATOR', ';');
		} else {
			define('PATH_SEPARATOR', ':');
		}
	}
	ini_set('include_path', dirname(__FILE__).PATH_SEPARATOR.ini_get('include_path'));

	define('NP_SHAREDLIBS_LOADED', true);
}
?>
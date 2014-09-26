<?php
// vim: tabstop=2:shiftwidth=2

/**
  * index.php ($Revision: 1.10 $)
  * 
  * by hsur ( http://blog.cles.jp/np_cles )
  * $Id: index.php,v 1.10 2006/10/16 21:32:29 hsur Exp $
*/

/*
  * Copyright (C) 2005-2006 CLES. All rights reserved.
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

$strRel = '../../../';
include ($strRel.'config.php');
include ($DIR_LIBS.'PLUGINADMIN.php');

require_once($DIR_PLUGINS . 'sharedlibs/sharedlibs.php');
require_once('cles/Feedback.php');
require_once('cles/Template.php');

//sendContentType('application/xhtml+xml', 'admin-impexp', _CHARSET);

if (!($member->isLoggedIn() && $member->isAdmin())){
	$oPluginAdmin->start();
	echo '<p>' . _ERROR_DISALLOWED . '</p>';
	$oPluginAdmin->end();
	exit;
}

// create the admin area page
$oPluginAdmin = new PluginAdmin('ImpExp');

//action
$action = requestVar('action');
$aActionsNotToCheck = array(
	'',
	'report',
);
if (!in_array($action, $aActionsNotToCheck)) {
	if (!$manager->checkTicket()) doError(_ERROR_BADTICKET);
}

switch ($action) {
	case 'export':
		$blog = requestVar('blog');
		$stripbr = requestVar('blog') ? true : false;
		$filename = 'export_'. $blog. '_' . strftime("%Y%m%d") . '.txt';
		
		header('Content-Type: text/plain');
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('Expires: 0');
		header('Pragma: no-cache');
		
		$oPluginAdmin->plugin->exportEntry($blog, $stripbr);
		
		exit(0);
}

// html start
$oPluginAdmin->start();
$fb =& new cles_Feedback($oPluginAdmin);

$templateEngine =& new cles_Template(dirname(__FILE__).'/template');
define('NP_IMPEXP_TEMPLATEDIR_INDEX', 'index');
$tplVars = array(
	'indexurl' => serverVar('PHP_SELF'),
	'optionurl' => $CONF['AdminURL'] . 'index.php?action=pluginoptions&amp;plugid=' . $oPluginAdmin->plugin->getid(),
	'actionurl' => $CONF['ActionURL'],
	'uploadedFileName' => NP_IMPEXP_UPLOADED_FILENAME,
	'charset' => _CHARSET,
	'ticket' => $manager->_generateTicket(),
);

// menu
$menu = $templateEngine->fetch('menu', NP_IMPEXP_TEMPLATEDIR_INDEX);
echo $templateEngine->fill($menu, $tplVars, false);

switch ($action) {
	case 'report' :
		$fb->printForm('');
		break;
		
	case 'import':
		echo $templateEngine->fetch('doimport_header', NP_IMPEXP_TEMPLATEDIR_INDEX);
		echo $oPluginAdmin->plugin->importFromFile();
		echo $templateEngine->fetch('doimport_footer', NP_IMPEXP_TEMPLATEDIR_INDEX);
		break;
		
	default :
		$blogs = $oPluginAdmin->plugin->getBloglist();
		$tplVars['blogselect'] .= '<select name="blog">';
		foreach($blogs as $id => $name){
			$tplVars['blogselect'] .= '<option value="'.$id.'">'.$name.'</option>';
		}		
		$tplVars['blogselect'] .= '</select>';

		$content = $templateEngine->fetch('overview', NP_IMPEXP_TEMPLATEDIR_INDEX);
		echo $templateEngine->fill($content, $tplVars, null);
		
		break;
}

$oPluginAdmin->end();

?>

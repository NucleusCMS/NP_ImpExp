<?php
// vim: tabstop=2:shiftwidth=2

/**
  * NP_ImpExp ($Revision: 1.59 $)
  * by hsur ( http://blog.cles.jp/np_cles )
  * 
  * $Id: NP_ImpExp.php,v 1.59 2006/10/16 21:35:39 hsur Exp $
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

// load class
require_once(dirname(__FILE__).'/sharedlibs/sharedlibs.php');
require_once('cles/Template.php');
require_once('cles/MTFileParser.php');

define('NP_IMPEXP_UPLOADED_FILENAME', 'mtfile');

class NP_ImpExp extends NucleusPlugin {

    // name of plugin
    function getName() {
        return 'Import/Export';
    }

    // author of plugin
    function getAuthor() {
        return 'cles';
    }

    // an URL to the plugin website
    // can also be of the form mailto:foo@bar.com
    function getURL() {
        return 'http://blog.cles.jp/np_cles/category/31/subcatid/14';
    }

    // version of the plugin
    function getVersion() {
        return '1.60';
    }

    // a description to be shown on the installed plugins listing
    function getDescription() {
        return NP_IMPEXP_description ;
    }
    
    function supportsFeature($what) {
        switch($what){
            case 'SqlTablePrefix':
            case 'SqlApi':
            case 'HelpPage':
            return 1;
            default:
            return 0;
        }
    }
    
    function getEventList() {
        return array('QuickMenu');
    }

    function getMinNucleusVersion() { return 350; }
    function getMinNucleusPatchLevel() { return 0; }
    
    function hasAdminArea() { return 1; }

    function event_QuickMenu(&$data) {
        global $member, $nucleus, $blogid;
        
        // only show to admins
        if (!$member->isLoggedIn() || !$member->isAdmin()) return;

        array_push(
            $data['options'],
            array(
                'title' => 'Import / Export',
                'url' => $this->getAdminURL(),
                'tooltip' => 'Import / Export'
            )
        );
    }
    
    function init(){
        // include language file for this plugin 
        $lang_name = str_replace( array('\\','/'), '', getLanguageName());
        $NP_ImpExp_dir = $this->getDirectory();
        if (is_file("{$NP_ImpExp_dir}language/{$lang_name}.php")) 
            @ include_once("{$NP_ImpExp_dir}language/{$lang_name}.php");
    }
        
    function importFromFile() {
        global $CONF;

        $upInfo = postFileInfo(NP_IMPEXP_UPLOADED_FILENAME);
        $filename = $upInfo['name'];
        $filesize = $upInfo['size'];
        $filetype = $upInfo['type'];
        $myfile = $upInfo['tmp_name'];
        if ($filesize > $CONF['MaxUploadSize']) {
            $this->error(_ERROR_FILE_TOO_BIG);
        }
        if (!preg_match("/^text\//i", $filetype)) {
            return _ERROR_BADFILETYPE . "($filetype)";
        }
        if (!is_uploaded_file($myfile)) {
            return _ERROR_BADREQUEST;
        }
        if (!is_file($myfile)) {
            return 'File Upload Error';
        }
        
        $parser = new cles_MTFileParser($myfile);
        
        // set encoding
        if($fromEncoding = requestVar('encoding') ){
            $parser->fromEncoding = $fromEncoding;
        }
        $parser->toEncoding = _CHARSET;
        
        $this->blogid = requestVar('blog');
        $this->stripBr = 1;
        
        $func = array(&$this, 'importEntry');
        $parser->parse($func);
        
        @unlink($myfile);
    }
    
    function importEntry($entry){
        global $member, $manager;
        $blog =& $manager->getBlog($this->blogid);
        
        list($date, $time, $ampm) = explode(' ', $entry['DATE'], 3);
        list($mm, $dd, $yyyy) = explode('/', $date, 3);
        list($hh, $ii, $ss) = explode(':', $time, 3);
        if( strtolower(trim($ampm)) == 'pm' ) $hh += 12;
        $timestamp = $blog->getCorrectTime(mktime( $hh, $ii, $ss, $mm, $dd, $yyyy));
        
        if (!$entry['PRIMARY CATEGORY']){
            $entry['PRIMARY CATEGORY'] = $entry['CATEGORY'];
        }
        if ($entry['PRIMARY CATEGORY'] == null) { $entry['PRIMARY CATEGORY'] = ''; }
                
        if( $blog->isValidCategory($entry['PRIMARY CATEGORY']) ){
            $catid = $entry['PRIMARY CATEGORY'];
        } else {
            $catid = $blog->getCategoryIdFromName($entry['PRIMARY CATEGORY']);
        }
        
        if( MEMBER::exists($entry['AUTHOR']) ){
            $m = MEMBER::createFromName($entry['AUTHOR']);
            $memberid = $m->getId();
        } else {
            $memberid = $member->getId();
        }
        
        if( $this->stripBr ){
            $entry['BODY'] = preg_replace("/<br ?\/?>([\r\n])/","$1",$entry['BODY']);
            $entry['EXTENDED BODY'] = preg_replace("/<br ?\/?>([\r\n])/","$1",$entry['EXTENDED BODY']);
        }
        
        if( (!$blog->convertBreaks()) && ($entry['CONVERT BREAKS']) ){
            $entry['BODY'] = addBreaks($entry['BODY']);
            $entry['EXTENDED BODY'] = addBreaks($entry['EXTENDED BODY']);
        }

        $itemid = $blog->additem(
            $catid, 
            $entry['TITLE'], 
            $entry['BODY'], 
            $entry['EXTENDED BODY'], 
            $this->blogid, 
            $memberid, 
            $timestamp, 
            $entry['ALLOW COMMENTS'] == '1' ? 0 : 1, #closed
            $entry['STATUS'] == 'Publish' ? 0 : 1 #draft
        );
        echo "Item: {$entry['TITLE']} itemid:{$itemid}<br />";
        
        if( $entry['COMMENT'] ){
            foreach($entry['COMMENT'] as $comment)
                echo $this->importComment($itemid, $comment);
        }
        
        if( $entry['PING'] ){
            foreach($entry['PING'] as $ping)
            echo $this->importPing($itemid, $ping);
        }
    }
    
    function importPing($itemid, $ping){
        global $manager;
        $blog =& $manager->getBlog($this->blogid);
        
        list($date, $time, $ampm) = explode(' ', $ping['DATE'], 3);
        list($mm, $dd, $yyyy) = explode('/', $date, 3);
        list($hh, $ii, $ss) = explode(':', $time, 3);
        if( strtolower(trim($ampm)) == 'pm' ) $hh += 12;
        $timestamp = $blog->getCorrectTime(mktime( $hh, $ii, $ss, $mm, $dd, $yyyy));
        $timestamp = date('Y/m/d H:i:s', $timestamp);
            
        $query = sprintf('INSERT INTO '.sql_table('plugin_tb').' (tb_id, url, title, excerpt, blog_name, timestamp) '
            . "VALUES ('%s', '%s', '%s', '%s', '%s', '%s')"
            , sql_real_escape_string( intval($itemid) )
            , sql_real_escape_string( $ping['URL'] )
            , sql_real_escape_string( $ping['TITLE'] )
            , sql_real_escape_string( strip_tags($ping['EXCERPT']) )
            , sql_real_escape_string( $ping['BLOG NAME'] )
            , sql_real_escape_string( $timestamp )
            );
        @sql_query($query);
        $tbid = sql_insert_id();
        
        echo "Trackback: {$ping['TITLE']} tbid:{$tbid}<br />";
    }

    function importComment($itemid, $comment){
        global $manager;
        $blog =& $manager->getBlog($this->blogid);
        $name = '';
        if( MEMBER::exists($comment['AUTHOR']) ){
            $m = MEMBER::createFromName($comment['AUTHOR']);
            $memberid = $m->getId();
            $name = '';
        } else {
            $memberid = 0;
            $name = $comment['AUTHOR'];
        }
        
        if($comment['EMAIL']) $url = $comment['EMAIL'];
        if($comment['URL']) $url = $comment['URL'];

        list($date, $time, $ampm) = explode(' ', $comment['DATE'], 3);
        list($mm, $dd, $yyyy) = explode('/', $date, 3);
        list($hh, $ii, $ss) = explode(':', $time, 3);
        if( strtolower(trim($ampm)) == 'pm' ) $hh += 12;
        $timestamp = $blog->getCorrectTime(mktime( $hh, $ii, $ss, $mm, $dd, $yyyy));
        $timestamp = date('Y/m/d H:i:s', $timestamp);

        $query = sprintf('INSERT INTO '.sql_table('comment').' (CUSER, CMAIL, CMEMBER, CBODY, CITEM, CTIME, CHOST, CIP, CBLOG) '
            . "VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')"
            , sql_real_escape_string( $name )
            , sql_real_escape_string( $url )
            , sql_real_escape_string( $memberid )
            , sql_real_escape_string( nl2br(strip_tags($comment['COMMENT'])) )
            , sql_real_escape_string( $itemid )
            , sql_real_escape_string( $timestamp )
            , sql_real_escape_string( $comment['IP'] )
            , sql_real_escape_string( $comment['IP'] )
            , sql_real_escape_string( $this->blogid )
        );
        @sql_query($query);
        $cid = sql_insert_id();
        echo "Comment: {$name}/{$memberid} cid:{$cid}<br />";
    }
    
    function exportEntry($blogid, $stripbr){
        global $manager;
        $delim_expr = "/^\-{5,8}$/mi";
        if( ! BLOG :: existsID($blogid) ){
            echo 'Error: No Such Blog.';
            return;
        }
        
        global $member;
        $member_bk = $member;
        $member =& MEMBER::createFromID(-1);

        $tpl = new cles_Template(dirname(__FILE__).'/impexp/template');
        $entryTpl = $tpl->fetch('entry', strtolower(__CLASS__), 'txt');
        $commentTpl = $tpl->fetch('comment', strtolower(__CLASS__), 'txt');
        $tbTpl = $tpl->fetch('tb', strtolower(__CLASS__), 'txt');
        
        $blog = new BLOG($blogid);
        $members = array();
        $categories = array();
        
        $query = 'SELECT inumber as itemid, ititle as title, ibody as body, imore as more, icat as catid, iclosed as closed, idraft as draft, iauthor as author, UNIX_TIMESTAMP(itime) as ts FROM ' . sql_table('item') . ' WHERE iblog = ' . $blogid . ' order by itime';
        $res = sql_query($query);
        while( $item = sql_fetch_object($res) ){
            $itemVar = array();
            $params = array('blog' => &$blog, 'item' => &$item);
            $manager->notify('PreItem', $params);

            // comment
            $commentQuery = 'SELECT * , UNIX_TIMESTAMP(ctime) as ts FROM ' . sql_table('comment') . ' WHERE citem = ' . $item->itemid . ' order by ctime';
            $commentRes = sql_query($commentQuery);
            while( $comment = sql_fetch_assoc($commentRes) ){
                $commentVar = array();
                
                if( $memberid = $comment['cmember'] ){
                    if( $members[$memberid] ){
                        $commentVar['author'] = $members[$memberid]->getRealName();
                    } else {
                        $members[$memberid] = MEMBER::createFromID($memberid);
                        $commentVar['author'] = $members[$memberid]->getRealName();
                    }
                } else {
                    $commentVar['author'] = $comment['cuser'];
                }
                
                if( isValidMailAddress( $comment['cmail']) ){
                    $commentVar['email'] = $comment['cmail'];
                    $commentVar['url'] = '';
                } else {
                    $commentVar['email'] = '';
                    $commentVar['url'] = $comment['cmail'];
                }
                
                $timestamp = $blog->getCorrectTime($comment['ts']);
                $amOrPm = ( strftime('%H', $timestamp) < 13 ) ? 'AM' : 'PM'; 
                $commentVar['date'] = strftime('%m/%d/%Y %I:%M:%S '.$amOrPm, $timestamp);

                $commentVar['ip'] = $comment['cip'];
                $commentVar['comment'] = preg_replace_callback($delim_expr, array(&$this, '_convertDelim'), $comment['cbody']);
                $commentVar['comment'] = strip_tags($commentVar['comment']);
                
                $itemVar['comment'] .=  $tpl->fill($commentTpl, $commentVar, null);
            }

            //tb
            $tbQuery = 'SELECT * , UNIX_TIMESTAMP(timestamp) as ts FROM ' . sql_table('plugin_tb') . ' WHERE tb_id = ' . $item->itemid. ' order by timestamp';
            $tbRes = sql_query($tbQuery);
            while( $tb = sql_fetch_assoc($tbRes) ){
                if($tb['block']) continue;                
                $tbVar = array();
                
                $tbVar['title'] = trim($tb['title']);
                $tbVar['url'] = trim($tb['url']);
                $tbVar['blogname'] = trim($tb['blog_name']);
                
                $timestamp = $blog->getCorrectTime($tb['ts']);
                $amOrPm = ( strftime('%H', $timestamp) < 13 ) ? 'AM' : 'PM'; 
                $tbVar['date'] = strftime('%m/%d/%Y %I:%M:%S '.$amOrPm, $timestamp);
                
                $tbVar['ip'] = '127.0.0.1';
                $tbVar['excerpt'] = preg_replace_callback($delim_expr, array(&$this, '_convertDelim'), $tb['excerpt']);
                
                $itemVar['tb'] .=  $tpl->fill($tbTpl, $tbVar, null);
            }
            
            //entry
            if( $memberid = $item->author ){
                if( ! $members[$memberid] ){
                    $members[$memberid] = MEMBER::createFromID($memberid);
                }
                $itemVar['author'] = $members[$memberid]->getRealName();
            }
            
            $catid = $item->catid;
            if( !$categories[$catid] ){
                $categories[$catid] = $blog->getCategoryName($catid);
            }
            $itemVar['categoryname'] = $categories[$catid];
            
            $itemVar['title'] = $item->title;
            
            $timestamp = $blog->getCorrectTime($item->ts);
            $amOrPm = ( strftime('%H', $timestamp) < 13 ) ? 'AM' : 'PM'; 
            $itemVar['date'] = strftime('%m/%d/%Y %I:%M:%S ' . $amOrPm, $timestamp);
            
            $itemVar['status'] = $item->draft ? 'Draft' : 'Publish';
            $itemVar['allow_comments'] = $item->closed ? 0 : 1;
            $itemVar['convert_breaks'] = $stripbr ? 1 : 0;
            
            $itemVar['body'] = $item->body;
            $itemVar['more'] = $item->more;
            $preg_expr = "#<\%(image|popup|media)\((.*?)\)%\>#i";
            $this->currentItem = $item;
            $itemVar['body'] = preg_replace_callback($preg_expr, array(&$this, '_convertMedia'), $itemVar['body']);
            $itemVar['more'] = preg_replace_callback($preg_expr, array(&$this, '_convertMedia'), $itemVar['more']);
            
            $itemVar['body'] = preg_replace_callback($delim_expr, array(&$this, '_convertDelim'), $itemVar['body']);
            $itemVar['more'] = preg_replace_callback($delim_expr, array(&$this, '_convertDelim'), $itemVar['more']);
            
            if( $stripbr ){
                $itemVar['body'] = preg_replace("/<br ?\/?>([\r\n])/","$1",$itemVar['body']);
                $itemVar['more'] = preg_replace("/<br ?\/?>([\r\n])/","$1",$itemVar['more']);
            }

            echo preg_replace("/(\r\n|\n|\r)/m", "\n", mb_convert_encoding( $tpl->fill($entryTpl, $itemVar, null), 'UTF-8', _CHARSET));
        }
        
        global $member;
        $member = $member_bk;
    }
    
    function _convertMedia($matches) {
        global $CONF;
        $type = $matches[1];
        list($url, $w, $h, $alt) = explode('|', $matches[2]);
        
        $vars = array (
            'w' => $w,
            'h' => $h,
            'url' => $url,
            'alt' => $alt,
        );
        
        if(strstr($url,'/'))
            $collection = dirname($url);
        if( !$collection || $collection == '.' || $collection == '/' )
            $collection = $this->currentItem->author;
        $filename = basename($url);
        
        switch($type){
            case 'image':
            case 'popup':
                $attr = '';
                $attr .= $w ? " width=\"$w\"": '' ;
                $attr .= $h ? " hight=\"$h\"": '' ;
                $attr .= $alt ? sprintf(' alt="%s" title="%s"', $alt,$alt): sprintf(' alt="%s" title="%s"', $filename,$filename);

                $title = ( $alt ) ? $alt : $filename;
                $mediatag = sprintf('<img src="%s%s/%s" %s />', $CONF['MediaURL'],$collection,$filename,$attr);
                break;
                
            case 'media':
                $title = ( $alt ) ? $alt : $filename;
                $mediatag = sprintf('<a href="%s%s/%s">%s</a>', $CONF['MediaURL'],$collection,$filename,$title);
                break;
        }
        
        return $mediatag;
    }
    
    function _convertDelim($matches){
        return $matches[0] . ' ';
    }

    function getBloglist(){
        $query = 'SELECT bnumber, bname FROM ' . sql_table('blog');
        $res = sql_query($query);
        $list = array();
        while( $row = sql_fetch_array($res) ){
            list($blogid, $blogname) = $row;
            $list[$blogid] = $blogname; 
        }
        return $list;
    }
    
}

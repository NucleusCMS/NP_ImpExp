<?php
// vim: tabstop=2:shiftwidth=2

/**
  * MTFileParser.php ($Revision: 1.3 $)
  * 
  * by hsur ( http://blog.cles.jp/np_cles )
  * $Id: MTFileParser.php,v 1.3 2006/07/02 01:15:37 hsur Exp $
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

class cles_MTFileParser {
	
	function cles_MTFileParser($filename){
		$this->filename = $filename;
		$this->fromEncoding = 'utf-8';
		$this->toEncoding = 'utf-8';
	}

	function parse(&$callback){
		$handle = fopen($this->filename, "rb");
		$buffer = "";
		if(!$handle)
			return;
		while (!feof($handle)) {
			while (("--------" != trim($buffer)) && !feof($handle)) {
				$entry .= $buffer;
				$buffer = fgets($handle);
			}
			if ($entry){
				if( $this->fromEncode != $this->toEncode ){
					$entry = mb_convert_encoding($entry, $this->toEncoding, $this->fromEncoding);	
				}
				$entry = $this->parseEntry($entry);
				call_user_func_array($callback, array($entry));
			}
			$entry = "";
			$buffer = "";
		}
		fclose($handle);
	}

	function parseEntry($entry) {
		$sections = preg_split("/(\r?\n)+-----(\r?\n)+/", $entry);
		$sections = array_filter($sections);
		$header = array_shift($sections);
		//var_dump($sections);
		$entry = $this->parseHeader($header);
		//var_dump($hdr);
		$entry['COMMENT'] = array();
		$entry['PING'] = array();
		foreach ($sections as $section) {
			if ($section)
				$this->parseSection($section, $entry);
		}
		return $entry;
	}

	function parseHeader($head) {
		$ret = array ();
		$lines = preg_split("/(\r?\n)+/", $head);
		foreach ($lines as $line) {
			list ($key, $value) = explode(':', $line, 2);
			$ret[$key] = trim($value);
		}
		return $ret;
	}

	function parseSection($section, &$entry) {
		if (preg_match('/^BODY:/', $section)) {
			$entry['BODY'] = $this->parseTextSection($section);
		}
		elseif (preg_match('/^EXTENDED BODY:/', $section)) {
			$entry['EXTENDED BODY'] = $this->parseTextSection($section);
		}
		elseif (preg_match('/^EXCERPT:/', $section)) {
			$entry['EXCERPT'] = $this->parseTextSection($section);
		}
		elseif (preg_match('/^KEYWORDS:/', $section)) {
			$entry['KEYWORDS'] = $this->parseTextSection($section);
		}
		elseif (preg_match('/^COMMENT:/', $section)) {
			$entry['COMMENT'][] = $this->parseCommentSection($section);
		}
		elseif (preg_match('/^PING:/', $section)) {
			$entry['PING'][] = $this->parsePingSection($section);
		}
	}

	function parsePingSection($ping) {
		$pingData = array ();
		$lines = preg_split("/(\r?\n)+/", $ping);
		array_shift($lines);
		$idx = 0;
		while ($line = array_shift($lines)) {
			list ($key, $value) = explode(':', $line, 2);
			if (preg_match('/^(TITLE|URL|IP|DATE|BLOG NAME)$/', trim($key))) {
				$pingData[$key] = trim($value);
				$idx ++;
			} else {
				break;
			}
		}
		$pingData['EXCERPT'] = $line."\n".implode("\n", $lines);
		return $pingData;
	}

	function parseCommentSection($comment) {
		$commentData = array ();
		$lines = preg_split("/(\r?\n)+/", $comment);
		array_shift($lines);
		$idx = 0;
		while ($line = array_shift($lines)) {
			list ($key, $value) = explode(':', $line, 2);
			if (preg_match('/^(AUTHOR|EMAIL|URL|IP|DATE)$/', trim($key))) {
				$commentData[$key] = trim($value);
				$idx ++;
			} else {
				break;
			}
		}
		$commentData['COMMENT'] = $line."\n".implode("\n", $lines);
		return $commentData;
	}

	function parseTextSection($comment) {
		$lines = preg_split("/(\r?\n)+/", $comment);
		array_shift($lines);
		return implode("\n", $lines);
	}
}
?>

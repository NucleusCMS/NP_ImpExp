<?php
// vim: tabstop=2:shiftwidth=2

/**
  * Feedback.php ($Revision: 1.16 $)
  * 
  * by hsur ( http://blog.cles.jp/np_cles )
  * $Id: Feedback.php,v 1.16 2006/05/04 05:54:59 hsur Exp $
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

class cles_Feedback {
	var $oPluginAdmin;
	function CLES_Feedback(&$pluginAdmin){
		$this->oPluginAdmin = $pluginAdmin;
	}
	
	function getMenuStr(){
		return mb_convert_encoding('動作確認/不具合報告', _CHARSET, 'UTF-8');
	}
	
	function printForm($extra = '') {
		ob_start();
		
		global $nucleus, $CONF;
		
		echo "<h2>動作確認/不具合報告</h2>";
		echo '<p>下記より、作者への動作確認/不具合の報告を行うことができます。</p>';

		echo "<h3>収集する情報と公開について</h3>";
		echo '<p>デフォルトで必要最低限の環境情報（赤字のもの）を開発者のサーバへ送信します。<br />
							<span style="font-weight:bold; color:red">差し支えない範囲で環境情報の提供にご協力ください。</span></p>
							<p>※　収集した情報は統計処理、及びプラグインのBugFixのみに利用されます。また統計処理した結果については公表することがあります。</p>';

		echo "<h3>サイト固有コードについて</h3>";
		echo '<p>動作報告の重複を取り除くため、管理画面のURLのmd5を計算したものを送信しています。この情報から管理画面のURLを復元することはできないようになっています。<a href="http://computers.yahoo.co.jp/dict/security/hash/677.html" target="_blank">md5の解説についてはこちらをご覧ください。(Yahoo!コンピュータ用語辞典)</a></p>';

		// form 
		echo '<form method="post" action="http://blog.cles.jp/support/report.php">' . "\n";

		// table
		echo "<table>\n";
		echo "<tr>\n";
		echo "<th>項目の説明</th>\n";
		echo "<th>送信される値</th>\n";
		echo "<th>送信する</th>\n";
		echo "</tr>\n";

		$res = sql_query("show variables like 'version'");
		$assoc = mysql_fetch_assoc($res);
		$mysqlVersion = $assoc['Value'];
		$gdinfo = gd_info();

		global $CONF;

		$this->_printtr('siteid', 'サイトの固有コード', md5(trim($CONF['AdminURL'])));
		$this->_printtr('plugin_name', 'プラグイン名', $this->oPluginAdmin->plugin->getName());
		$this->_printtr('plugin_version', 'プラグインのバージョン', $this->oPluginAdmin->plugin->getVersion());
		$this->_printtr('plugin_info', 'プラグインの情報', $extra, true);
		$this->_printtr('nucleus_version', 'Nucleusのバージョン', $nucleus['version'], true);
		$this->_printtr('nucleus_charset', 'Nucleusのキャラクタセット', _CHARSET);
		$this->_printtr('php_version', 'PHPのバージョン', PHP_VERSION, true);
		$this->_printtr('php_sapi', 'PHPの種類', php_sapi_name());
		$this->_printtr('php_os', 'OSの種類', PHP_OS, true);
		$this->_printtr('php_safemode', 'セーフモードの有無', ini_get('safe_mode') ? 'on' : 'off');
		$this->_printtr('php_gd_version', 'GDのバージョン', $gdinfo['GD Version'], true);
		$this->_printtr('php_gd_support', 'サポートしているイメージタイプ', implode(',', $this->_supportedImageTypes()) );
		$this->_printtr('mysql_version', 'MySQLのバージョン', $mysqlVersion, true);

		echo "<tr>\n";
		echo "<td>このプラグインは機能しましたか？</td>\n";
		echo '<td colspan="2"><input type="radio" name="user_intention" value="ok" />はい <br/> <input type="radio" name="intention" value="ng" />いいえ'."</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td>不具合の内容をお寄せください<br /><em>必ず回答が必要な質問については、<a href=\"http://japan.nucleuscms.org/bb/\">Nucleusサポートフォーラム</a>もしくは<a href=\"http://blog.cles.jp/np_cles/\">作者ページ</a>でご質問ください。</em></td>\n";
		echo '<td colspan="2"><textarea name="user_freetext" rows="10" cols="70"></textarea>'."</td>\n";
		echo "</tr>\n";

		echo "<tr>\n";
		echo "<td>よろしければサイトのURLを教えてください</td>\n";
		echo '<td colspan="2"><textarea name="user_url" rows="1" cols="70"></textarea>'."</td>\n";
		echo "</tr>\n";
		
		echo "<tr>\n";
		echo "<td>リンク集作成の際、リンクをはらせていただけますか？</td>\n";
		echo '<td colspan="2"><input type="radio" name="user_disclose" value="yes" />はい <br/> <input type="radio" name="intention" value="no" />いいえ'."</td>\n";
		echo "</tr>\n";

		echo '<tr><td colspan="3"><div align="right"><input type="submit" name="submit" value="動作確認を送信する" /></div></td></tr>';
		echo "</table>\n";
		echo "</form>\n";
		
		$contents = ob_get_contents();
		ob_end_clean();
		echo mb_convert_encoding($contents, _CHARSET, 'UTF-8');
	}
	
	function _printtr($name, $desc, $value, $canDisable = false) {
		echo "<tr>\n";
	
		if ($canDisable) {
			echo "<td>".$desc."</td>\n";
			echo "<td>".htmlspecialchars($value)."</td>\n";
			echo '<td><input type="checkbox" name="'.htmlspecialchars($name).'" value="'.htmlspecialchars($value).'" /></td>'."\n";
		} else {
			echo '<td><span style="font-weight:bold; color:red">'.$desc."</span></td>\n";
			echo '<td><span style="font-weight:bold; color:red">'.htmlspecialchars($value)."</span></td>\n";
			echo '<td><span style="font-weight:bold; color:red"><input type="checkbox" name="dummy" value="dummy" checked="checked" disabled="disabled" /><input type="hidden" name="'.htmlspecialchars($name).'" value="'.htmlspecialchars($value).'" /></span></td>'."\n";
		}
		echo "</tr>\n";
	}
	
	function _supportedImageTypes() {
		$aSupportedTypes = array ();
	
		$aPossibleImageTypeBits = array (IMG_GIF => 'GIF', IMG_JPG => 'JPG', IMG_PNG => 'PNG', IMG_WBMP => 'WBMP');
	
		foreach ($aPossibleImageTypeBits as $iImageTypeBits => $sImageTypeString) {
			if (imagetypes() & $iImageTypeBits) {
				$aSupportedTypes[] = $sImageTypeString;
			}
		}
	
		return $aSupportedTypes;
	}

}
?>

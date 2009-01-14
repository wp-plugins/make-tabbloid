<?php
/*
Plugin Name: Make Tabbloid
Plugin URI: http://www.rsc-ne-scotland.org.uk/mashe/make-tabbloid-plugin/
Description: A plugin which integrates the www.tabbloid.com service to create printer friendly 'tabloid' editions of your Wordpress blog. You can add a link to your &quot;Tabbloid&quot edition as a widget or by adding <code>&lt;?php makeTabbloid('linkName','fileName', showThumbnail); ?&gt; </code> in your template (linkName and fileName are strings and showThumbnail is a boolean).  
Author: Martin Hawksey
Author URI: http://www.rsc-ne-scotland.org.uk/mashe
Version: 0.9.2
*/


/*  Copyright 2009  Martin Hawksey  (email : martin.hawksey@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define(MAKE_TABBLOID_WIDGET_ID, "widget_make_tabbloid");



if (!class_exists("MakeTabbloid")) {
class MakeTabbloid {
	var $adminOptionsName = "MakeTabbloidAdminOptionsName";
	function MakeTabbloid() {

	}
	function init() {
			$this->getAdminOptions();
	}
	function getAdminOptions() {
			$MakeTabbloidAdminOptions = array('mt_apikey' => '', 'mt_validkey' => 'false', 'mt_feeds' => array());
			$devOptions = get_option($this->adminOptionsName);
			if (!empty($devOptions)) {
				foreach ($devOptions as $key => $option)
					$MakeTabbloidAdminOptions[$key] = $option;
			}				
			update_option($this->adminOptionsName, $MakeTabbloidAdminOptions);
			return $MakeTabbloidAdminOptions;
	}
	function printAdminPage() {
			$devOptions = $this->getAdminOptions();
			if (isset($_POST['update_makeTabbloidPluginSeriesSettings'])) { 
				if (isset($_POST['makeTabbloidAPIKey'])) {
					$devOptions['mt_validkey'] = mt_checkAPI($_POST['makeTabbloidAPIKey']);
					if ($devOptions['mt_validkey'] =='true'){
					    update_option('tabbloid_api_key', $_POST['makeTabbloidAPIKey']);
					}
				}
						
			update_option($this->adminOptionsName, $devOptions);
			?>
<div class="updated"><p><strong><?php _e("Settings Updated.", "MakeTabbloid");?></strong></p></div>
					<?php
			}
			if (isset($_POST['update_makeTabbloidAddFeed'])) {
				if (isset($_POST['mt_addFeed'])) {
					$mt_feedResult = mt_addFeed(get_option('tabbloid_api_key'),$_POST['mt_addFeed']);						
				}
						?>
<div class="updated"><p><strong><?php _e("Feed Added - ".$mt_feedResult, "MakeTabbloid");?></strong></p></div>
					<?php
			}
			if (isset($_GET['removeID'])){
				$mt_feedResult = mt_removeFeed(get_option('tabbloid_api_key'),$_GET['removeID']);
					?>
<div class="updated"><p><strong><?php _e("Feed Removed - ".$mt_feedResult, "MakeTabbloid");?></strong></p></div>
					 <?php 
			}
			if 	($devOptions['mt_validkey']=='true'){
				$devOptions['mt_feeds'] = mt_getFeeds(get_option('tabbloid_api_key'));
			}
			$actionString = remove_querystring_var($_SERVER["REQUEST_URI"], "removeID");
					 ?>
<div class=wrap>
<h2>Make Tabbloid</h2>
<form method="post" action="<?php echo $actionString; ?>">
<h3>Tabbloid API Key</h3>
<p>To use Make Tabbloid you need to register with <a href="http://www.tabbloid.com/developer" target="_blank">http://www.tabbloid.com/developer</a> and enter the generated API key below: </p>
<input name="makeTabbloidAPIKey" type="text" value="<?php echo get_option('tabbloid_api_key')?>" size="70"> 
<span class="submit" style="border-top:none; ">
<input type="submit" name="update_makeTabbloidPluginSeriesSettings" value="<?php _e('Update Settings', 'MakeTabbloidPluginSeries') ?>" />
<?php if ($devOptions['mt_validkey']=='true'){ echo "<span style=\"padding: 0.5em; background-color: rgb(34, 221, 34); color: rgb(255, 255, 255); font-weight: bold;\">Valid Key</span>"; } else {echo "<span style=\"padding: 0.5em; background-color: rgb(221, 34, 34); color: rgb(255, 255, 255); font-weight: bold;\">Invalid API Key</span>"; } ?>
</span>
</form>
<?php if ($devOptions['mt_validkey']=='true'){ ?>
	<form method="post" action="<?php echo $actionString; ?>">
	<h3>Add Blog Feed</h3>
	<input name="mt_addFeed" type="text" size="70" value="<?php echo get_bloginfo_rss('rss2_url') ?>">
	<span class="submit" style="border-top:none;" >
	<input type="submit" name="update_makeTabbloidAddFeed" value="<?php _e('Add Feed', 'MakeTabbloidPluginSeries') ?>" />
	</span>
	<h3>Current Feeds</h3>
	<p>You can add a link to your &quot;Tabbloid&quot edition via the <a href="widgets.php">widgets</a> or by adding <code>&lt;?php makeTabbloid('linkName','fileName', showThumbnail); ?&gt; </code> in your template. </p>
	<?php
	if (count($devOptions['mt_feeds'])>1){
		echo "<ul>";
	  	for ($i=0; $i<count($devOptions['mt_feeds']); $i++){
		 	echo "<li>".$devOptions['mt_feeds'][$i+1]." [<a href=\"".$actionString."&removeID=".$devOptions['mt_feeds'][$i]."\">Remove</a>]</li>\n";
	  		$i++;
	  	}
		echo "</ul>";
	} else {
		echo "<ul><li><em>None</em></li></ul>";
	}
 ?>
 </form>
 </div>
 <?php } ?>
					<?php
	}//End function printAdminPage()
	
   }//End Class MakeTabbloid
}//End If !class_not_exist
function makeTabbloid($mt_linkname, $mt_filename, $mt_preview){
	$myFile = $mt_filename.".pdf";
	if (file_exists($myFile)){
		$lastBuild = filemtime($myFile) - strtotime(get_lastpostdate());
		$fileSize = filesize($myFile);
		if ($fileSize > 1024){
		 $fileBuilt = TRUE;
		}
	} else {
		$lastBuild = -1;
	} 
	if ($lastBuild < 0 || $fileSize < 1024){ // start check to see if posts have been made since last file was made
		$api_key = get_option('tabbloid_api_key');
		$pdfData = tabbliodHTTPPost($api_key, 'make_pdf','');
		$fh = fopen($myFile, 'w') or die("can't open file");
		fwrite($fh, $pdfData);
		fclose($fh);
		$fileSize = filesize($myFile);
		if ($fileSize > 1024){
		 $fileBuilt = TRUE;
		}
	}
	$pdfURL = str_replace(" ","%20",get_bloginfo('url')."/".$myFile);
	// Prepare POST request
	if ($mt_preview == "TRUE"){
		$build_array  = array(
						'url' => $pdfURL);
		$request_data = http_build_query($build_array);
		// Send the POST request (with cURL)
		$c = curl_init('http://view.samurajdata.se/ps.php');
		curl_setopt($c, CURLOPT_POST, true);
		curl_setopt($c, CURLOPT_POSTFIELDS, $request_data);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($c);
		$status = curl_getinfo($c, CURLINFO_HTTP_CODE);
		curl_close($c);
		if ($status == '200'){
			$strStart = stripos($result,"id=")+3;
			$strEnd = stripos($result,"&page");
			$previewID = substr($result,$strStart,$strEnd-$strStart);
		}
		if (strlen($previewID) < 64){
			$previewHTML = "<div style=\"font-size:80%; font-style:italic;\" align=\"center\"><a href=\"".$pdfURL."\" target=\"_blank\" ><img src=\"http://view.samurajdata.se/rsc/".$previewID."/tmp1.gif\" width=\"150\" height=\"194\"/></a><br/>Preview powered by:<br/><a href=\"http://view.samurajdata.se\" target=\"_blank\" \">http://view.samurajdata.se</a></div>";
		}
	}
	if ($fileBuilt){?>
		<a href="<?php echo $pdfURL;?>" target="_blank" ><?php echo $mt_linkname;?></a><?php echo $previewHTML; ?>
	<?php
	} else {
		echo "Waiting for www.tabbloid.com ...";
	}
}
function remove_querystring_var($url, $key) {
	$url = preg_replace('/(.*)(\?|&)' . $key . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&');
	$url = substr($url, 0, -1);
	return ($url);
}
function mt_checkAPI($api_key){
		$checkAPIRes = tabbliodHTTPPost($api_key, 'methods','');
		if ($checkAPIRes != "Invalid API key"){
			$checkAPIRes = "true";
		} else {
			$checkAPIRes = "false";
		}
		return $checkAPIRes;
	}
function mt_addFeed($api_key,$mt_feed){
 		$result = tabbliodHTTPPost($api_key, 'add_feed', $mt_feed);
		return $result;
}
function mt_getFeeds($api_key){
		$result = tabbliodHTTPPost($api_key, 'list_feeds','');
		$result = str_replace("[","",$result); 
		$result = str_replace("]","",$result);
		$result = str_replace("\"","",$result);
		$resultAR = explode(", ",$result);
		return $resultAR;
}
function mt_removeFeed($api_key,$mt_feed){
		$result = tabbliodHTTPPost($api_key, 'remove_feed', $mt_feed);
		return $result;
}
function tabbliodHTTPPost($api_key, $method, $feed_name){
			// Prepare POST request
			$build_array  = array(
					'api_key' => $api_key,
					'method'  => $method
				);
			if ($method == 'add_feed'){
				$build_array['feed_url'] = $feed_name;
			} elseif ($method == 'remove_feed'){
				$build_array['feed_id'] = $feed_name;
			}
			$request_data = http_build_query($build_array);
			// Send the POST request (with cURL)
			$c = curl_init('http://tabbloid.com/api');
			curl_setopt($c, CURLOPT_POST, true);
			curl_setopt($c, CURLOPT_POSTFIELDS, $request_data);
			curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
			$result = curl_exec($c);
			$status = curl_getinfo($c, CURLINFO_HTTP_CODE);
			curl_close($c);
			return $result;
	}

function widget_make_tabbloid($args) {
  extract($args, EXTR_SKIP);
  $options = get_option(MAKE_TABBLOID_WIDGET_ID);
  echo $before_widget; 
  echo $before_title . $options['mt_title'] . $after_title; 
  echo $options['mt_text'];
  makeTabbloid($options['mt_link_name'],$options['mt_file_name'],$options['mt_show_preview']);
  //makeTabbloid("TabbloidEdition2",TRUE);
  echo $after_widget;
}
function widget_make_tabbloid_init() {
  wp_register_sidebar_widget(MAKE_TABBLOID_WIDGET_ID, 
  	__('Make Tabbloid'), 'widget_make_tabbloid');
  wp_register_widget_control(MAKE_TABBLOID_WIDGET_ID,   
    __('Make Tabbloid'), 'widget_make_tabbloid_control'); 
}

function widget_make_tabbloid_control() {
  $options = get_option(MAKE_TABBLOID_WIDGET_ID);
  if (!is_array($options)) {
    $options = array();
  }

  $widget_data = $_POST[MAKE_TABBLOID_WIDGET_ID];
  if ($widget_data['submit']) {
    $options['mt_title'] = $widget_data['mt_title'];
    $options['mt_text'] = $widget_data['mt_text'];
    $options['mt_link_name'] = $widget_data['mt_link_name'];
	$options['mt_file_name'] = $widget_data['mt_file_name'];
	$options['mt_show_preview'] = $widget_data['mt_show_preview'];

    update_option(MAKE_TABBLOID_WIDGET_ID, $options);
  }

  // Render form
  $mt_title = $options['mt_title'];
  $mt_text = $options['mt_text'];
  $mt_link_name = $options['mt_link_name'];
  $mt_file_name = $options['mt_file_name'];
  $mt_show_preview = $options['mt_show_preview'];
  
  ?>
<p>
  <label for="<?php echo MAKE_TABBLOID_WIDGET_ID;?>-mt-title">
    Title:
  </label>
  <input class="widefat" 
    type="text"
    name="<?php echo MAKE_TABBLOID_WIDGET_ID; ?>[mt_title]" 
    id="<?php echo MAKE_TABBLOID_WIDGET_ID; ?>-mt-title" 
    value="<?php echo $mt_title; ?>"/>
</p>
<p>
  <label for="<?php echo MAKE_TABBLOID_WIDGET_ID;?>-mt-text">
    Preamble text (optional):
  </label>
  <textarea class="widefat" rows="6"
    name="<?php echo MAKE_TABBLOID_WIDGET_ID; ?>[mt_text]" 
    id="<?php echo MAKE_TABBLOID_WIDGET_ID; ?>-mt-text"><?php echo $mt_text; ?></textarea>
</p>
<p>
  <label for="<?php echo MAKE_TABBLOID_WIDGET_ID;?>-mt-link-name">
    Link text:
  </label>
  <input class="widefat" 
    type="text"
    name="<?php echo MAKE_TABBLOID_WIDGET_ID; ?>[mt_link_name]" 
    id="<?php echo MAKE_TABBLOID_WIDGET_ID; ?>-mt-link-name" 
    value="<?php echo $mt_link_name; ?>"/>
</p>
<p>
  <label for="<?php echo MAKE_TABBLOID_WIDGET_ID;?>-mt-file-name">
    File name (this is also used as the filename for your local copy of your 'tabbloid' pdf):
  </label>
  <input class="widefat" 
    type="text"
    name="<?php echo MAKE_TABBLOID_WIDGET_ID; ?>[mt_file_name]" 
    id="<?php echo MAKE_TABBLOID_WIDGET_ID; ?>-mt-file-name" 
    value="<?php echo $mt_file_name; ?>"/>
</p>
<p>
  <label for="<?php echo MAKE_TABBLOID_WIDGET_ID;?>-mt-show-preview">
    Show thumbnail of PDF:
  </label>
  <select class="widefat"
    name="<?php echo MAKE_TABBLOID_WIDGET_ID; ?>[mt_show_preview]"
    id="<?php echo MAKE_TABBLOID_WIDGET_ID;?>-mt-show-preview">
    <option value="TRUE" <?php echo ($my_show_preview == "TRUE") ? "selected" : ""; ?>>
      Yes
    </option>
    <option value="FALSE" <?php echo ($mt_show_preview == "TRUE") ? "" : "selected"; ?>>
      No
    </option>
  </select>
</p>
<input type="hidden" 
  name="<?php echo MAKE_TABBLOID_WIDGET_ID; ?>[submit]" 
  value="1"/>
<?php
}


if (class_exists("MakeTabbloid")) {
	$mt_pluginSeries = new MakeTabbloid();
}

if (!function_exists("MakeTabbloidPluginSeries_ap")) {
	function MakeTabbloidPluginSeries_ap() {
		global $mt_pluginSeries;
		if (!isset($mt_pluginSeries)) {
			return;
		}
		if (function_exists('add_options_page')) {
	add_options_page('Make Tabbloid', 'Make Tabbloid', 9, basename(__FILE__), array(&$mt_pluginSeries, 'printAdminPage'));
		}
	}	
}

	//Actions and Filters	
if (isset($mt_pluginSeries)) {
	//Actions
	add_action('admin_menu', 'MakeTabbloidPluginSeries_ap');
	add_action("plugins_loaded", "widget_make_tabbloid_init");
	//add_action('wp_head', array(&$mt_pluginSeries, 'addHeaderCode'), 1);
	add_action('activate_make-tabbloid/make-tabbloid.php',  array(&$mt_pluginSeries, 'init'));
}
?>
=======
<?php
/*
Plugin Name: Make Tabbloid
Plugin URI: http://www.rsc-ne-scotland.org.uk/mashe/make-tabbloid-plugin/
Description: A plugin which integrates the www.tabbloid.com service to create printer friendly 'tabloid' editions of your Wordpress blog. You can add a link to your &quot;Tabbloid&quot edition as a widget or by adding <code>&lt;?php makeTabbloid('linkName','fileName', showThumbnail); ?&gt; </code> in your template (linkName and fileName are strings and showThumbnail is a boolean).  
Author: Martin Hawksey
Author URI: http://www.rsc-ne-scotland.org.uk/mashe
Version: 0.9.1
*/


/*  Copyright 2009  Martin Hawksey  (email : martin.hawksey@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define(MAKE_TABBLOID_WIDGET_ID, "widget_make_tabbloid");



if (!class_exists("MakeTabbloid")) {
class MakeTabbloid {
	var $adminOptionsName = "MakeTabbloidAdminOptionsName";
	function MakeTabbloid() {

	}
	function init() {
			$this->getAdminOptions();
	}
	function getAdminOptions() {
			$MakeTabbloidAdminOptions = array('mt_apikey' => '', 'mt_validkey' => 'false', 'mt_feeds' => array());
			$devOptions = get_option($this->adminOptionsName);
			if (!empty($devOptions)) {
				foreach ($devOptions as $key => $option)
					$MakeTabbloidAdminOptions[$key] = $option;
			}				
			update_option($this->adminOptionsName, $MakeTabbloidAdminOptions);
			return $MakeTabbloidAdminOptions;
	}
	function printAdminPage() {
			$devOptions = $this->getAdminOptions();
			
			if (isset($_POST['update_makeTabbloidPluginSeriesSettings'])) { 
				if (isset($_POST['makeTabbloidAPIKey'])) {
					$devOptions['mt_validkey'] = mt_checkAPI($_POST['makeTabbloidAPIKey']);
					if ($devOptions['mt_validkey'] =='true'){
					    update_option('tabbloid_api_key', $_POST['makeTabbloidAPIKey']);
					}
				}
						
			update_option($this->adminOptionsName, $devOptions);
			?>
<div class="updated"><p><strong><?php _e("Settings Updated.", "MakeTabbloid");?></strong></p></div>
					<?php
			}
			if (isset($_POST['update_makeTabbloidAddFeed'])) {
				if (isset($_POST['mt_addFeed'])) {
					$mt_feedResult = mt_addFeed(get_option('tabbloid_api_key'),$_POST['mt_addFeed']);						
				}
						?>
<div class="updated"><p><strong><?php _e("Feed Added - ".$mt_feedResult, "MakeTabbloid");?></strong></p></div>
					<?php
			}
			if (isset($_GET['removeID'])){
				$mt_feedResult = mt_removeFeed(get_option('tabbloid_api_key'),$_GET['removeID']);
					?>
<div class="updated"><p><strong><?php _e("Feed Removed - ".$mt_feedResult, "MakeTabbloid");?></strong></p></div>
					 <?php 
			}
			if 	($devOptions['mt_validkey']=='true'){
				$devOptions['mt_feeds'] = mt_getFeeds(get_option('tabbloid_api_key'));
			}
					 ?>
<div class=wrap>
<h2>Make Tabbloid</h2>
<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
<h3>Tabbloid API Key</h3>
<p>To use Make Tabbloid you need to register with <a href="http://www.tabbloid.com/developer" target="_blank">http://www.tabbloid.com/developer</a> and enter the generated API key below: </p>
<input name="makeTabbloidAPIKey" type="text" value="<?php echo get_option('tabbloid_api_key')?>" size="70"> 
<span class="submit" style="border-top:none; ">
<input type="submit" name="update_makeTabbloidPluginSeriesSettings" value="<?php _e('Update Settings', 'MakeTabbloidPluginSeries') ?>" />
<?php if ($devOptions['mt_validkey']=='true'){ echo "<span style=\"padding: 0.5em; background-color: rgb(34, 221, 34); color: rgb(255, 255, 255); font-weight: bold;\">Valid Key</span>"; } else {echo "<span style=\"padding: 0.5em; background-color: rgb(221, 34, 34); color: rgb(255, 255, 255); font-weight: bold;\">Invalid API Key</span>"; } ?>
</span>
</form>
<?php if ($devOptions['mt_validkey']=='true'){ ?>
	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
	<h3>Add Blog Feed</h3>
	<input name="mt_addFeed" type="text" size="70" value="<?php echo get_bloginfo_rss('rss2_url') ?>">
	<span class="submit" style="border-top:none;" >
	<input type="submit" name="update_makeTabbloidAddFeed" value="<?php _e('Add Feed', 'MakeTabbloidPluginSeries') ?>" />
	</span>
	<h3>Current Feeds</h3>
	<p>You can add a link to your &quot;Tabbloid&quot edition via the <a href="widgets.php">widgets</a> or by adding <code>&lt;?php makeTabbloid('linkName','fileName', showThumbnail); ?&gt; </code> in your template. </p>
	<?php
	if (count($devOptions['mt_feeds'])>1){
		echo "<ul>";
	  	for ($i=0; $i<count($devOptions['mt_feeds']); $i++){
		 	echo "<li>".$devOptions['mt_feeds'][$i+1]." [<a href=\"".$_SERVER["REQUEST_URI"]."&removeID=".$devOptions['mt_feeds'][$i]."\">Remove</a>]</li>\n";
	  		$i++;
	  	}
		echo "</ul>";
	} else {
		echo "<ul><li><em>None</em></li></ul>";
	}
 ?>
 </form>
 </div>
 <?php } ?>
					<?php
	}//End function printAdminPage()
	
   }//End Class MakeTabbloid
}//End If !class_not_exist
function makeTabbloid($mt_linkname, $mt_filename, $mt_preview){
	$myFile = $mt_filename.".pdf";
	if (file_exists($myFile)){
		$lastBuild = filemtime($myFile) - strtotime(get_lastpostdate());
	} else {
		$lastBuild = -1;
	} 
	if ($lastBuild < 0){ // start check to see if posts have been made since last file was made
		$api_key = get_option('tabbloid_api_key');
		$pdfData = tabbliodHTTPPost($api_key, 'make_pdf','');
		$fh = fopen($myFile, 'w') or die("can't open file");
		fwrite($fh, $pdfData);
		fclose($fh);
	}
	$pdfURL = str_replace(" ","%20",get_bloginfo('url')."/".$myFile);
	// Prepare POST request
	if ($mt_preview == "TRUE"){
		$build_array  = array(
						'url' => $pdfURL);
		$request_data = http_build_query($build_array);
		// Send the POST request (with cURL)
		$c = curl_init('http://view.samurajdata.se/ps.php');
		curl_setopt($c, CURLOPT_POST, true);
		curl_setopt($c, CURLOPT_POSTFIELDS, $request_data);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($c);
		$status = curl_getinfo($c, CURLINFO_HTTP_CODE);
		curl_close($c);
		if ($status == '200'){
			$strStart = stripos($result,"id=")+3;
			$strEnd = stripos($result,"&page");
			$previewID = substr($result,$strStart,$strEnd-$strStart);
		}
		if (strlen($previewID) < 64){
			$previewHTML = "<div style=\"font-size:80%; font-style:italic;\" align=\"center\"><a href=\"".$pdfURL."\" target=\"_blank\" ><img src=\"http://view.samurajdata.se/rsc/".$previewID."/tmp1.gif\" width=\"150\" height=\"194\"/></a><br/>Preview powered by:<br/><a href=\"http://view.samurajdata.se\" target=\"_blank\" \">http://view.samurajdata.se</a></div>";
		}
	}
	?>
<a href="<?php echo $pdfURL;?>" target="_blank" ><?php echo $mt_linkname;?></a><?php echo $previewHTML; ?>
	<?php
}
function mt_checkAPI($api_key){
		$checkAPIRes = tabbliodHTTPPost($api_key, 'methods','');
		if ($checkAPIRes != "Invalid API key"){
			$checkAPIRes = "true";
		} else {
			$checkAPIRes = "false";
		}
		return $checkAPIRes;
	}
function mt_addFeed($api_key,$mt_feed){
 		$result = tabbliodHTTPPost($api_key, 'add_feed', $mt_feed);
		return $result;
}
function mt_getFeeds($api_key){
		$result = tabbliodHTTPPost($api_key, 'list_feeds','');
		$result = str_replace("[","",$result); 
		$result = str_replace("]","",$result);
		$result = str_replace("\"","",$result);
		$resultAR = explode(", ",$result);
		return $resultAR;
}
function mt_removeFeed($api_key,$mt_feed){
		$result = tabbliodHTTPPost($api_key, 'remove_feed', $mt_feed);
		return $result;
}
function tabbliodHTTPPost($api_key, $method, $feed_name){
			// Prepare POST request
			$build_array  = array(
					'api_key' => $api_key,
					'method'  => $method
				);
			if ($method == 'add_feed'){
				$build_array['feed_url'] = $feed_name;
			} elseif ($method == 'remove_feed'){
				$build_array['feed_id'] = $feed_name;
			}
			$request_data = http_build_query($build_array);
			// Send the POST request (with cURL)
			$c = curl_init('http://tabbloid.com/api');
			curl_setopt($c, CURLOPT_POST, true);
			curl_setopt($c, CURLOPT_POSTFIELDS, $request_data);
			curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
			$result = curl_exec($c);
			$status = curl_getinfo($c, CURLINFO_HTTP_CODE);
			curl_close($c);
			return $result;
	}

function widget_make_tabbloid($args) {
  extract($args, EXTR_SKIP);
  $options = get_option(MAKE_TABBLOID_WIDGET_ID);
  echo $before_widget; 
  echo $before_title . $options['mt_title'] . $after_title; 
  echo $options['mt_text'];
  makeTabbloid($options['mt_link_name'],$options['mt_file_name'],$options['mt_show_preview']);
  //makeTabbloid("TabbloidEdition2",TRUE);
  echo $after_widget;
}
function widget_make_tabbloid_init() {
  wp_register_sidebar_widget(MAKE_TABBLOID_WIDGET_ID, 
  	__('Make Tabbloid'), 'widget_make_tabbloid');
  wp_register_widget_control(MAKE_TABBLOID_WIDGET_ID,   
    __('Make Tabbloid'), 'widget_make_tabbloid_control'); 
}

function widget_make_tabbloid_control() {
  $options = get_option(MAKE_TABBLOID_WIDGET_ID);
  if (!is_array($options)) {
    $options = array();
  }

  $widget_data = $_POST[MAKE_TABBLOID_WIDGET_ID];
  if ($widget_data['submit']) {
    $options['mt_title'] = $widget_data['mt_title'];
    $options['mt_text'] = $widget_data['mt_text'];
    $options['mt_link_name'] = $widget_data['mt_link_name'];
	$options['mt_file_name'] = $widget_data['mt_file_name'];
	$options['mt_show_preview'] = $widget_data['mt_show_preview'];

    update_option(MAKE_TABBLOID_WIDGET_ID, $options);
  }

  // Render form
  $mt_title = $options['mt_title'];
  $mt_text = $options['mt_text'];
  $mt_link_name = $options['mt_link_name'];
  $mt_file_name = $options['mt_file_name'];
  $mt_show_preview = $options['mt_show_preview'];
  
  ?>
<p>
  <label for="<?php echo MAKE_TABBLOID_WIDGET_ID;?>-mt-title">
    Title:
  </label>
  <input class="widefat" 
    type="text"
    name="<?php echo MAKE_TABBLOID_WIDGET_ID; ?>[mt_title]" 
    id="<?php echo MAKE_TABBLOID_WIDGET_ID; ?>-mt-title" 
    value="<?php echo $mt_title; ?>"/>
</p>
<p>
  <label for="<?php echo MAKE_TABBLOID_WIDGET_ID;?>-mt-text">
    Preamble text (optional):
  </label>
  <textarea class="widefat" rows="6"
    name="<?php echo MAKE_TABBLOID_WIDGET_ID; ?>[mt_text]" 
    id="<?php echo MAKE_TABBLOID_WIDGET_ID; ?>-mt-text"><?php echo $mt_text; ?></textarea>
</p>
<p>
  <label for="<?php echo MAKE_TABBLOID_WIDGET_ID;?>-mt-link-name">
    Link text:
  </label>
  <input class="widefat" 
    type="text"
    name="<?php echo MAKE_TABBLOID_WIDGET_ID; ?>[mt_link_name]" 
    id="<?php echo MAKE_TABBLOID_WIDGET_ID; ?>-mt-link-name" 
    value="<?php echo $mt_link_name; ?>"/>
</p>
<p>
  <label for="<?php echo MAKE_TABBLOID_WIDGET_ID;?>-mt-file-name">
    File name (this is also used as the filename for your local copy of your 'tabbloid' pdf):
  </label>
  <input class="widefat" 
    type="text"
    name="<?php echo MAKE_TABBLOID_WIDGET_ID; ?>[mt_file_name]" 
    id="<?php echo MAKE_TABBLOID_WIDGET_ID; ?>-mt-file-name" 
    value="<?php echo $mt_file_name; ?>"/>
</p>
<p>
  <label for="<?php echo MAKE_TABBLOID_WIDGET_ID;?>-mt-show-preview">
    Show thumbnail of PDF:
  </label>
  <select class="widefat"
    name="<?php echo MAKE_TABBLOID_WIDGET_ID; ?>[mt_show_preview]"
    id="<?php echo MAKE_TABBLOID_WIDGET_ID;?>-mt-show-preview">
    <option value="TRUE" <?php echo ($my_show_preview == "TRUE") ? "selected" : ""; ?>>
      Yes
    </option>
    <option value="FALSE" <?php echo ($mt_show_preview == "TRUE") ? "" : "selected"; ?>>
      No
    </option>
  </select>
</p>
<input type="hidden" 
  name="<?php echo MAKE_TABBLOID_WIDGET_ID; ?>[submit]" 
  value="1"/>
<?php
}


if (class_exists("MakeTabbloid")) {
	$mt_pluginSeries = new MakeTabbloid();
}

if (!function_exists("MakeTabbloidPluginSeries_ap")) {
	function MakeTabbloidPluginSeries_ap() {
		global $mt_pluginSeries;
		if (!isset($mt_pluginSeries)) {
			return;
		}
		if (function_exists('add_options_page')) {
	add_options_page('Make Tabbloid', 'Make Tabbloid', 9, basename(__FILE__), array(&$mt_pluginSeries, 'printAdminPage'));
		}
	}	
}

	//Actions and Filters	
if (isset($mt_pluginSeries)) {
	//Actions
	add_action('admin_menu', 'MakeTabbloidPluginSeries_ap');
	add_action("plugins_loaded", "widget_make_tabbloid_init");
	//add_action('wp_head', array(&$mt_pluginSeries, 'addHeaderCode'), 1);
	add_action('activate_make-tabbloid/make-tabbloid.php',  array(&$mt_pluginSeries, 'init'));
}
?>
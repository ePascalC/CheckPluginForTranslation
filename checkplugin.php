<?php
// HTML page start
?>
<html><header><title>Check Plugin wp-info.org</title>
<style>
p.ind { padding-left: 1.5em; text-indent:-1.5em;}
</style>
</header>
<body>

<?php
// Show version and point to information
echo '<b>Check Plugin v0.2.3</b> - More info and help appreciated on <a href="https://github.com/ePascalC/CheckPluginForTranslation">GitHub</a>,';
echo ' also check out <a href="http://wp-info.org/pa-qrg/">http://wp-info.org/pa-qrg/</a><br>';
echo '-----------------------------<br><br>';

// Plugin slug
if ( $_GET['slug'] ) {
	$plug_slug = strtolower($_GET['slug']);
	echo 'Plugin slug taken from url: ' . $plug_slug . '<br>';
} else {
	$plug_slug = 'bbp-toolkit';
	//$plug_slug = 'wpcasa-mail-alert';
	echo '<span style="color: orange;">' . 'No slug found in this url (add ?slug=myplugin). Using ' . $plug_slug . ' as example.</span>' . '<br>';
}	

// Check base dir	
$base_dir = 'https://plugins.svn.wordpress.org/' . $plug_slug;
$retcode = checkplug_get_retcode($base_dir . '/');
if ($retcode != 200) {
	echo '<span style="color: red;">' . 'Unable to find path ' . $base_dir . '/ (return code is ' . $retcode . ')</span>' . '<br>';
	die();
}
echo 'Plugin <b>slug</b>: ' . $plug_slug . '<br>';

// Get readme.txt correct upper/lower case spelling
$text = checkplug_get_file_contents($base_dir .  '/trunk/');
$lines = explode("\n", $text);
foreach ($lines as $line) {
	if (stripos($line, '<a href="readme.txt">') !== false) {
		$readme = checkplug_get_text_between('<li><a href="', '"', $line);
	}
}
if (!$readme) {
	echo '<span style="color: red;">' . 'Unable to find the readme file in folder ' . $trunk_readme . '</span>' . '<br>';
	die();
}

// Check if readme.txt is accessible in trunk
$trunk_readme = $base_dir . '/trunk/' . $readme;
$retcode = checkplug_get_retcode($trunk_readme);
if ($retcode != 200) {
	echo '<span style="color: red;">' . 'Unable to read file from ' . $trunk_readme . ' (return code is ' . $retcode . ')</span>' . '<br>';
	die();
}
echo '<b>Readme file</b> is available on ' . $trunk_readme . '<br>';

// Get the file
/*
WordPress.org’s Plugin Directory works based on the information found in the field Stable Tag in the readme. When WordPress.org parses the readme.txt, the very first thing it does is to look at the readme.txt in the /trunk directory, where it reads the “Stable Tag” line. If the Stable Tag is missing, or is set to “trunk”, then the version of the plugin in /trunk is considered to be the stable version. If the Stable Tag is set to anything else, then it will go and look in /tags/ for the referenced version. So a Stable Tag of “1.2.3” will make it look for /tags/1.2.3/.
*/
$text = checkplug_get_file_contents($trunk_readme);
$nbr_r = substr_count($text, "\r");
$nbr_n = substr_count($text, "\n");
if ( $nbr_r > $nbr_n ) {
	$lines = explode("\r", $text);
} else {
	$lines = explode("\n", $text);
}
$stable_tag = 'notfound';
$req_at_least = 'notfound';
foreach ($lines as $line) {
	if (stripos($line, 'Requires at least:') !== false) {
		echo ' - ' . $line . '<br>';
		$req_at_least = trim(substr($line, strlen('Requires at least:')));
	}

	if (stripos($line, 'Stable Tag:') !== false) {
		echo ' - ' . $line . '<br>';
		$stable_tag = strtolower(trim(substr($line, strlen('Stable Tag:'))));
	}
}

// Check the tags in the readme.txt trunk
if ( $stable_tag == 'trunk' ) {
	echo '<span style="color: orange;">' . 'Stable tag is set to trunk, is this what is expected?' . '</span>' . '<br>';
}
if ( $stable_tag == 'notfound' ) {
	echo '<span style="color: red;">' . 'Stable tag not found so defaulting to trunk, better define it!' . '</span>' . '<br>';
	$stable_tag = 'trunk';
}

// Get the tags if any
$tags_folder = $base_dir . '/tags';
$tags_html = checkplug_get_file_contents($tags_folder);
$lines = explode("\n", $tags_html);
$tags = array();
foreach ($lines as $line) {
	if (strpos($line, '<li>') !== false) {
		$tag = checkplug_get_text_between('<li><a href="', '/"', $line);
		if ($tag != '..') {
			$tags[] = $tag;
		}
	}
}
if ($tags) {
	// sort versions
	usort($tags, 'version_compare');
	
	echo '<p class="ind">';
	echo 'Tag folders found: ';
	$first = true;
	foreach ($tags as $tag) {
		if (!$first) {
			echo ', ';
		} else {
			$first = false;
		}
		echo $tag;
	}
	if ($stable_tag == 'trunk') {
		echo ' (but none are used, only trunk)';
	}
	echo '</p>';
} else {
	if ($stable_tag == 'trunk') {
		echo 'No folders found under /tag, but trunk is used so that is fine.';
	} else {
		echo '<span style="color: red;">' . 'No folders found under /tag' . '</span>';
	}
	echo '<br>';
}


// Make sure the needed folder exists
if ($stable_tag == 'trunk') {
	$folder = $base_dir . '/trunk/';
} else {
	$folder = $base_dir . '/tags/' . $stable_tag . '/';
}
if (checkplug_get_retcode($folder) != 200) {
	echo '<span style="color: red;">' . 'Unable to find or access ' . $folder . ' (return code is ' . $retcode . ')</span>';
	die();
}

// Get the files in that folder
$files = checkplug_get_file_contents($folder);

// Get all php files
$lines = explode("\n", $files);

$php_files = array();
foreach ($lines as $line) {
	$line = trim($line);
	if (strpos($line, '<li>') !== false) {
		$f = checkplug_get_text_between('<li><a href="', '/"', $line);
		if (!$f) {
			$php_file = checkplug_get_text_between('<li><a href="', '.php"', $line);
			if ($php_file) {
				$php_files[] = $php_file . '.php';
			}
		}
	}
}
if (!$php_files) {
	echo '<span style="color: red;">' . 'Unable to find or access php files in ' . $folder . '</span>';
	die();
}

// Loop all php files found
foreach ($php_files as $php_file) {

	// Read the (hopefully main) php file
	$main_php = checkplug_get_file_contents($folder . $php_file);

	// Get the comment part, there might be more then 1 /* */
	$occurrence = 1;
	$main_php_comment = checkplug_get_text_between('/*', '*/', $main_php, $occurrence);
	while ($main_php_comment) {
		$lines = explode("\n", $main_php_comment);
		$plug_info = array();
		foreach ($lines as $line) {
			$line = trim($line);
			$t = 'Plugin Name:';
			$i = stripos($line, $t);
			if ($i !== false) {
				$plug_info['name'] = trim(substr($line, strlen($t) + $i));
			}
			$t = 'Version:';
			$i = stripos($line, $t);
			if ($i !== false) {
				$plug_info['version'] = trim(substr($line, strlen($t) + $i));
			}
			$t = 'Text Domain:';
			$i = stripos($line, $t);
			if ($i !== false) {
				$plug_info['text_domain'] = trim(substr($line, strlen($t) + $i));
			}
		}
		
		// If we get the version, we have the correct comment block
		if ( $plug_info['version'] ) break;
		
		$occurrence = $occurrence + 1;
		$main_php_comment = checkplug_get_text_between('/*', '*/', $main_php, $occurrence);
	}
	if ( ( isset($plug_info['version']) ) && ( isset($plug_info['name']) ) )  {
		echo 'File <b>' . $php_file . '</b> in ' . $folder . ' had the info needed:<br>';
		echo ' - Plugin Name: ' . $plug_info['name'] . '<br>';
		echo ' - Version: ' . $plug_info['version'] . '<br>';
		echo ' - Text Domain: ' . $plug_info['text_domain'] . '<br>';
		echo '<br>';
		break;
	}
}

// Check Text domain
if ( !isset( $plug_info['text_domain'] ) ) {
	echo '<span style="color: red;">' . 'Your plugin slug is ' . $plug_slug . ', but there seems no Text Domain: defined in ' . $main_php . '!' . '</span>';
	die();
}	
if ($plug_info['text_domain'] != $plug_slug) {
	echo '<span style="color: red;">' . 'Your plugin slug is ' . $plug_slug . ', but your Text Domain is ' . $plug_info['text_domain'] .'. Change your Text Domain so it is equal to your slug!' . '</span>';
	die();
}

// If trunk, check if version has existing tag
if ( ( $stable_tag == 'trunk' ) && ( in_array($plug_info['version'], $tags) ) ) {
	echo '<span style="color: orange;">' . 'Trunk is being used, but there is also a folder under tags for version ' . $plug_info['version'] . '</span>';
}	

// load_plugin_textdomain checks
if ( version_compare($req_at_least, '4.6', '>=') ) {
	echo 'Required version (' . $req_at_least . ') is at least 4.6 so no <b>load_plugin_textdomain</b> is needed.<br>';
	echo '<span style="color: green;">(more code needed here to perform this check)</span><br>';
} else {
	echo 'Required version (' . $req_at_least . ') is below 4.6 so a <b>load_plugin_textdomain</b> is needed.<br>';
	echo '<span style="color: green;">(more code needed here to perform this check)</span><br>';
}
  // MORE CODE NEEDED HERE TO CHECK

// Language packs
echo '<h3>Language packs created:</h3>';
$l_arr = array();
$v_arr = array();
$f = checkplug_get_file_contents('https://api.wordpress.org/translations/plugins/1.0/?slug=' . $plug_slug);
if ($f) {
	$a = json_decode($f);
	foreach ($a->translations as $item) {
		$d = new DateTime($item->updated);
		$l_arr[$item->language]['updated'] = $d->format('Y-m-d');
		$l_arr[$item->language]['english_name'] = $item->english_name;
		$l_arr[$item->language]['language'] = $item->language;
		$l_arr[$item->language]['version'] = $item->version;
		if( !in_array($item->version, $v_arr ) ) {
			$v_arr[] = $item->version;
        }
	}
}
usort($v_arr, 'version_compare'); //sort versions
$v_arr = array_reverse($v_arr); // in reverse order

foreach ( $v_arr as $ver ) {
	echo '<p class="ind">';
	echo 'v<b>' . $ver . '</b>: ';
	foreach ( $l_arr as $l ) {
		if ( $l['version'] == $ver ) {
			echo $l['english_name'] . ' (<b>' . $l['language'] . '</b> - ' . $l['updated'] . '), ';
		}
	}
	echo '</p>';
}
	
// Translation status per locale
echo '<h3>Translation status (% per locale)</h3>';
echo '<span style="color: green;">(more code needed here to perform this check)</span><br>';

// Translation editors from https://translate.wordpress.org/projects/wp-plugins/$plug_slug/contributors
echo '<h3>Translation editors per locale</h3>';
$url = 'https://translate.wordpress.org/projects/wp-plugins/' . $plug_slug . '/contributors';
$f = checkplug_get_file_contents($url);
if ($f) {
	$doc = new DOMDocument;
	@$doc->loadHTML($f);
	$doc->preserveWhiteSpace = false;
	$classname = 'has-editors';
	$xpath = new DOMXPath($doc);
	$rows = $xpath->query("//*[contains(@class, '" . $classname . "')]");
	$nbr_rows = $rows->length;
	if ($nbr_rows) {
		echo '<table border="1">';
		foreach ($rows as $row) {
			//Get locale name and code
			$classname = "locale-name";
			$loc_name = trim($xpath->query(".//*[contains(@class, '" . $classname . "')]", $row)->item(0)->childNodes[0]->nodeValue);
			$classname = "locale-code";
			$loc_code = $xpath->query(".//*[contains(@class, '" . $classname . "')]", $row)->item(0)->nodeValue;
			$loc_code = str_replace('#', '', $loc_code);
			//Get usernames
			$hrefs = $xpath->query('.//p[1]/a/@href', $row);
			$ptes = '';
			foreach ($hrefs as $href) {
				$uname = checkplug_get_text_between('https://profiles.wordpress.org/', '/', $href->nodeValue);
				if ($ptes) $ptes = $ptes . '; ';
				$ptes = $ptes . '<a href="' . $href->nodeValue . '">' . $uname . '</a>';
			}
			echo '<tr><td>' . $loc_code . '</td><td>' . $ptes . '</td></tr>';
			// add to central array
			if ( !isset($l_arr[$loc_code]) ) {
				$l_arr[$loc_code]['language'] = $loc_code;
				$l_arr[$loc_code]['english_name'] = $loc_name;
			}
			$l_arr[$loc_code]['pte'] = $ptes;
		}
		echo '</table>';
	}
}

// Latest Revision log
echo '<h3>Latest Revision log entries</h3>';
$url = 'https://plugins.trac.wordpress.org/log/' . $plug_slug . '/?limit=10&mode=stop_on_copy&format=rss';
$rss_items = checkplug_fetch_feed( $url );
if (!$rss_items) {
	echo '<span style="color: orange;">' . 'Unable to get <a href="' . $url . '">latest revision log</a>, might be just a temporary issue.</span>';
} else {	
	echo '<table border="1">';
	foreach ( $rss_items as $item ) {
		$revlog_url = $item['link'];
		$revlog_desc = $item['description'];
		$revlog_date = date('Y-m-d H:i', $item['date']);
		echo '<tr><td>' . $revlog_date . ' GMT</td><td>' . $revlog_desc . '</td></tr>';
	}
	echo '</table>';
}

// Summary table
echo '<h3>Summary table</h3>';
echo '<table border="1">';
echo '<tr><th>Locale</th><th>Name</th><th colspan="2">Language pack</th><th>Editor (PTE)</th></tr>';
ksort($l_arr);
foreach ( $l_arr as $loc ) {
	echo '<tr><td>' . $loc['language'] . '</td><td>' . $loc['english_name'] . '</td><td>' . $loc['version'] . '</td><td>' . $loc['updated'] . '</td><td>' . $loc['pte'] . '</td></tr>';
}
echo '</table>';




/*
 * FUNCTIONS
 */

function checkplug_get_file_contents($url) {
	$ch = curl_init();
	curl_setopt_array(
		$ch, array( 
		CURLOPT_URL => $url,
		CURLOPT_HEADER => false,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_AUTOREFERER => true,
		CURLOPT_USERAGENT => 'Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.43 Safari/537.31',
		CURLOPT_FOLLOWLOCATION => true
	));
	$output = curl_exec($ch);
	curl_close($ch);
	return $output;
}

function checkplug_get_retcode($url) {
	$ch = curl_init();
	curl_setopt_array(
		$ch, array( 
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_NOBODY => false,
		CURLOPT_USERAGENT => 'Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.43 Safari/537.31',
	));
	$output = curl_exec($ch);
	$retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	// $retcode >= 400 -> not found, $retcode = 200, found.
	return $retcode;
}

function checkplug_get_text_between($sstr, $estr, $haystack, $occurrence = 1) {
	// subtract 1 from occurrence to fit with array numbering
	$occurrence = $occurrence - 1;
	
	// Get all occurrences of sstr
	$all_start_pos = array();
	$s = strpos($haystack, $sstr);
	if ($s !== false) {
		$all_start_pos[] = $s;
		while ($s !== false) {
			$s = strpos($haystack, $sstr, $s + 1);
			if ($s) $all_start_pos[] = $s;
		}
	} else {
		$return = null;
	}
	
	if ( isset( $all_start_pos[$occurrence] ) ) {
		$s = $all_start_pos[$occurrence];
		$e = strpos($haystack, $estr, $s + strlen($sstr));
		if ($e !== false) {
			$return = trim(substr($haystack, $s + strlen($sstr) , $e - $s - strlen($sstr)));
		} else {
			$return = null;
		}
	} else {
		$return = null;
	}
	return $return;
}

function checkplug_fetch_feed( $url ) {
	$retcode = checkplug_get_retcode($url);
	if ($retcode !== 200) return false;
	
	$content = checkplug_get_file_contents($url);
	
	$return = array();
    $x = new SimpleXmlElement($content);
    foreach($x->channel->item as $entry) {
 		$return[] = array( 'link' => (string) $entry->link, 'description' => (string) $entry->description, 'date' => strtotime($entry->pubDate) );
    }
	return $return;
}

// HTML page end
echo '</body>';
echo '</html>';

?>

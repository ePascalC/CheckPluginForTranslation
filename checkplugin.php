<?php

/**
ABUSE CHECK
Throttle client requests to avoid DoS attack
Found on https://gist.github.com/luckyshot/6077693
*/
session_start();
$usage = array(5,5,5,10,20,30); // seconds to wait after each request
if (isset($_SESSION['use_last'])) {
  $nextin = $_SESSION['use_last']+$usage[$_SESSION['use_count']];
	if (time() < $nextin) {
		header('HTTP/1.0 503 Service Temporarily Unavailable');
		header('Status: 503 Service Temporarily Unavailable');
		header('Retry-After: ' . $nextin-time() );
		die('Server is bit busy right now!<br>Please wait '.($nextin-time()).' seconds&hellip;' );
	}else{
		$_SESSION['use_count']++;
		if ($_SESSION['use_count'] > sizeof($usage)-1) {$_SESSION['use_count']=sizeof($usage)-1;}
	}
}else{
	$_SESSION['use_count'] = 0;
}
$_SESSION['use_last'] = time();

// HTML page start
?>
<html><head><title>Plugin i18n Readiness wp-info.org</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
	p.ind { padding-left: 1.5em; text-indent:-1.5em;}
</style>
</head>
<body>

<?php
// Show version and point to information
echo '<b>Plugin i18n Readiness v0.3.3</b> - More info and help appreciated on <a href="https://github.com/ePascalC/CheckPluginForTranslation">GitHub</a>,';
echo ' also check out <a href="http://wp-info.org/pa-qrg/">http://wp-info.org/pa-qrg/</a><br>';
echo '---------------------------------------<br><br>';

// Prepare
$p = array(); // will hold all the plugin info

// Plugin slug
if ( $_GET['slug'] ) {
	// Check the allowed characters for a plugin slug and use that!
	$p['slug'] = strtolower( strip_tags( htmlspecialchars( $_GET['slug'] ) ) );
	echo 'Plugin slug taken from url: ' . $p['slug'] . '<br>';
} else {
	echo '<form method="get">Plugin Slug: <input type="text" name="slug"><input type="submit"></form>';
	$p['slug'] = 'bbpress';
	checkplug_show_warning( 'Please enter a plugin slug and hit "Submit". Using ' . $p['slug'] . ' as example.<br>' );
	echo file_get_contents('./checkplugini18n_cache.html');
	die();
}	

// Check base dir	
$p['svn_base_dir'] = 'https://plugins.svn.wordpress.org/' . $p['slug'];
$retcode = checkplug_get_retcode($p['svn_base_dir'] . '/');
if ($retcode != 200) {
	checkplug_show_error( 'Unable to find path ' . $p['svn_base_dir'] . '/ (return code is ' . $retcode . ')' );
}
echo 'Plugin <b>slug</b>: ' . $p['slug'] . '<br>';

// Get readme.txt correct upper/lower case spelling
$text = checkplug_get_file_contents($p['svn_base_dir'] .  '/trunk/');
$lines = explode("\n", $text);
foreach ($lines as $line) {
	if (stripos($line, '<a href="readme.txt">') !== false) {
		$p['fn_trunk_readme'] = checkplug_get_text_between('<li><a href="', '"', $line);
		$p['readme_ext_txt'] = 'y';
	}
	if (stripos($line, '<a href="readme.md">') !== false) {
		$p['readme_ext_md'] = 'y';
	}

}
if (!$p['fn_trunk_readme']) {
	checkplug_show_error( 'Unable to find the readme file in folder ' . $p['svn_base_dir'] . '/trunk/' );
}

// Check if readme.txt is accessible in trunk
$p['fp_trunk_readme'] = $p['svn_base_dir'] . '/trunk/' . $p['fn_trunk_readme'];
$retcode = checkplug_get_retcode($p['fp_trunk_readme']);
if ($retcode != 200) {
	checkplug_show_error( 'Unable to read file ' . $p['fp_trunk_readme'] . ' (return code is ' . $retcode . ')' );
}

// If both readme.txt and readme.md exist, the txt wins
if ( $p['readme_ext_txt'] && $p['readme_ext_md'] ) {
    checkplug_show_warning( 'You have both a readme.txt and readme.md . Only the <b>.txt</b> is taken into account.' );
}


echo '<b>Readme file</b> is available on ' . $p['fp_trunk_readme'] . '<br>';

// Get the file
/*
WordPress.org’s Plugin Directory works based on the information found in the field Stable Tag in the readme.
When WordPress.org parses the readme.txt, the very first thing it does is to look at the readme.txt in the /trunk directory,
where it reads the “Stable Tag” line. If the Stable Tag is missing, or is set to “trunk”,
then the version of the plugin in /trunk is considered to be the stable version.
If the Stable Tag is set to anything else, then it will go and look in /tags/ for the referenced version.
So a Stable Tag of “1.2.3” will make it look for /tags/1.2.3/.

If tag not found, it will default to trunk.
*/
$text = checkplug_get_file_contents($p['fp_trunk_readme']);
$nbr_r = substr_count($text, "\r");
$nbr_n = substr_count($text, "\n");
if ( $nbr_r > $nbr_n ) {
	$lines = explode("\r", $text);
} else {
	$lines = explode("\n", $text);
}
$p['stable_tag'] = 'notfound';
$p['req_at_least'] = 'notfound';
$p['tested_up_to'] = 'notfound';
foreach ($lines as $line) {
	if (stripos($line, 'Stable Tag:') !== false) {
		echo ' - ' . $line . '<br>';
		$p['stable_tag'] = strtolower(trim(substr($line, strlen('Stable Tag:'))));
	}
	if (stripos($line, 'Requires at least:') !== false) {
		// Saving for later. If Stable Tag is NOT trunk, then the value will have to be taken from the readme in the tags folder
		$p['req_at_least'] = trim(substr($line, strlen('Requires at least:')));
	}
	if (stripos($line, 'Tested up to:') !== false) {
		// Saving for later. If Stable Tag is NOT trunk, then the value will have to be taken from the readme in the tags folder
		$p['tested_up_to'] = strtolower(trim(substr($line, strlen('Tested up to:'))));
	}
}

// Check the tags in the readme.txt trunk
if ( $p['stable_tag'] == 'trunk' ) {
	checkplug_show_warning( 'Stable tag is set to trunk, is this what is expected? <form style="display: inline;" action="https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/#how-the-readme-is-parsed"><input type="submit" value="More info" /></form>' );
}
if ( $p['stable_tag'] == 'notfound' ) {
	checkplug_show_warning( 'Stable tag not found in readme file so defaulting to trunk, better define it! <form style="display: inline;" action="https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/#how-the-readme-is-parsed"><input type="submit" value="More info" /></form>' );
	$p['stable_tag'] = 'trunk';
}

// Get the tags if any
$tags_folder = $p['svn_base_dir'] . '/tags';
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
		// Highlight if tag is the stable tag
		if ( $tag == $p['stable_tag'] ) {
			echo '<b>' . $tag . '</b>';
		} else {
			echo $tag;
		}
	}
	if ($p['stable_tag'] == 'trunk') {
		echo ' (but none are used, only trunk)';
	}
	echo '</p>';
} else {
	if ($p['stable_tag'] == 'trunk') {
		echo 'No folders found under /tag, but trunk is used so that is fine.';
	} else {
		checkplug_show_warning( 'No folders found under /tags although your Stable Tag is set to ' . $p['stable_tag'] .
		    ' ! So create the <b>' . $p['stable_tag'] . '</b> folder under /tags OR change the Stable Tag to <b>trunk</b>. Defaulting now to trunk. ' . 
		    ' <form style="display: inline;" action="https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/#how-the-readme-is-parsed"><input type="submit" value="More info" /></form>' );
		$p['stable_tag'] = 'trunk';
	}
	echo '<br>';
}

// Make sure the needed folder exists
if ($p['stable_tag'] != 'trunk') {
	$folder = $p['svn_base_dir'] . '/tags/' . $p['stable_tag'] . '/';
	if (checkplug_get_retcode($folder) != 200) {
		checkplug_show_warning( 'Unable to find or access ' . $folder . ' (return code is ' . $retcode . '). Defaulting to trunk. <form style="display: inline;" action="https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/#how-the-readme-is-parsed"><input type="submit" value="More info" /></form>' );
		$p['stable_tag'] = 'trunk';
	}
} else {
    $folder = $p['svn_base_dir'] . '/trunk/';
}

// Get the files in that folder
$files = checkplug_get_file_contents($folder);

// Get all php files AND the readme file
$lines = explode("\n", $files);

$php_files = array();
foreach ($lines as $line) {
	$line = trim($line);
	if (stripos($line, '<a href="readme.txt">') !== false) {
		$p['fn_tags_readme'] = checkplug_get_text_between('<li><a href="', '"', $line);
	}
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
	checkplug_show_error( 'Unable to find or access php files in ' . $folder );
}

// Get info from the readme file in the tags folder if not trunk
/* If the Stable Tag is 1.2.3 and /tags/1.2.3/ exists, then nothing in trunk will be read any further for parsing by any part of the system. If you try to change the description of the plugin in /trunk/readme.txt, and Stable Tag isn’t trunk, then your changes won’t do anything on your plugin page. Everything comes from the readme.txt in the file being pointed to by the Stable Tag. */
if ( $p['stable_tag'] != 'trunk' ) {
	$p['fp_tags_readme'] = $folder . $p['fn_tags_readme'];
	echo 'Checking file <b>' . $p['fp_tags_readme'] . '</b>...<br>';
	$text = checkplug_get_file_contents($p['fp_tags_readme']);
	$nbr_r = substr_count($text, "\r");
	$nbr_n = substr_count($text, "\n");
	if ( $nbr_r > $nbr_n ) {
		$lines = explode("\r", $text);
	} else {
		$lines = explode("\n", $text);
	}
	$p['req_at_least'] = 'notfound';
	$p['tested_up_to'] = 'notfound';
	foreach ($lines as $line) {
		if (stripos($line, 'Requires at least:') !== false) {
			echo ' - ' . $line . '<br>';
			$p['req_at_least'] = trim(substr($line, strlen('Requires at least:')));
		}
		if (stripos($line, 'Tested up to:') !== false) {
			echo ' - ' . $line . '<br>';
			$p['tested_up_to'] = strtolower(trim(substr($line, strlen('Tested up to:'))));
		}
	}
}

// Check Req_at_least
if ( $p['req_at_least'] == 'notfound' ) {
	checkplug_show_warning( 'No <b>Requires at least:</b> is set in the readme file (' . $p['fn_tags_readme'] . '). Considering 1.0 for further testing.<br>' );
	$p['req_at_least'] = '1.0';
} else {
	// Compare Tested_up_to with Req_at_least
	if ( version_compare($p['req_at_least'], $p['tested_up_to'], '>') ) {
		checkplug_show_warning( 'Your <b>Requires at least:</b> is set to <b>' . $p['req_at_least'] . '</b>,' .
			' but the plugin seems only <b>Tested up to: ' . $p['tested_up_to'] . '</b><br>' );
	}
}	
echo '<br>';

// Loop all php files found
foreach ($php_files as $php_file) {

	// Read the (hopefully main) php file
	$p['fp_main_php'] = $folder . $php_file;
	$main_php_content = checkplug_get_file_contents($p['fp_main_php']);

	// Get the comment part, there might be more then 1 /* */
	$occurrence = 1;
	$main_php_comment = checkplug_get_text_between('/*', '*/', $main_php_content, $occurrence);
	while ($main_php_comment) {
		$lines = explode("\n", $main_php_comment);
		foreach ($lines as $line) {
			$line = trim($line);
			$t = 'Plugin Name:';
			$i = stripos($line, $t);
			if ($i !== false) {
				$p['name'] = trim(substr($line, strlen($t) + $i));
			}
			$t = 'Version:';
			$i = stripos($line, $t);
			if ($i !== false) {
				$p['version'] = trim(substr($line, strlen($t) + $i));
			}
			$t = 'Text Domain:';
			$i = stripos($line, $t);
			if ($i !== false) {
				$p['text_domain'] = trim(substr($line, strlen($t) + $i));
			}
		}
		
		// If we get the version, we have the correct comment block
		if ( $p['version'] ) break;
		
		$occurrence = $occurrence + 1;
		$main_php_comment = checkplug_get_text_between('/*', '*/', $main_php_content, $occurrence);
	}
	if ( ( isset($p['version']) ) && ( isset($p['name']) ) )  {
		echo 'File <b>' . $php_file . '</b> in ' . $folder . ' had the info needed:<br>';
		echo ' - Plugin Name: ' . $p['name'] . '<br>';
		echo ' - Version: ' . $p['version'] . '<br>';
		echo ' - Text Domain: ' . $p['text_domain'] . '<br>';
		echo '<br>';
		break;
	}
}

// Check Text domain
if ( !isset( $p['text_domain'] ) ) {
    if ( version_compare($p['req_at_least'], '4.6', '>=') ) {
        // No Text domain needs to be defined if WP is 4.6+
        $p['text_domain'] = $p['slug'];
    } else {
	    checkplug_show_error( 'Your plugin slug is <b>' . $p['slug'] . '</b>, but there seems no Text Domain: defined in <b>' . $php_file . '</b>! To correctly internationlize your plugin you will need it.' .
	    ' <form style="display: inline;" action="https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#text-domains"><input type="submit" value="More info" /></form>' );
    }
}	
if ($p['text_domain'] != $p['slug']) {
	checkplug_show_error( 'Your plugin slug is <b>' . $p['slug'] . '</b>, but your Text Domain is <b>' . $p['text_domain'] .'</b>. Change your Text Domain in <b>' . $php_file . '</b> so it is equal to your slug!' .
	    ' <form style="display: inline;" action="https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#text-domains"><input type="submit" value="More info" /></form>' );
}

// If trunk, check if version has existing tag
if ( ( $p['stable_tag'] == 'trunk' ) && ( in_array($p['version'], $tags) ) ) {
	checkplug_show_warning( 'Trunk is being used, but there is also a folder under tags for version ' . $p['version'] . '<br>');
}	

// load_plugin_textdomain checks
if ( version_compare($p['req_at_least'], '4.6', '>=') ) {
	echo 'Required version (' . $p['req_at_least'] . ') is at least 4.6 so no <b>load_plugin_textdomain</b> is needed.<br>If you have the function somewhere, you can remove it.<br>';
	echo '<span style="color: green;">(more code needed here to perform this check)</span><br>';
} else {
	echo 'Required version (' . $p['req_at_least'] . ') is below 4.6 so a <b>load_plugin_textdomain</b> is needed.<br>Please make sure you load it at a certain point in your plugin.' .
    ' <form style="display: inline;" action="https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#loading-text-domain"><input type="submit" value="More info" /></form>';
	echo '<span style="color: green;">(more code needed here to perform this check)</span><br>';
}
	
  // MORE CODE NEEDED HERE TO CHECK

  
  
// Language packs
echo '<h3>Language packs created:</h3>';
$l_arr = array();
$v_arr = array();
$f = checkplug_get_file_contents('https://api.wordpress.org/translations/plugins/1.0/?slug=' . $p['slug']);
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
	

// Translation editors from https://translate.wordpress.org/projects/wp-plugins/$p['slug']/contributors
echo '<h3>Translation editors per locale</h3>';
$url = 'https://translate.wordpress.org/projects/wp-plugins/' . $p['slug'] . '/contributors';
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

// Translation status per locale
// Just adding to the global table
// Only create new locales if > 0%
$f = checkplug_get_file_contents('https://translate.wordpress.org/api/projects/wp-plugins/' . $p['slug'] . '/stable');
if ($f) {
	$a = json_decode($f);
	foreach ($a->translation_sets as $item) {
		// add to central array
		$loc_code = $item->wp_locale;
		if ( ( !isset($l_arr[$loc_code]) ) && ( $item->percent_translated > 3 ) ) {
    		$l_arr[$loc_code]['language'] = $loc_code;
	    	$l_arr[$loc_code]['english_name'] = $item->name;
		}
		if ( isset($l_arr[$loc_code]) ) {
    		$l_arr[$loc_code]['trstat_stable'] = $item->percent_translated;
	    	$l_arr[$loc_code]['trurl_stable'] = 'https://translate.wordpress.org/projects/wp-plugins/' . $p['slug'] . '/stable/' . $item->locale . '/default' ;
		}
	}
}
$f = checkplug_get_file_contents('https://translate.wordpress.org/api/projects/wp-plugins/' . $p['slug'] . '/dev');
if ($f) {
	$a = json_decode($f);
	foreach ($a->translation_sets as $item) {
		// add to central array
		$loc_code = $item->wp_locale;
		if ( ( !isset($l_arr[$loc_code]) ) && ( $item->percent_translated > 3 ) ) {
    		$l_arr[$loc_code]['language'] = $loc_code;
	    	$l_arr[$loc_code]['english_name'] = $item->name;
		}
		if ( isset($l_arr[$loc_code]) ) {
    		$l_arr[$loc_code]['trstat_dev'] = $item->percent_translated;
	    	$l_arr[$loc_code]['trurl_dev'] = 'https://translate.wordpress.org/projects/wp-plugins/' . $p['slug'] . '/dev/' . $item->locale . '/default' ;
		}
	}
}

// Latest Revision log
echo '<h3>Latest Revision log entries</h3>';
$url = 'https://plugins.trac.wordpress.org/log/' . $p['slug'] . '/?limit=10&mode=stop_on_copy&format=rss';
$rss_items = checkplug_fetch_feed( $url );
if (!$rss_items) {
	echo '<span style="color: orange;">' . 'WARNING: Unable to get <a href="' . $url . '">latest revision log</a>, might be just a temporary issue.</span>';
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


// Links
checkplug_print_links();

// Summary table
echo '<h3>Summary table</h3>';
echo '<table border="1">';
echo '<tr><th>Locale</th><th>Name</th><th colspan="2">Language pack</th><th>Editor (PTE)</th><th>Stable</th><th>Dev</th></tr>';
ksort($l_arr);
foreach ( $l_arr as $loc ) {
	echo '<tr><td>' . $loc['language'] . '</td><td>' . $loc['english_name'] . '</td><td>' . $loc['version'] . '</td><td>' . $loc['updated'] . '</td><td>' . $loc['pte'] .
	    '</td><td><a href="' . $loc['trurl_stable'] . '">' . $loc['trstat_stable'] . '%</a></td><td><a href="' . $loc['trurl_dev'] . '">' . $loc['trstat_dev'] . '%</a></td></tr>';
}
echo '</table>';




/*
 * FUNCTIONS
 */

function checkplug_show_error($text) {
	echo '<span style="color: red;">ERROR: ' . $text . '</span><br>';
	checkplug_print_links();
	die();
}

function checkplug_show_warning($text) {
	echo '<span style="color: orange;">WARNING: ' . $text . '</span><br>';
}

function checkplug_print_links() {
	global $p;

	echo '<h3>Links</h3>';
	echo '<table>';
	$url = 'https://wordpress.org/plugins/' . $p['slug'] . '/';
	echo sprintf('<tr><td>%s</td><td><a href="%s">%s</td></tr>', 'Plugin page', $url, $url);
	if ($p['svn_base_dir']) echo sprintf('<tr><td>%s</td><td><a href="%s">%s</td></tr>', 'Base SVN folder', $p['svn_base_dir'], $p['svn_base_dir']);
	if ($p['fp_trunk_readme']) echo sprintf('<tr><td>%s</td><td><a href="%s">%s</td></tr>', 'Trunk readme', $p['fp_trunk_readme'], $p['fp_trunk_readme']);
	if ($p['fp_tags_readme']) echo sprintf('<tr><td>%s</td><td><a href="%s">%s</td></tr>', 'Tags readme', $p['fp_tags_readme'], $p['fp_tags_readme']);
	if ($p['fp_main_php']) {
		echo sprintf('<tr><td>%s</td><td><a href="%s">%s</td></tr>', 'Main php file', $p['fp_main_php'], $p['fp_main_php']);
		$url = 'https://translate.wordpress.org/projects/wp-plugins/' . $p['slug'] . '/contributors';
		echo sprintf('<tr><td>%s</td><td><a href="%s">%s</td></tr>', 'Translation Editors', $url, $url);
		$url = 'https://translate.wordpress.org/projects/wp-plugins/' . $p['slug'];
		echo sprintf('<tr><td>%s</td><td><a href="%s">%s</td></tr>', 'Locale Translations', $url, $url);
		$url = 'https://plugins.trac.wordpress.org/log/' . $p['slug'] . '/?limit=10&mode=stop_on_copy&format=rss';
		echo sprintf('<tr><td>%s</td><td><a href="%s">%s</td></tr>', 'Revision log', $url, $url);
		$url = 'https://wordpress.slack.com/messages/meta-language-packs/search/in:%23meta-language-packs%20' . $p['slug'] . '/';
		echo sprintf('<tr><td>%s</td><td><a href="%s">%s</td></tr>', '<a href="https://make.wordpress.org/chat/">Slack</a> #meta-language-packs', $url, $url);
		$url = 'https://wordpress.slack.com/messages/meta-language-packs/search/in:%23polyglots-warnings%20' . $p['slug'] . '/';
		echo sprintf('<tr><td>%s</td><td><a href="%s">%s</td></tr>', '<a href="https://make.wordpress.org/chat/">Slack</a> #polyglots-warnings', $url, $url);
	}
	echo '</table>';
}

 
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

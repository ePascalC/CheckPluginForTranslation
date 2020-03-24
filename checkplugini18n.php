<?php

$tool_version = '0.4.6';
$p = array(); // will hold all the plugin info
$whitelist = array ('1.2.3.4', '5.6.7.8');

// Some DDoS protection
$ret = throttle_requests( $whitelist );

// Page header and CSS
page_start( $tool_version );

if ( $ret == 'whitelisted' ) echo '(=> IP whitelisted)<br>';

// Ask slug in inputbox if not already in URL
get_slug();

// If slug is not a slug, die!
if ( !is_valid_slug($p['slug']) ) {
    $args = array(
        	'type' => 'err',
        	'text' => 'That is not a valid slug in the URL',
        	'die'  => true,
        );
    show_msg( $args );
}

// Add an indication in the log
error_log('Running for ' . $p['slug']);


// Get WP and other current versions
$current_stable_ver = get_current_versions();
$wp_dev_major = get_major_version( $current_stable_ver['wp_dev'] );
$wp_current_major = get_major_version( $current_stable_ver['wp_current'] );


// Check if plugin is closed
check_closed();

// Check base dir
$url = 'https://plugins.svn.wordpress.org/' . $p['slug'];
if ( !is_accessible( $url . '/' ) ) {
    $args = array(
        	'type' => 'err',
        	'text' => 'Unable to find path ' . $url . '/',
        	'die'  => true,
        	'cbid' => __LINE__,
        );
    show_msg( $args );
}
$p['svn_base_dir'] = $url;
echo 'Plugin <b>slug</b>: ' . $p['slug'] . '<br>';

// Get readme.txt and .md correct upper/lower case spelling
// Check access to the files
// Show warning if both txt and md exist
get_readmes_filenames('trunk');
echo '<b>Readme file</b> is available on ' . $p['fp_trunk_readme'] . '<br>';

// Readme file size
$i = get_file_size( $p['fp_trunk_readme'] );
$fs = (int)($i/1024);
if ($fs == 0) $fs = 1;
echo '- Readme filesize: ' . $fs . 'k<br>';
if ( $i > 10240 ) {
    $args = array(
        	'type' => 'warn',
        	'spaces' => 2,
        	'text' => 'Please reduce your ' . $p['fn_trunk_readme'] . ' in /trunk as it is over 10k',
        	'action' => 'link',
        	'action_text' => 'https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/#file-size',
        	'cbid' => __LINE__,
        );
    show_msg( $args );
}
    
// Get trunk readme file contents
$headers = array(
            'Tags'            => 'Tags',
            'RequiresAtLeast' => 'Requires at least',
            'TestedUpTo'      => 'Tested up to',
            'StableTag'       => 'Stable tag',
            'RequiresPHP'     => 'Requires PHP',
        );
$trunk_tags = get_file_tags( $p['fp_trunk_readme'], $headers );

//Readme tags and check stable tag
$p['stable_tag'] = $trunk_tags['StableTag'];
$p['req_at_least'] = $trunk_tags['RequiresAtLeast'];
$p['req_php'] = $trunk_tags['RequiresPHP'];
$p['tested_up_to'] = $trunk_tags['TestedUpTo'];
$p['tags'] = explode(',', $trunk_tags['Tags'] );
$p['tags_string'] = $trunk_tags['Tags'];

if ( $p['stable_tag'] ) {
	echo '- Stable tag: ' . $p['stable_tag'] . '<br>';
} else {
    $args = array(
        	'type' => 'warn',
        	'spaces' => 2,
        	'text' => 'Stable tag not found in readme file so defaulting to trunk, but better define it!',
        	'action' => 'link',
        	'action_text' => 'https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/#how-the-readme-is-parsed',
        	'cbid' => __LINE__,
        );
    show_msg( $args );
	$p['stable_tag'] = 'trunk';
}
// If trunk, check the tags in the readme.txt trunk
if ( $p['stable_tag'] == 'trunk' ) {
    $args = array(
        	'type' => 'warn',
        	'spaces' => 2,
        	'text' => 'The plugin sources used by everyone are in /trunk, is this what is expected?',
        	'action' => 'link',
        	'action_text' => 'https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/#how-the-readme-is-parsed',
        	'cbid' => __LINE__,
        );
    show_msg( $args );
}

// Get the tags folder if any
$tags = get_all_tags_folders( $p['svn_base_dir'] . '/tags' );
if ($tags) {
	echo '<p class="ind">';
	echo 'Tag folders found: ';
	$first = true;
	$errors = '';
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
		if ( !is_valid_version ( $tag ) ) {
		    $errors = $errors . ', ' . $tag;
		}
	}
	if ($p['stable_tag'] == 'trunk') {
		echo ' (but none are used, only trunk)';
	}
	// If over 20, give recommendations to clean
	if ( count( $tags ) > 20 ) {
	    echo '<br>';
        $args = array(
            	'type' => 'rec',
            	'text' => 'You have over 20 versions here, is that really needed? Does anybody actually need those older versions? Why have the system build ZIP files for them?',
            	'cbid' => __LINE__,
            );
        show_msg( $args );
	}
	if ( $errors ) {
	    echo '<br>';
        $args = array(
            	'type' => 'rec',
            	'text' => 'You can use any naming you want for your Stable Tag and so folders in /tags, however most common usage is to use versions. The following folders are not valid PHP version numbers :' . $errors,
            	'cbid' => __LINE__,
            );
        show_msg( $args );
	}
	echo '</p>';
} else {
	if ($p['stable_tag'] == 'trunk') {
		echo '&nbsp;&nbsp;No folders found under /tags, but trunk is used so that is fine.';
	} else {
        $args = array(
            	'type' => 'warn',
            	'text' => 'No folders found under /tags although your Stable Tag is set to ' . $p['stable_tag'] .
		            ' ! So create the <b>' . $p['stable_tag'] . '</b> folder under /tags OR change the Stable Tag to <b>trunk</b>. Defaulting now to trunk. ',
            	'action' => 'link',
            	'action_text' => 'https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/#how-the-readme-is-parsed',
            	'cbid' => __LINE__,
            );
        show_msg( $args );	    
		$p['stable_tag'] = 'trunk';
	}
	echo '<br>';
}

// Make sure the needed folder exists
if ($p['stable_tag'] != 'trunk') {
	$folder = $p['svn_base_dir'] . '/tags/' . $p['stable_tag'] . '/';
	if ( !is_accessible( $folder ) ) {
        $args = array(
            	'type' => 'warn',
            	'text' => 'Unable to find or access ' . $folder . ' Defaulting to trunk.',
            	'action' => 'link',
            	'action_text' => 'https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/#how-the-readme-is-parsed',
            	'cbid' => __LINE__,
            );
        show_msg( $args );	    
		$p['stable_tag'] = 'trunk';
		$folder = $p['svn_base_dir'] . '/trunk/';
	} else {
        // Get readme in the /tags folder
        get_readmes_filenames( $p['stable_tag'] );
        echo '<b>Readme file</b> is available on ' . $p['fp_tags_readme'] . '<br>';
        
        // Readme file size
        $i = get_file_size( $p['fp_tags_readme'] );
        $fs = (int)($i/1024);
        if ($fs == 0) $fs = 1;
        echo '- Readme filesize: ' . $fs . 'k<br>';
        if ( $i > 10240 ) {
            $args = array(
                	'type' => 'warn',
                	'spaces' => 2,
                	'text' => 'Please reduce your ' . $p['fn_tags_readme'] . ' in /tags/' . $p['stable_tag']  . ' as it is over 10k',
                	'action' => 'link',
                	'action_text' => 'https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/#file-size',
                	'cbid' => __LINE__,
                );
            show_msg( $args );
        }
            
        // Get trunk readme file contents
        $headers = array(
                    'Tags'            => 'Tags',
                    'RequiresAtLeast' => 'Requires at least',
                    'TestedUpTo'      => 'Tested up to',
                    'StableTag'       => 'Stable tag',
                    'RequiresPHP'     => 'Requires PHP',
                );
        $trunk_tags = get_file_tags( $p['fp_tags_readme'], $headers );
        
        //Readme tags
        $p['req_at_least'] = $trunk_tags['RequiresAtLeast'];
        $p['req_php'] = $trunk_tags['RequiresPHP'];
        $p['tested_up_to'] = $trunk_tags['TestedUpTo'];
        $p['tags'] = explode(',', $trunk_tags['Tags'] );
        $p['tags_string'] = $trunk_tags['Tags'];
    }
} else {
    $folder = $p['svn_base_dir'] . '/trunk/';
}

// Show the readme tags

// Requires at least
echo '- Requires at least: '; echo $p['req_at_least']?:'&lt;not set&gt;'; echo '<br>';
if ( !$p['req_at_least'] ) {
    $args = array(
    	'type' => 'warn',
		'spaces' => 2,
    	'text' => 'No <b>Requires at least:</b> is set in the readme file. Considering 1.0 for further testing on this page, but please consider adding it.',
    	'cbid' => __LINE__,
    );
    show_msg( $args );
	$p['req_at_least'] = '1.0';
} else {
	if ( version_has_wp( $p['req_at_least'] ) ) {
		$args = array(
				'type' => 'warn',
				'spaces' => 2,
				'text' => 'Your <b>Requires at least:</b> starts with "WP" or "WordPress", please use only the version, something like ' . $wp_current_major,
				'cbid' => __LINE__,
				);
		show_msg( $args );
	} else {
		if ( !is_valid_version( $p['req_at_least'] ) ) {
			$args = array(
					'type' => 'err',
					'spaces' => 2,
					'text' => 'Your <b>Requires at least</b> is not a valid version. It should be something like ' . $wp_current_major,
					'action' => 'link',
					'action_text' => 'https://www.php.net/manual/en/function.version-compare.php',
					'cbid' => __LINE__,
					);
			show_msg( $args );
		} else {		
			// Compare Tested_up_to with Req_at_least
			if ( is_valid_version( $p['tested_up_to'] ) ) {
				if ( version_compare($p['req_at_least'], $p['tested_up_to'], '>') ) {
					$args = array(
						'type' => 'warn',
						'text' => 'Your <b>Requires at least:</b> is set to <b>' . $p['req_at_least'] . '</b>, but the plugin seems only <b>Tested up to: ' . $p['tested_up_to'] . '</b>',
						'cbid' => __LINE__,
					);
					show_msg( $args );
				}
			}
		}
	}
}

// Tested up to
echo '- Tested up to: '; echo $p['tested_up_to']?:'&lt;not set&gt;'; echo '<br>';
if ( !$p['tested_up_to'] ) {
    $args = array(
    	'type' => 'warn',
		'spaces' => 2,
    	'text' => 'No <b>Tested up to:</b> is set in the readme file. Consider adding it to positively impact your ranking in the repository.',
		'action' => 'link',
		'action_text' => 'https://meta.trac.wordpress.org/ticket/3936#comment:9',
    	'cbid' => __LINE__,
    );
    show_msg( $args );
} else {
	if ( version_has_wp( $p['tested_up_to'] ) ) {
		$args = array(
				'type' => 'warn',
				'spaces' => 2,
				'text' => 'Your <b>Tested up to:</b> starts with "WP" or "WordPress", please use only the version, something like ' . $wp_current_major,
				'cbid' => __LINE__,
				);
		show_msg( $args );
	} else {
		if ( !is_valid_version( $p['tested_up_to'] ) ) {
			$args = array(
					'type' => 'err',
					'spaces' => 2,
					'text' => 'Your <b>Tested up to:</b> is not a valid version. It should be something like ' . $wp_current_major,
					'action' => 'link',
					'action_text' => 'https://www.php.net/manual/en/function.version-compare.php',
					'cbid' => __LINE__,
					);
			show_msg( $args );
		} else {		
			if ( !version_has_only_major_minor( $p['tested_up_to'] ) ) {
				$args = array(
					'type' => 'rec',
					'spaces' => 2,
					'text' => 'You can limit your <b>Tested up to:</b> to just the major release (e.g. ' . $wp_current_major . '). The repository will automatically add the latest minor version as plugins shouldn’t break with a minor update.',
					'action' => 'link',
					'action_text' => 'https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/#readmeheader-information',
					'cbid' => __LINE__,
				);
				show_msg( $args );
			}
			if ( version_compare($wp_dev_major, $p['tested_up_to'], '<') ) {
				$args = array(
					'type' => 'err',
					'spaces' => 2,
					'text' => 'Your <b>Tested up to:</b> is set higher then the current development version (' . $wp_dev_major . ')',
					'cbid' => __LINE__,
				);
				show_msg( $args );
			}
		}
	}
}

// Requires PHP
echo '- Requires PHP: '; echo $p['req_php']?:'&lt;not set&gt;'; echo '<br>';
if ( !empty( $p['req_php'] ) ) {
	if ( !is_valid_version( $p['req_php'] ) ) {
		$args = array(
				'type' => 'err',
				'spaces' => 2,
				'text' => 'Your <b>Requires PHP:</b> is not a valid version. It should be something like 5.6',
				'action' => 'link',
				'action_text' => 'https://www.php.net/manual/en/function.version-compare.php',
				'cbid' => __LINE__,
				);
		show_msg( $args );
	}
}

// Max 12, better not more than 5 tags
if ( isset($p['tags'] ) ) {
	echo '- Tags: ' . $p['tags_string'] . '<br>';
	if ( count($p['tags']) > 12 ) {
		$args = array(
				'type' => 'err',
				'spaces' => 2,
				'text' => 'You have more than 12 tags defined, max 5 should be your target! Important terms should go in the body of the readme as a list of keywords is basically useless for the search engine.',
				'action' => 'link',
				'action_text' => 'https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/#12-public-facing-pages-on-wordpress-org-readmes-must-not-spam',
				'cbid' => __LINE__,
					);
		show_msg( $args );
    } else {
		if ( count($p['tags']) > 5 ) {
			$args = array(
					'type' => 'rec',
					'spaces' => 2,
					'text' => 'You have more than 5 tags defined, only the first 5 are displayed so you could remove the extra ones.',
					'action' => 'info',
					'action_text' => 'Tag 6 and further work for sorting (like if you go to /tags/tagname) but the rest may or may not help you. Important terms should go in the body of the readme. Lists of keywords are basically useless for the search engine. People search for solutions to the problems they\'re having, not keywords. Think sentences, not lists of words.',
					'cbid' => __LINE__,
				);
			show_msg( $args );
		}
	}
}

echo '<br>';

// Get the  php files in the folder
$files = checkplug_get_file_contents( $folder );
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
    $args = array(
    	'type' => 'err',
    	'text' => 'Unable to find or access php files in ' . $folder,
    );
    show_msg( $args );
}

// Loop all php files found
foreach ($php_files as $php_file) {
	// Find and read the main php file
	$p['fp_main_php'] = $folder . $php_file;
	$headers = array(
		'Name'        => 'Plugin Name',
		'PluginURI'   => 'Plugin URI',
		'Version'     => 'Version',
		'Description' => 'Description',
		'Author'      => 'Author',
		'AuthorURI'   => 'Author URI',
		'License'     => 'License',
		'LicenseURI'  => 'License URI',
		'TextDomain'  => 'Text Domain',
		'DomainPath'  => 'Domain Path',
		'Network'     => 'Network',
		'RequiresWP'  => 'Requires at least',
		'RequiresPHP' => 'Requires PHP',
		// Site Wide Only is deprecated in favor of Network.
		'_sitewide'   => 'Site Wide Only',
		);
	$phpfile_tags = get_file_tags( $p['fp_main_php'], $headers );
	if ( !empty( $phpfile_tags['Name'] ) ) {
		echo 'File <b>' . $php_file . '</b> in ' . $folder . ' had the info needed:<br>';
		echo ' - Plugin Name: '; echo $phpfile_tags['Name']?:'&lt;not set&gt;'; echo '<br>';
		echo ' - Version: '; echo $phpfile_tags['Version']?:'&lt;not set&gt;'; echo '<br>';
		echo ' - Text Domain: '; echo $phpfile_tags['TextDomain']?:'&lt;not set&gt;'; echo '<br>';

		break;
	}
}

if ( empty( $phpfile_tags['Name'] ) ) {
    if ( !empty( $phpfile_tags['Version'] ) ) {
        $args = array(
    		'type' => 'err',
    		'spaces' => 2,
    		'text' => 'Your plugin in <b>' . $php_file . '</b> does not seem to contain the mandatory "Plugin Name" tag!',
    		'action' => 'link',
    		'action_text' => 'https://developer.wordpress.org/plugins/plugin-basics/header-requirements/#header-fields',
        	'cbid' => __LINE__,
    		);
    	show_msg( $args );
    } else {
        $args = array(
    		'type' => 'err',
    		'text' => 'It looks like no php file contained the headers in <b>' . $folder . '</b>, so cannot check anything!',
    		);
    	show_msg( $args ); 
    }
}

// Check Text domain
if ( empty( $phpfile_tags['TextDomain'] ) ) {
    if ( version_compare($p['req_at_least'], '4.6', '>=') ) {
        // No Text domain needs to be defined if WP is 4.6+
        $phpfile_tags['TextDomain'] = $p['slug'];
    } else {
	   $args = array(
			'type' => 'err',
			'spaces' => 2,
			'text' => 'Your "Requires at least" is below 4.6. so you need to set a Text Domain in <b>' . $php_file . '</b> and it needs to be <b>' . $p['slug'] . '</b> to correctly internationalize your plugin! ',
			'action' => 'link',
			'action_text' => 'https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#text-domains',
        	'cbid' => __LINE__,
			);
		show_msg( $args );
    }
}
if ( $phpfile_tags['TextDomain'] ) {
	if ( $phpfile_tags['TextDomain'] != $p['slug'] ) {
		if ( version_compare($p['req_at_least'], '4.6', '>=') ) {
			// No Text domain needs to be defined if WP is 4.6+
		   $args = array(
				'type' => 'err',
				'spaces' => 2,
				'text' => 'Your plugin slug is <b>' . $p['slug'] . '</b>, but your Text Domain is set as <b>' . $phpfile_tags['TextDomain'] .'</b>. As the "Requires at least" is at least 4.6, you should remove the Text Domain tag, but you still need to modify the text domain in all your source files.',
				'action' => 'link',
				'action_text' => 'https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#text-domains',
            	'cbid' => __LINE__,
				);
			show_msg( $args );
		} else {        
		   $args = array(
				'type' => 'err',
				'spaces' => 2,
				'text' => 'Your plugin slug is <b>' . $p['slug'] . '</b>, but your Text Domain is <b>' . $phpfile_tags['TextDomain'] .'</b>. Change your Text Domain so it is equal to your slug and modify the text domain in all your source files. This change is needed because your "Requires at least" is below 4.6.',
				'action' => 'link',
				'action_text' => 'https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#text-domains',
            	'cbid' => __LINE__,
				);
			show_msg( $args );
		}
	}
}

// No underscores in text domain
$pos = strpos( $phpfile_tags['TextDomain'], '_' );
if( false !== $pos) {
   $args = array(
		'type' => 'err',
		'spaces' => 2,
		'text' => 'Your Text Domain should not contain underscores!',
		'action' => 'link',
		'action_text' => 'https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#text-domains',
    	'cbid' => __LINE__,
		);
	show_msg( $args );	
}

// If trunk, check if version has existing tag
if ( ( $p['stable_tag'] == 'trunk' ) && ( in_array( $phpfile_tags['Version'], $tags ) ) ) {
   $args = array(
		'type' => 'rec',
		'text' => 'Trunk is being used, but there is a folder under tags with the same name as your plugin version (' . $phpfile_tags['Version'] . '). This could lead to confusion',
    	'cbid' => __LINE__,
		);
	show_msg( $args );	
}	

// Domain Path check
if ( $phpfile_tags['DomainPath'] ) {
	echo ' - Domain Path: ' . $phpfile_tags['DomainPath'] . '<br>';
	$args = array(
		'type' => 'rec',
		'spaces' => 2,
		'text' => 'The <b>Domain Path</b> header can be omitted as the plugin is in the official WordPress Plugin Directory.',
		'action' => 'link',
		'action_text' => 'https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#domain-path',
    	'cbid' => __LINE__,
		);
	show_msg( $args );	
}

// License
if ( !$phpfile_tags['License'] ) {
	$args = array(
		'type' => 'rec',
		'spaces' => 2,
		'text' => 'Setting a <b>License</b> header is strongly recommended, preferably the same one as WordPress itself.',
		'action' => 'link',
		'action_text' => 'https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/#1-plugins-must-be-compatible-with-the-gnu-general-public-license',
    	'cbid' => __LINE__,
		);
	show_msg( $args );
} else {
	echo ' - License: ' . $phpfile_tags['License'] . '<br>';
	if ( filter_var( $phpfile_tags['License'], FILTER_VALIDATE_URL ) ) {
		$args = array(
			'type' => 'rec',
			'spaces' => 2,
			'text' => 'It looks like the <b>License</b> header is a URL, so you probably wanted to add it under <b>License URI</b>?',
			'action' => 'link',
			'action_text' => 'https://developer.wordpress.org/plugins/plugin-basics/header-requirements/#header-fields',
			'cbid' => __LINE__,
			);
    	show_msg( $args );
	} else {
		if ( license_is_not_for_software( $phpfile_tags['License'] ) ) {
			$args = array(
				'type' => 'err',
				'spaces' => 2,
				'text' => 'The <b>' . $phpfile_tags['License'] . '</b> license is not for software, changing to the same license as WordPress — “GPLv2 or later” — is strongly recommended.',
				'action' => 'link',
				'action_text' => 'https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/#1-plugins-must-be-compatible-with-the-gnu-general-public-license',
				'cbid' => __LINE__,
				);
			show_msg( $args );
		} else {
			if ( license_is_gpl_incompatible( $phpfile_tags['License'] ) ) {
				$args = array(
					'type' => 'err',
					'spaces' => 2,
					'text' => 'The <b>' . $phpfile_tags['License'] . '</b> license is incompatible! Changing to the same license as WordPress — “GPLv2 or later” — is strongly recommended.',
					'action' => 'link',
					'action_text' => 'https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/#1-plugins-must-be-compatible-with-the-gnu-general-public-license',
					'cbid' => __LINE__,
					);
				show_msg( $args );
			} else {
				if ( !license_is_gpl_compatible( $phpfile_tags['License'] ) ) {
					$args = array(
						'type' => 'rec',
						'spaces' => 2,
						'text' => 'The <b>' . $phpfile_tags['License'] . '</b> license is not a standard abbreviation for a compatible license. You might have a compatible license but the correct abbreviation (e.g. GPLv2) is expected here...',
						'action' => 'link',
						'action_text' => 'https://developer.wordpress.org/plugins/plugin-basics/header-requirements/#header-fields',
						'cbid' => __LINE__,
						);
					show_msg( $args );
					error_log('=> Lic: ' . $phpfile_tags['License']);
				}
			}
		}
			
	}
}

// Network or _sitewide
if ( $phpfile_tags['_sitewide'] ) {
	$args = array(
		'type' => 'warn',
		'spaces' => 2,
		'text' => 'The <b>_sitewide</b> header is deprecated, please use <b>Network: true</b>',
    	'cbid' => __LINE__,
		);
	show_msg( $args );
}
if ( $phpfile_tags['Network'] ) {
	if ( ( $phpfile_tags['Network'] == 'true' ) || ( $phpfile_tags['Network'] == true ) ) {
		// all fine
	} else {
		$args = array(
			'type' => 'err',
			'spaces' => 2,
			'text' => 'The <b>Network</b> header can only have <b>true</b> as value!',
        	'cbid' => __LINE__,
			);
		show_msg( $args );
	}
}

// load_plugin_textdomain checks
echo '<br>';
if ( is_valid_version( $p['req_at_least'] ) ) {
	if ( version_compare($p['req_at_least'], '4.6', '>=') ) {
		$args = array(
			'type' => 'rec',
			'text' => '"Requires at least" (' . $p['req_at_least'] . ') is at least 4.6 so no <b>load_plugin_textdomain</b> is needed. If you have the function somewhere, you can remove it.',
			'action' => 'link',
			'action_text' => 'https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#loading-text-domain',
			'cbid' => __LINE__,
		);
		show_msg( $args );
	} else {
		$args = array(
			'type' => 'warn',
			'text' => '"Requires at least" (' . $p['req_at_least'] . ') is below 4.6 so a <b>load_plugin_textdomain</b> is needed. Please make sure you load it at a certain point in your plugin.',
			'action' => 'link',
			'action_text' => 'https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#loading-text-domain',
			'cbid' => __LINE__,
		);
		show_msg( $args );
	} // MORE CODE NEEDED HERE TO CHECK
} else {
	$args = array(
		'type' => 'warn',
		'text' => 'Unable to evaluate "Requires at least" correctly (' . $p['req_at_least'] . ') so a <b>load_plugin_textdomain</b> is needed. Please make sure you load it at a certain point in your plugin.',
		'action' => 'link',
		'action_text' => 'https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#loading-text-domain',
		'cbid' => __LINE__,
		);
	show_msg( $args );
}
	

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
	if ( !isset ( $a->error ) ) {
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
}
$f = checkplug_get_file_contents('https://translate.wordpress.org/api/projects/wp-plugins/' . $p['slug'] . '/dev');
if ($f) {
	$a = json_decode($f);
	if ( !isset ( $a->error ) ) {
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

page_end();

/***************************
 ***************************
 ******* FUNCTIONS  ********
 ***************************
 ***************************/
function show_msg( $args ) {
	// $args['type'] : err, warn, rec
	// $args['spaces'] : number of nbsp before the text
	// $args['text']
	// $args['action'] : link, info
	// $args['action_text'] : The link or the hover text
   	// $args['die'] : true
   	// $args['cbid'] : line number (for the clipboard copy)

	if ( ! isset( $args['type'] ) )
		die( '$args type is empty' );
	if ( !in_array( $args['type'], array('err', 'warn', 'rec') ) )
		die( '$args type incorrect' );
	if ( ! isset($args['text']) )
		die( '$args text is empty' );
	if ( isset( $args['action'] ) ) {
		if ( !in_array( $args['action'], array('link', 'info') ) )
			die( '$args action incorrect' );
		if ( !isset( $args['action_text'] ) )
			die( '$args text_text is empty' );
	}
	if ( isset( $args['die'] ) ) {
	    if ( !is_bool( $args['die'] ) )
	        die( '$args die can only have true as value' );
	}
	$prefix = '';
	if ( isset( $args['spaces'] ) ) {
	    $prefix = str_repeat( '&nbsp;', $args['spaces'] );
	}
	    
	if ( $args['type'] == 'rec') {
	    $type_string = 'RECOMMENDATION: ';
	    echo $prefix;
		echo '<span style="color: DarkKhaki;">RECOMMENDATION: ' . $args['text'] . '</span>';
	}
	if ( $args['type'] == 'warn') {
	    $type_string = 'WARNING: ';
	    echo $prefix;
		echo '<span style="color: orange;">WARNING: ' . $args['text'] . '</span>';
	}
	if ( $args['type'] == 'err') {
        $type_string = 'ERROR: ';
	    echo $prefix;
		echo '<span style="color: red;">ERROR: ' . $args['text'] . '</span>';
	}
	if ( $args['action'] == 'info') {
		echo ' <div class="tooltip">i<span class="tooltiptext">' . $args['action_text'] . '</span></div>';
	}
	if ( $args['action'] == 'link') {
		echo ' <a href="' . $args['action_text'] . '" class="button" title="Opens in new tab" target="_blank">More info</a>';
	}
	if ( isset($args['cbid'] ) ) {
	    echo ' <span id="' . $args['cbid'] . '" style="display: none;">' . $type_string . $args['text'];
	    if ( $args['action_text'] ) echo ' (see ' . $args['action_text'] . ')';
	    echo '</span>';
	    echo '<img style="vertical-align: middle;" height="24" width="24" src="https://lh5.ggpht.com/b-7FA5CMqRQMFt5g77SJNbIw0qfHCuyg1kaiahhKIL4nmsumXH27MYNps1B2lvmqV6qI4kZ3M-DCN9k5OZ4lcbKT"' .
	      'alt="Copy to clipboard" title="Copy to clipboard" onClick="CopyCB(\'' . $args['cbid'] . '\')">';
	}
	echo '<br>';
	if ( $args['die'] )
	    die();
}

function check_closed() {
    global $p;
    $url = 'https://wordpress.org/plugins/' . $p['slug'] . '/';
    $doc1 = checkplug_get_file_contents($url);
    if ($doc1) {
        $doc = new DOMDocument;
	    @$doc->loadHTML($doc1);
	    $doc->preserveWhiteSpace = false;
	    $classname = 'notice-error';
	    $xpath = new DOMXPath($doc);
		$lists = $xpath->query("//*[contains(@class, '" . $classname . "')]");
		foreach ($lists as $list) {
            $args = array(
        		'type' => 'warn',
        		'text' => 'The plugin is mentioning an error: ' . $list->nodeValue,
    			'action' => 'link',
        		'action_text' => $url,
            	'cbid' => __LINE__,
    		);
    	    show_msg( $args );
    	    echo '<br>';
		}
    } else {
        $args = array(
    		'type' => 'err',
    		'text' => 'The plugin cannot be accessed WordPress Plugin Directory! (' . $url . ')',
        	'cbid' => __LINE__,
		);
	    show_msg( $args );
    }
}

function is_accessible( $url ) {
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
	if ( $retcode == 200) {
	    return true;
	} else {
	    return false;
	}
}

function get_file_tags( $file, $headers) {
	$file_data = checkplug_get_file_contents( $file, 10000 );

    // Make sure we catch CR-only line endings.
    $file_data = str_replace( "\r", "\n", $file_data );
 
    foreach ( $headers as $field => $regex ) {
        if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( $regex, '/' ) . ':(.*)$/mi', $file_data, $match ) && $match[1] ) {
            $headers[ $field ] = trim( preg_replace( '/\s*(?:\*\/|\?>).*/', '', $match[1] ) );
        } else {
            $headers[ $field ] = '';
        }
    }
 
    return $headers;
}

function get_readmes_filenames( $context ) {
    // context is trunk OR folder under tags
    global $p;
    
    unset($p['readme_ext_txt']);
    unset($p['readme_ext_md']);

    if ( $context == 'trunk' ) {
        $text = checkplug_get_file_contents( $p['svn_base_dir'] .  '/trunk/' );
    } else {
        $text = checkplug_get_file_contents( $p['svn_base_dir'] . '/tags/' . $p['stable_tag'] . '/' );
    }
    $lines = explode("\n", $text);
    foreach ($lines as $line) {
    	if (stripos($line, '<a href="readme.txt">') !== false) {
    	    if ( $context == 'trunk' ) { 
    		    $p['fn_trunk_readme'] = checkplug_get_text_between('<li><a href="', '"', $line);
    	    } else {
    		    $p['fn_tags_readme'] = checkplug_get_text_between('<li><a href="', '"', $line);
    	    }
    		$p['readme_ext_txt'] = 'y';
    	}
    	if (stripos($line, '<a href="readme.md">') !== false) {
    	    if ( $context == 'trunk' ) { 
        	    if ( !isset( $p['fn_trunk_readme'] ) )
        	        $p['fn_trunk_readme'] = checkplug_get_text_between('<li><a href="', '"', $line);
        		$p['readme_ext_md'] = 'y';
    	    } else {
        	    if ( !isset( $p['fn_tags_readme'] ) )
        	        $p['fn_tags_readme'] = checkplug_get_text_between('<li><a href="', '"', $line);
        		$p['readme_ext_md'] = 'y';
    	    }
    	}
    
    }
    if ( $context == 'trunk' ) { 
        if (!$p['fn_trunk_readme']) {
            $args = array(
                	'type' => 'err',
                	'text' => 'Unable to find a readme file in folder ' . $p['svn_base_dir'] . '/trunk/',
                	'die'  => true,
                );
            show_msg( $args );
        }
    } else {
        if (!$p['fn_tags_readme']) {
            $args = array(
                	'type' => 'err',
                	'text' => 'Unable to find a readme file in folder ' . $p['svn_base_dir'] . '/tags/' . $p['stable_tag'] . '/',
                	'die'  => true,
                );
            show_msg( $args );
        }
    }

    // Check if readme is accessible
    if ( $context == 'trunk' ) { 
        $p['fp_trunk_readme'] = $p['svn_base_dir'] . '/trunk/' . $p['fn_trunk_readme'];
        if ( !is_accessible( $p['fp_trunk_readme'] ) ) {
            $args = array(
                	'type' => 'err',
                	'text' => 'Unable to read file ' . $p['fp_trunk_readme'],
                	'die'  => true,
                );
            show_msg( $args );
        }
    } else {
        $p['fp_tags_readme'] = $p['svn_base_dir'] . '/tags/' . $p['stable_tag'] . '/' . $p['fn_tags_readme'];
        if ( !is_accessible( $p['fp_tags_readme'] ) ) {
            $args = array(
                	'type' => 'err',
                	'text' => 'Unable to read file ' . $p['fp_tags_readme'],
                	'die'  => true,
                );
            show_msg( $args );
        }
    }
    // If both readme.txt and readme.md exist, the txt wins
    if ( $p['readme_ext_txt'] && $p['readme_ext_md'] ) {
       $args = array(
            	'type' => 'warn',
            	'text' => 'You have both a readme.txt and readme.md . Only the <b>.txt</b> is taken into account.',
            );
        show_msg( $args );
    }

}

function get_all_tags_folders( $tags_folder ) {
    // Get the tags folder if any
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
    }
    return $tags;
}

function checkplug_print_links() {
	global $p;
	if ( !$p['svn_base_dir'] )
		return;

	echo '<h3>Links</h3>';
	echo '<table>';
	$url = 'https://wordpress.org/plugins/' . $p['slug'] . '/';
	echo sprintf('<tr><td>%s</td><td><a href="%s">%s</td></tr>', 'Plugin page', $url, $url);
	echo sprintf('<tr><td>%s</td><td><a href="%s">%s</td></tr>', 'Base SVN folder', $p['svn_base_dir'], $p['svn_base_dir']);
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
		$url = 'https://app.slack.com/client/T024MFP4J/C0E7F4RND';
		echo sprintf('<tr><td>%s</td><td><a href="%s">%s</td></tr>', '<a href="https://make.wordpress.org/chat/">Slack</a> #meta-language-packs', $url, $url);
		$url = 'https://app.slack.com/client/T024MFP4J/C0AFVRSHX';
		echo sprintf('<tr><td>%s</td><td><a href="%s">%s</td></tr>', '<a href="https://make.wordpress.org/chat/">Slack</a> #polyglots-warnings', $url, $url);
	}
	echo '</table>';
}

 
function checkplug_get_file_contents($url, $bytes = 0) {
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
	if ($bytes > 0) {
	    $bytes = $bytes - 1;
	    curl_setopt($ch, CURLOPT_RANGE, '0-' . $bytes);
    }
	    
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

/**
 * Returns the size of a file without downloading it, or -1 if the file
 * size could not be determined.
 *
 * @param $url - The location of the remote file to download. Cannot
 * be null or empty.
 *
 * @return The size of the file referenced by $url, or -1 if the size
 * could not be determined.
 */
function get_file_size( $url ) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $info = curl_exec($ch);	
    curl_close($ch);
    $v = checkplug_get_text_between('content-length: ', 'last-', $info);
    if ( is_null($v) ) return 0;
    return (int)$v;

}

function version_has_wp ( $version ) {
	$version = trim( $version );
	if ( strtoupper( substr( $version, 0, 2 ) ) === "WP" )
		return true;
	if ( strtoupper( substr( $version, 0, 9 ) ) === "WORDPRESS" )
		return true;

	return false;
}

function is_valid_version ( $version ) {
	$version = trim( $version );
	
	if ( !empty($version) ) {
		// Strip off any -alpha, -RC, -beta suffixes, as these complicate comparisons and are rarely used.
		list( $version, ) = explode( '-', $version );
		
		if ( preg_match( '!^\d+\.\d(\.\d+)?$!', $version ) )
			return true;
		if ( preg_match( '!^\d+\.\d\.\d(\.\d+)?$!', $version ) )
			return true;
	}
	
	return false;
}

function is_valid_slug ( $slug ) {
	if ( !empty($slug) ) {
	    $notallowed = array("*","/","(","=","&","'");
        $testCase = str_split($slug);

        foreach($testCase as $test)
        {
            if(in_array($test, $notallowed))
            {
                return false;
            }
        }
	    
		/*if ( preg_match ('/[\[\(\*=]+/', $slug) )
			return false;
			*/
	}
	
	return true;
}

function get_major_version ( $version ) {
	$version = trim( $version );
	
	if ( !empty($version) ) {
		// Strip off any -alpha, -RC, -beta suffixes
		$dashes = explode('-', $version);
		$version = $dashes[0];
		
		$dots = explode ('.', $version);
		if ( !empty( $dots[1] ) )
			return $dots[0] . '.' . $dots[1];
/*
		// Strip off any -alpha, -RC, -beta suffixes, as these complicate comparisons and are rarely used.
		list( $version, ) = explode( '-', $version );

		preg_match_all('/./', $version, $matches, PREG_OFFSET_CAPTURE);  
		if ( $matches[0][3][1] > 0 )
			return substr($version, 0, $matches[0][3][1]-1);
*/		
		return $version;
		
	}
	
	return false;
}

function version_has_only_major_minor ( $version ) {
	$version = trim( $version );
	if ( !is_valid_version( $version ) )
		return false;
	
	$dots = substr_count($version, '.');
	if ( $dots == 1 )
		return true;
	
	return false; 
}

function sanitize_text( $text ) {
    return trim(filter_var($text, FILTER_SANITIZE_STRING));
}

function throttle_requests( $whitelist = array() ) {
	/**
	ABUSE CHECK
	Throttle client requests to avoid DoS attack
	Found on https://gist.github.com/luckyshot/6077693
	*/
	if ( !empty( $whitelist ) ) {
    	if ( in_array(get_User_IpAddr(), $whitelist ) ) {
    	    return 'whitelisted';
    	}
	}
	session_start();
	$usage = array(5,5,5,10,20,30); // seconds to wait after each request
	if (isset($_SESSION['use_last'])) {
	  $nextin = $_SESSION['use_last']+$usage[$_SESSION['use_count']];
		if (time() < $nextin) {
			header('HTTP/1.0 403 Forbidden');
			die('Hey, you are going too fast, slowdown!<br>Please wait '.($nextin-time()).' seconds&hellip;' );
		}else{
			$_SESSION['use_count']++;
			if ($_SESSION['use_count'] > sizeof($usage)-1) {$_SESSION['use_count']=sizeof($usage)-1;}
		}
	}else{
		$_SESSION['use_count'] = 0;
	}
	$_SESSION['use_last'] = time();
}

function get_User_IpAddr(){
    if(!empty($_SERVER['HTTP_CLIENT_IP'])){
        //ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
        //ip pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }else{
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function page_start( $tool_version ) {
	?>
	<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>Plugin i18n Readiness wp-info.org</title>
	<link rel="apple-touch-icon" sizes="57x57" href="/tools/favicon/apple-icon-57x57.png">
	<link rel="apple-touch-icon" sizes="60x60" href="/tools/favicon/apple-icon-60x60.png">
	<link rel="apple-touch-icon" sizes="72x72" href="/tools/favicon/apple-icon-72x72.png">
	<link rel="apple-touch-icon" sizes="76x76" href="/tools/favicon/apple-icon-76x76.png">
	<link rel="apple-touch-icon" sizes="114x114" href="/tools/favicon/apple-icon-114x114.png">
	<link rel="apple-touch-icon" sizes="120x120" href="/tools/favicon/apple-icon-120x120.png">
	<link rel="apple-touch-icon" sizes="144x144" href="/tools/favicon/apple-icon-144x144.png">
	<link rel="apple-touch-icon" sizes="152x152" href="/tools/favicon/apple-icon-152x152.png">
	<link rel="apple-touch-icon" sizes="180x180" href="/tools/favicon/apple-icon-180x180.png">
	<link rel="icon" type="image/png" sizes="192x192"  href="/tools/favicon/android-icon-192x192.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/tools/favicon/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="96x96" href="/tools/favicon/favicon-96x96.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/tools/favicon/favicon-16x16.png">
	<link rel="manifest" href="/tools/favicon/manifest.json">
	<meta name="msapplication-TileColor" content="#ffffff">
	<meta name="msapplication-TileImage" content="/tools/favicon/ms-icon-144x144.png">
	<meta name="theme-color" content="#ffffff">
	
	<style>
		p.ind { padding-left: 1.5em; text-indent:-1.5em;}
		a.button{
			border-radius: 25px;
			display:inline-block;
			padding:0.05em 0.5em;
			margin:0.1em;
			border:0.1em solid #CCCCCC;
			box-sizing: border-box;
			text-decoration:none;
			color:#000000;
			background-color:#CCCCCC;
			text-align:center;
			position:relative;
		}
		a.button:hover{
			border-color:#7a7a7a;
		}
		a.button:active{
			background-color:#999999;
		}
		@media all and (max-width:30em){
			a.button{
				display:block;
				margin:0.1em auto;
			}
		}
		.tooltip {
			position:relative;
			border-radius: 25px;
			display:inline-block;
			padding:0.05em 0.5em;
			margin:0.1em;
			border:0.1em solid #CCCCCC;
			box-sizing: border-box;
			text-decoration:none;
			color:#000000;
			background-color:#CCCCCC;
			text-align:center;
		}
		
		.tooltip .tooltiptext {
		  visibility: hidden;
		  width: 300px;
		  background-color: #CCCCCC;
		  color:#000000;
		  text-align: center;
		  border-radius: 6px;
		  padding: 5px;
		  position: absolute;
		  z-index: 1;
		  top: -5px;
		  left: 110%;
		}
		
		.tooltip .tooltiptext::after {
		  content: "";
		  position: absolute;
		  top: 50%;
		  right: 100%;
		  margin-top: -5px;
		  border-width: 5px;
		  border-style: solid;
		  border-color: transparent black transparent transparent;
		}
		.tooltip:hover .tooltiptext {
		  visibility: visible;
		}
	</style>
	<script>
    	function CopyCB(id) {
    		var text = document.getElementById(id).innerText;
    		var elem = document.createElement("textarea");
    		document.body.appendChild(elem);
    		elem.value = text;
    		elem.select();
    		document.execCommand("copy");
    		document.body.removeChild(elem);
    	}
	</script>
	</head>
	<body>

	<?php
	// Show version and point to information
	echo '<table style="border: 1px solid"><tr>';
	echo '<td><img src="/tools/favicon/apple-icon-60x60.png"></td>';
	echo '<td><h2><b>Plugin i18n Readiness v' . $tool_version . '</b></h2> More info and help appreciated on <a href="https://github.com/ePascalC/CheckPluginForTranslation/issues">GitHub</a>, ';
	echo 'also check out <a href="http://wp-info.org/pa-qrg/">http://wp-info.org/pa-qrg/</a></td>';
	echo '</tr></table><br>';
}

function page_end() {
	echo '<br><br>';
	echo '</body>';
	echo '</html>';
}

function get_slug() {
	global $p;
	if ( $_GET['slug'] ) {
		// Check the allowed characters for a plugin slug and use that!
		$p['slug'] = strtolower( strip_tags( htmlspecialchars( $_GET['slug'] ) ) );
	} else {
		echo '<form method="get">Plugin Slug: <input type="text" name="slug"><input type="submit"></form>';
		$p['slug'] = 'bbpress';
		$args = array (
			'type' => 'warn',
			'text' => 'Please enter a plugin slug and hit "Submit". Using ' . $p['slug'] . ' as example.',
			);
		show_msg( $args );
		echo '<br>';
		echo file_get_contents('./checkplugini18n_cache.html');
		die();
	}	
}

function license_is_gpl_compatible($lic) {
	// https://www.gnu.org/licenses/license-list.html#GPLCompatibleLicenses
	$lic = strtoupper($lic); // uppercase
	$lic = str_replace(' ', '', $lic); // remove spaces
	$lic = str_replace('#', '', $lic);// remove hashtag
	
	$arr = array(
		'GPLV2ORLATER',
		'GNUGPL',
		'GNUGPLV3',
		'GPLV2',
		'GPL-2.0+',
		'GPL-2.0',
		'GPLV3',
		'LGPL',
		'LGPLV3',
		'LGPLV2.1',
		'AGPL',
		'AGPLV3.0',
		'GNUALLPERMISSIVE',
		'APACHE2',
		'ARTISTICLICENSE2',
		'CLARIFIEDARTISTIC',
		'BERKELEYDB',
		'BOOST',
		'MODIFIEDBSD',
		'CECILL',
		'CLEARBSD',
		'CRYPTIXGENERALLICENSE',
		'ECOS2.0',
		'ECL2.0',
		'EIFFEL',
		'EUDATAGRID',
		'EXPAT',
		'FREEBSD',
		'FREETYPE',
		'HPND',
		'IMATIX',
		'IMLIB',
		'IJG',
		'INFORMAL',
		'INTEL',
		'ISC',
		'MPL-2.0',
		'NCSA',
		'NETSCAPEJAVASCRIPT',
		'NEWOPENLDAP',
		'PERLLICENSE',
		'PUBLICDOMAIN',
		'PYTHON',
		'PYTHON1.6A2',
		'RUBY',
		'SGIFREEB',
		'STANDARDMLOFNJ',
		'UNICODE',
		'UPL',
		'UNLICENSE',
		'VIM',
		'W3C',
		'WEBM',
		'WTFPL',
		'WX',
		'WXWIND',
		'X11LICENSE',
		'XFREE861.1LICENSE',
		'ZLIB',
		'ZOPE2.0',
	);
	if ( in_array($lic, $arr) ) {
		return true;
	} else {
		return false;
	}
}

function license_is_gpl_incompatible($lic) {
	// https://www.gnu.org/licenses/license-list.html#GPLIncompatibleLicenses
	$lic = strtoupper($lic); // uppercase
	$lic = str_replace(' ', '', $lic); // remove spaces
	$lic = str_replace('#', '', $lic); // remove hashtag
	
	$arr = array(
		'AGPLV1.0',
		'ACADEMICFREELICENSE',
		'APACHE1.1',
		'APACHE1',
		'APSL2',
		'BITTORRENT',
		'ORIGINALBSD',
		'CECILL-B',
		'CECILL-C',
		'CDDL',
		'CPAL',
		'COMMONPUBLICLICENSE10',
		'CONDOR',
		'EPL',
		'EPL2',
		'EUPL-1.1',
		'EUPL-1.2',
		'FDK',
		'GNUPLOT',
		'IBMPL',
		'JOSL',
		'LPPL-1.3A',
		'LPPL-1.2',
		'LUCENT102',
		'MS-PL',
		'MS-RL',
		'MPL',
		'NOSL',
		'NPL',
		'NOKIA',
		'OLDOPENLDAP',
		'OSL',
		'OPENSSL',
		'PHORUM',
		'PHP-3.01',
		'PYTHONOLD',
		'QPL',
		'RPSL',
		'SISSL',
		'SPL',
		'XINETD',
		'YAHOO',
		'ZEND',
		'ZIMBRA',
		'ZOPE',
		'ALADDIN',
		'ANTI-996',
		'APSL1',
		'ARTISTICLICENSE',
		'ATTPUBLICLICENSE',
		'CPOL',
		'COMCLAUSE',
		'DOR',
		'ECOS11',
		'FIRSTDONOHARM',
		'GPL-PA',
		'HESSLA',
		'JAHIA',
		'JSON',
		'KSH93',
		'LHA',
		'MS-SS',
		'NASA',
		'OCULUSRIFTSDK',
		'OPENPUBLICL',
		'PPL',
		'PPL3A',
		'PINE',
		'PLAN9',
		'RPL',
		'SCILAB',
		'SCRATCH',
		'SML',
		'SQUEAK',
		'SUNCOMMUNITYSOURCELICENSE',
		'SUNSOLARISSOURCECODE',
		'WATCOM',
		'SYSTEMC-3.0',
		'TRUECRYPT-3.0',
		'UTAHPUBLICLICENSE',
		'YAST',
	);
	if ( in_array($lic, $arr) ) {
		return true;
	} else {
		return false;
	}
}

function license_is_not_for_software($lic) {
	// https://www.gnu.org/licenses/license-list.html#GPLCompatibleLicenses
	$lic = strtoupper($lic); // uppercase
	$lic = str_replace(' ', '', $lic); // remove spaces
	$lic = str_replace('#', '', $lic); // remove hashtag
	
	$arr = array(
		'FDL',
		'FREEBSDDL',
		'ACDL',
		'OPENPUBLICATIONL',
		'OPENCONTENTL',
		'CC-BY-NC',
		'CC-BY-ND',
		'GPLOTHER',
		'FDLOTHER',
		'CC0',
		'CCBY',
		'CCBYSA',
		'DSL',
		'FREEART',
		'ODBL',
		'GPLFONTS',
		'ARPHIC',
		'ECFONTS',
		'IPAFONT',
		'SILOFL',
		'GNUVERBATIM',
		'CCBYND',
	);
	if ( in_array($lic, $arr) ) {
		return true;
	} else {
		return false;
	}
}

function get_current_versions() {
	$a = checkplug_get_file_contents('https://api.wordpress.org/core/version-check/1.7/?version=100.100');
	$a = json_decode($a);
	$arr = array(
		'wp_dev'     => $a->offers[0]->current,
		'wp_current' => $a->offers[1]->current,
		'php'        => $a->offers[1]->php_version,
		'mysql'      => $a->offers[1]->mysql_version,
		);
	return $arr;
}

/*
TODO
Check https://meta.trac.wordpress.org/ticket/3936
Plugin Name is required!!!
*/

/*
For plugin main file: https://core.trac.wordpress.org/browser/trunk/src/wp-admin/includes/plugin.php
Plugin header: https://developer.wordpress.org/plugins/plugin-basics/header-requirements/#header-fields
Button styles: https://fdossena.com/?p=html5cool/buttons/i.frag
Test versions in https://docs.google.com/spreadsheets/d/e/2PACX-1vRs_2lCNMdrFT460rmbkvg0VHEHOLusjx6N8EEtVM7ZqpEk3BGctnG_y2kJywknSjGj4ur6G6Hrz8MP/pubhtml?gid=1773978482&single=true
Major minor wp vs php: https://make.wordpress.org/core/handbook/about/release-cycle/version-numbering/ vs https://www.php.net/manual/en/about.phpversions.php
*/

/*
  Evaluate if it's faster to get_meta_tags from the plugins page https://wordpress.org/plugins/bbpress,
  and get the post number from the shortlink url (?p=213)
  After that, get the JSON from https://wordpress.org/plugins/wp-json/wp/v2/plugin/213

*/

/*
  Add cache
  
  if(file_exists("wp-cache/" . $plugin_slug . ".cached")
                && filemtime("wp-cache/" . $plugin_slug . ".cached") > time() - 3600)
        {
                $plugin_data = file_get_contents("wp-cache/" . $plugin_slug . ".cached");
        } else {
                $plugin_data = file_get_contents("https://translate.wordpress.org/projects/wp-plugins/$plugin_slug/contributors");
                file_put_contents("wp-cache/" . $plugin_slug . ".cached", $plugin_data);
        }
		
*/

/*
 Readme files:
 https://developer.wordpress.org/plugins/wordpress-org/how-your-readme-txt-works/
 https://wordpress.org/plugins/developers/#readme => https://wordpress.org/plugins/readme.txt
 https://github.com/markjaquith/WordPress-Plugin-Readme-Parser/blob/master/parse-readme.php
 */
 

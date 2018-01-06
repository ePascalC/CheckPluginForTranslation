# CheckPluginForTranslation
Check your plugin on wordpress.org making sure it's ready for translation.

I came up with this script and run it on http://wp-info.org/tools/checkplugin.php to help myself in quickly checking why plugin authors say they have issues with:
- A language pack is not created
- My plugin is not ready for translation
- I have only dev, no stable
- Etc

Examples on https://github.com/ePascalC/CheckPluginForTranslation/blob/master/v0.2.5%20examples.pdf

Checks that will be performed:
* Plugin slug and base folder are reachable
* Readme in /trunk or /tags
  * Find the 'Required at least' and 'Stable Tag'
  * Make sure 'Tested up to' is not over 'Required at least'
* Versions under /tags
* Find the main php file
  * Find Text Domain
  * _function load_plugin_textdomain (if needed) (still to be done)_
* Existing language packs
* _Translation status (still to be done)_
* Translation editors - PTE
* Revision log
* _Translation warning (as in #polyglots-warnings on slack) (still to be done)_
* _Language pack status (as in #meta-language-packs on slack) (still to be done)_
* _Waiting strings (still to be done)_
* List with useful links
* Summary table

All help is appreaciated!

Pascal.

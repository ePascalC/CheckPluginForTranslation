# CheckPluginForTranslation
Check your plugin on wordpress.org making sure it's ready for translation.

I came up with this script and run it on http://wp-info.org/tools/checkplugin.php to help myself in quickly checking why plugin authors say they have issues with:
- A language pack is not created
- My plugin is not ready for translation
- I have only dev, no stable
- Etc

Checks that will be performed:
* Plugin slug and base folder are reachable
* Readme.txt in /trunk
  * Find the 'Required at least' and 'Stable Tag'
  * _Make sure 'Tested up to' is not over 'Required at least' (still to be done)_
* Versions under /tags
* Find the main php file
  * Find Text Domain
  * _function load_plugin_textdomain (if needed) (still to be done)_
* Existing language packs
* _Translation status (still to be done)_
* _Translation editors - PTE (still to be done)_
* Revision log
* _Translation warning (as in #polyglots-warnings on slack) (still to be done)_
* _Language pack status (as in #meta-language-packs on slack) (still to be done)_
* _Waiting strings (still to be done)_
* _List with useful links (still to be done)_
* Summary table

All help is appreaciated!

Pascal.

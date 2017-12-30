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
* Versions under /tags
* Find the main php file
  * Find Text Domain
  * function load_text_domain (if needed)
* Existing language packs
* Translation status
* Revision log
* Translation warning (as in #polyglots-warnings on slack)
* Language pack status (as in #meta-language-packs on slack)

All help is appreaciated!

Pascal.

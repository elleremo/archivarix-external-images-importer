=== Archivarix External Images Importer ===
Contributors: archivarix
Donate link: https://wordpressfoundation.org/donate/
Tags: external, images, importer, wordpress, archive, archivarix
Requires at least: 5.0
Tested up to: 5.2
Requires PHP: 5.6
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Import external images in posts and pages from its external sources or Web Archive if original sources are not available anymore.

== Description ==
Archivarix External Images Importer will scan your posts and pages for external urls in src/srcset attribute for all img tags. Based on a configured settings it will

* Download images from their external positions
* Download images by looking for it in Internet Archive
* Try to download from the original position, in case of a failure tries Internet Archive
* Try to download from Internet Archive, in case of a failure tries its original external url.

You can choose what to do with images that could not be retrieved from external sources.

For further information and instructions please see  [Archivarix Plugins](https://en.archivarix.com/wp-plugins/)

== Installation ==
The quickest method for installing the importer is:

1. Visit Tools -> Import in the WordPress dashboard
1. Click on the WordPress link in the list of importers
1. Click "Install Now"
1. Finally click "Activate Plugin & Run Importer"

If you would prefer to do things manually then follow these instructions:

1. Upload the `archivarix-external-images-importer` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to the Settings -> Archivarix External Images Importer to configure plugins behaviour

== Changelog ==
= 1.0.0 =
* Initial release

== Frequently Asked Questions ==
= Help! I'm getting out of memory errors or a blank screen. =
If your exported file is very large, the import script may run into your host's configured memory limit for PHP.

A message like "Fatal error: Allowed memory size of 8388608 bytes exhausted" indicates that the script can't successfully import your XML file under the current PHP memory limit. If you have access to the php.ini file, you can manually increase the limit; if you do not (your WordPress installation is hosted on a shared server, for instance), you might have to break your exported XML file into several smaller pieces and run the import script one at a time.

For those with shared hosting, the best alternative may be to consult hosting support to determine the safest approach for running the import. A host may be willing to temporarily lift the memory limit and/or run the process directly from their end.
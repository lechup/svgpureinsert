====== svg pureinsert plugin ======

---- plugin ----
description: Inserts a non PNG or otherwise modified SVG file, just its clean version. Plugin should work - mostly tested on SVGs from http://www.openclipart.org/ and Inkscape output. Any comments - place for them below.

author     : Leszek Piątek
email      : lpiatek@gmail.com
type       : syntax
lastupdate : 2009-01-25
compatible : DokuWiki-2007-06-26b
depends    : 
conflicts  : 
similar    : 
tags       : media, images, diagram, svg
downloadurl: https://github.com/lechup/svgpureinsert/blob/master/svgpureinsert.zip?raw=true
----

====== Download ======

Here you can download plugin (supports remote installation): [[https://github.com/lechup/svgpureinsert/blob/master/svgpureinsert.zip?raw=true|github]] or
[[http://forum.dokuwiki.org/post/30455|in this post]] at the DokuWiki-forum.

====== Installation  ======
Do normal remote installation (ctrl+c -> ctrl+v upper link in admin/manage plugins section of your DokuWiki)

Or manually after downloading plugin extract it and copy all the files to your DokuWiki /lib/plugins/ folder.

To enable the "media" support edit or create conf/mime.local.conf and add at the bottom entry:
SVG image/svg+xml

If you want to disable cache for SVG files edit lib/plugins/svgpureInsert/svgpureInsert.php:
  - define('SVG_CACHE', true); to define('SVG_CACHE', false);
  - delete folder: lib/plugins/svgpureInsert/cache
Caching is enabled by default:
  - folder lib/plugins/svgpureInsert/cache should have permissions to write


====== Overview ======
My intention was to create plugin which enables using built-in SVG images visualization engine in Firefox, Opera browsers. I just didn't want to have PNG's or JPG's - I love vector graphics ;)

== Features: ==
  * image like syntax, so it supports uploading and inserting SVG as a media files
  * supports DokuWiki align syntax
  * **supports resizing !!** - and this is what SVG is about :)
  * caching enabled by default
  * XHTML 1.0 valid

== Known bugs/disadvantages: ==
  * not using $conf['savedir']
  * embedded as a iframe - no wiki url syntax support
  * tooltip caption is not working (syntax is available) but Firefox is not displaying it
  * SVG is supported by Firefox and Opera, NO IE (5.5, 6.0) support (IE 7.0 8.0 not tested)
    * There is a browser plugin for IE 6 to display SVG. To be tested with this plugin.

====== Got improved code?  ========
Pull request to [[https://github.com/lechup/svgpureinsert]]!


====== How does it work? ======
  - I've created syntax plugin which search for { {file_or_url.svg|description} } its getSort is lower than media files, so it catch up everything nicely.
  - Next step is search the base size of SVG
  - When we got base size, we can count the size we want to display, and insert iframe tag to DokuWiki renderer
  - We point iframe to svgpureInsert.php?url={url}&width={w}&height={h} which is used to change, and insert some SVG tags needed to resize the file
  - After that when - CACHING is enabled in lib/plugins/svg/svg.php (default) - we save image in cache and display it as a svg/image+XML mime, when there is no CACHE we just display resized image and download it each time we refresh the page (despite the DokuWiki cache)



====== Version History ======

== svgpureInsert 1.05 ==
Thanks to **Konstantin** for sharing better code. Open Source rox!
  * fixed issue appeared when you use dokuwiki over https (SSL)
  * fixed issue with output of dia's SVGs

== svgpureInsert 1.04 ==
  * changed name to svgpureInsert not to collide with svg plugin
  * some other stuff done by: **Goulven Guillard**, thanks for sharing better code!:
      * corrected the remote installation
      * corrected preg_match regular expressions which didn't work fine
      * set $_GET results into variables

== svg_pureInsert 1.03 ==
  * some issues with "mm" and "px" size of svg
  * other issues with resizing
  * now supports remote installation

== svg_pureInsert 1.02 ==
  * svg_pureInstert do not disturb other media files (there was issue while using SVG and JPG images on the same page)
  * if no size of SVG file is given (e.g. {{egzm.svg|} }), we use default (written into SVG) image size - "You want to set not numeric width or height..." error

== svg_pureInsert 1.01 ==
  * added namespace support, just forgot to test it before now: { {mcol:mcol_worm.svg?100 } } will point to proper directory (mcol/mcol_worm.svg)

====== Comments ======

I tried your plugin and found what follows:
  - the hardcoded ''define('SVG_ROOT_PATH', '../../../');'' fails on my install (Centos5.6). The value from ''$conf['savedir']'' (stripped of the final ''data/'') should be used instead
  - Even fixing the above problem, images are clipped to some arbitrary size (I used an inkscape-generated A4 size image): the embedded width and height data appear not be honored.

Cheers,
Alessandro Forghieri (alf at orion dot it ).

----

**Sadly, the plugin can not recognize chinese namespace, and can not find the svg file...**

----

Great plugin! SVGs from other sites worked great, but I couldn't get it to display an internal SVG. It turns out that there was a extra '/' in the path. Here is a patch that fixes this:

<code diff svgpureInsertpath.patch>
diff -u svgpureInsert/syntax.php svgpureInsert_new/syntax.php
--- svgpureInsert/syntax.php	2009-03-27 15:21:12.000000000 -0700
+++ svgpureInsert_new/syntax.php	2010-06-23 10:16:30.000000000 -0700
@@ -59,7 +59,7 @@
 					//src

 					$GLOBALS['data_svgpureInsert']['src'] = $p_match[2];

 					if(strpos($GLOBALS['data_svgpureInsert']['src'], "http://")===false && strpos($GLOBALS['data_svgpureInsert']['src'], "ftp://")===false)

-						$GLOBALS['data_svgpureInsert']['src'] = 'data/media/'.str_replace(':', '/', $GLOBALS['data_svgpureInsert']['src']);

+						$GLOBALS['data_svgpureInsert']['src'] = 'data/media'.str_replace(':', '/', $GLOBALS['data_svgpureInsert']['src']);

 					$GLOBALS['data_svgpureInsert']['src'] = urlencode($GLOBALS['data_svgpureInsert']['src'].'.svg');	

 					//

 				}
</code>
--Andy

----
besides the bug mentioned above by Andy, the whole thing does not work if the data path ("savedir") is changed in the dokuwiki config. That variable should be used instead of the hardcoded path.

--Sebastian

====== svg pureinsert plugin ======

---- plugin ----
description: Inserts a non png or otherwise modified svg file, just its clean version.
author     : Leszek Piatek
email      : lpiatek@gmail.com
type       : syntax
lastupdate : 2008-03-21
compatible : dokuwiki-2007-06-26b
depends    :
conflicts  :
similar    :
tags       : svg insert not
----


====== Installation ======
After downloading plugin extract it and copy all the files to your dokuwiki root folder.

To enable the "media" support edit conf/mime.local.conf and add at the bottom entry:
svg     image/svg+xml

If you want to disable cache for SVG files edit lib/plugins/svgpureInsert/svgpureInsert.php:
  - define('SVG_CACHE', true); to define('SVG_CACHE', false);
  - delete folder: lib/plugins/svgpureInsert/cache
Caching is enabled by default:
  - folder lib/plugins/svgpureInsert/cache should have permissions to write


====== Overview ======
My intention was to create plugin which enables using built-in SVG images visualization engine in Firefox, Opera and Chrome. I just didn't want to have png's or jpg's - I love vector graphics ;)

== Benefits: ==
  * image like syntax, so it supports uploading and inserting svg as a media files
  * supports dokuwiki align syntax
  * **supports resizing !!** - and this is what SVG is about :)
  * caching enabled by default
  * xhtml 1.0 valid

== Drawbacks ==
  * embedded as a iframe - no links available
  * tooltip caption is not working (syntax is available) but Firefox is not displaying it
  * SVG is supported by FireFox and Opera, NO IE support (haven't checked on IE 7.0)


====== How does it work? ======
  - I've created syntax plugin which search for { {file_or_url.svg|description} } it's getSort is lower than media files, so it catch up everything nicely.
  - Next step is search the base size of SVG
  - When we got base size, we can count the size we want to display, and insert iframe tag to dokuwiki renderer
  - We point it to svgpureInsert.php?url={url}&width={w}&height={h} which is used to change, and insert some SVG tags needed to resize the file
  - After that when - CACHING is enabled in svgpureInsert.php - we save image in cache and display it as a svg/image+xml mime, when there is no CACHE (default) we just display resized image

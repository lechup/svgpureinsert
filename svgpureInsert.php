<?php
/*

 svgpureInsert.php, read and cache svg file, scaling to proper dimension, part of svgpureInsert plugin to dokuWiki
 @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 @author     Leszek Piatek <lpiatek@gmail.com>

DESCRIPTION:
Read svg $_GET['url'] file and display it as a  svg/image+xml mime type, if needed we can cache the result to speed up things a bit.

$_GET MODIFIERS:
url: url of svg to read
width: specified width of svg file
height: specified height of svg file

USAGE:
just point <img scr="svgpureInsert.php?url=encoded_url_or_path&width=200" alt="" />
if only one argument of dimension (width or height) is specified,
we scale image to have specified dimension (second dimension is calculated propotionally)
we can set SVG_CACHE,  and SVG_CACHE_DIR if we want to create cache locally (faster loading, no problems with dead links)

*/

// *********** CONFIGURATION: ************* //
define('SVG_ROOT_PATH', '../../../');
define('SVG_CACHE', true); //if you want tu turn on caching set to true
define('SVG_CACHE_DIR', SVG_ROOT_PATH . 'lib/plugins/svgpureInsert/cache/'); //relatively to svgpureInsert.php, need to be writable by php (set chmod 755)

// *********** CODE, be sure what you are doing while editing stuff below ************* //

//URL check and other stuff check
if(isset($_GET['url']))
    $URL = $_GET['url'];
else {
    echo "There is no file to draw!!!";
    exit;
}

$exp = explode('.', $URL);
$exp = end($exp);

if($exp != 'svg') {
    echo('You want to read file with non svg extension...');
    exit;
}

if(isset($_GET['width']))
    $width = $_GET['width'];
else
    $width = 0;

if(isset($_GET['height']))
    $height = $_GET['height'];
else
    $height = 0;

if(!is_numeric($width) || !is_numeric($height)) {
    echo('You want to set non numeric width or height...');
    exit;
}
//


//did we cache anything?
$cache_file = SVG_CACHE_DIR . md5($URL . '?' . $width . 'x' . $height) . '.svg';
if(!file_exists($cache_file) or !SVG_CACHE) {
    $URL = urldecode($URL);
    if(strpos($URL, "http://") === false && strpos($URL, "ftp://") === false)
        $URL = SVG_ROOT_PATH . $URL;

    $fp = @fopen($URL, 'r');

    if($fp) {
        //PRINT HEADER
        header('Content-type: image/svg+xml');

        //GET CONTENT
        $buff = '';
        while(!feof($fp)) {
            $buff .= fread($fp, 4096);
        }
        fclose($fp);

        //MODIFY TO PROPER WIDTH AND HEIGHT
        if($width > 0 || $height > 0) {
            #find main tag
            preg_match('#<svg(.*?)>#ism', $buff, $buff_match);

            $buff_svg = $buff_match[0];

            #remove width attribute
            if(preg_match('#[\s]width=[\"]([0-9]++)(\.[0-9]++)??(.*)??[\"][\s]*#iU', $buff_svg, $match)) {
                if(!$match[2])
                    $real_width = $match[1];
                else
                    $real_width = $match[1] + 1;
                #change mm to pixels
                if($match[3] === 'mm')
                    $real_width = round($real_width * 3);

                $buff_svg = str_replace($match[0], '', $buff_svg);
            }

            #remove height attribute
            if(preg_match('#[\s]height=[\"]([0-9]++)(\.[0-9]++)??([^\"]*)??[\"][\s]*#iU', $buff_svg, $match)) {
                if(!$match[2])
                    $real_height = $match[1];
                else
                    $real_height = $match[1] + 1;
                #change mm to pixels
                if($match[3] === 'mm')
                    $real_height = round($real_height * 3);

                $buff_svg = str_replace($match[0], '', $buff_svg);
            }

            #remove viewBox attribute
            if(preg_match("#viewBox\=\"([^\"]*)\"#i", $buff_svg, $match)) {
                if($match[1]) {
                    if(strpos($match[1], ',') !== false)
                        $exp = explode(",", $match[1]);
                    else
                        $exp = explode(" ", $match[1]);

                    $real_width  = $exp[2];
                    $real_height = $exp[3];
                }
                $buff_svg = str_replace($match[0], '', $buff_svg);
            }

            #remove preserveAspectRatio=".*"
            if(preg_match("#preserveAspectRatio\=[\"]?.*[\"]?#iU", $buff, $match))
                $buff = str_replace($match[0], '', $buff);

            if($width == 0)
                $width = $real_width;
            if($height == 0)
                $height = $real_height;

            $buff_svg = str_replace("<svg", '<svg preserveAspectRatio="none" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $real_width . ' ' . $real_height . '"', $buff_svg);

            $buff = str_replace($buff_match[0], $buff_svg, $buff);

            exit($buff);
        }

        //SAVE THE BUFFER TO CACHE
        if(SVG_CACHE) {
            $fp = fopen($cache_file, "w");
            fwrite($fp, $buff);
            fclose($fp);
        }

        //PRINT BUFFER    
        echo($buff);

    } else {
        echo('Sorry, but <b>' . $URL . '</b> doesn\'t exist...');
    }
} else {
    //yes we got the cache, so print it immediately
    header('Content-type: image/svg+xml');

    $fp   = fopen($cache_file, "r");
    $buff = fread($fp, filesize($cache_file));
    fclose($fp);

    //PRINT CACHE
    echo($buff);
}
//
?>

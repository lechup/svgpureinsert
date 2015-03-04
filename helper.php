<?php
/**
 * Plugin svgpureInsert: Inserts a non png or other modified svg file, just its pure version
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <gohr@cosmocode.de>
 */

if(!defined('DOKU_INC')) exit;
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

class helper_plugin_svgpureinsert extends DokuWiki_Plugin {

    /**
     * Gets a local scalable copy of the SVG with its dimensions
     *
     * @param $id
     * @param int $cachetime
     * @return bool|array either array($file, $width, $height) or false if no cache available
     */
    public function getAdjustedSVG($id, $cachetime = -1) {
        $info = $this->getInfo();
        $cachefile = getCacheName($id . $info['date'], '.svg');
        $cachedate = @filemtime($cachefile);
        if($cachedate && $cachetime < (time() - $cachedate)) {
            list($width, $height) = $this->readSVGsize($cachefile);
            return array($cachefile, $width, $height);
        }

        // still here, create a new cache file
        if(preg_match('/^https?:\/\//i', $id)) {
            io_download($id, $cachefile); #FIXME make max size configurable
        } else {
            @copy(mediaFN($id), $cachefile);
        }
        clearstatcache(false, $cachefile);

        // adjust the size in the cache file
        if(file_exists($cachefile)){
            list($width, $height) = $this->readSVGsize($cachefile, true);
            return array($cachefile, $width, $height);
        }

        return false;
    }


    /**
     * Parse te given XML attributes into an array
     *
     * @author troelskn
     * @link http://stackoverflow.com/a/1083821/172068
     * @param $input
     * @return array
     */
    public function parseAttributes($input) {
        $dom = new DomDocument();
        $dom->loadHtml("<html $input />");
        $attributes = array();
        foreach($dom->documentElement->attributes as $name => $attr) {
            $attributes[$name] = $attr->value;
        }
        return $attributes;
    }

    /**
     * Calculates pixel size for any given SVG size
     *
     * @param $value
     * @return int
     */
    public function convertToPixel($value) {
        if(!preg_match('/^(\d+?(\.\d*)?)(in|em|ex|px|pt|pc|cm|mm)?$/', $value, $m)) return 0;

        $digit = (double) $m[1];
        $unit  = (string) $m[3];

        $dpi         = 72;
        $conversions = array(
            'in' => $dpi,
            'em' => 16,
            'ex' => 12,
            'px' => 1,
            'pt' => $dpi / 72, # 1/27 of an inch
            'pc' => $dpi / 6, # 1/6 of an inch
            'cm' => $dpi / 2.54, # inch to cm
            'mm' => $dpi / (2.54 * 10), # inch to cm,
        );

        if(isset($conversions[$unit])) {
            $digit = $digit * (float) $conversions[$unit];
        }

        return ceil($digit);
    }

    /**
     * Read the Size of an SVG from its contents
     *
     * @param string $file local SVG file (or part of it)
     * @param bool $fix should the file's size attributes be fixed as well?
     * @return array
     */
    public function readSVGsize($file, $fix = false) {
        $default  = array(100, 100);

        $data = io_readFile($file, false);
        if(!$data) return $default;
        if(!preg_match('/<svg([^>]*)>/s', $data, $m)) return $default;
        $attributes = $this->parseAttributes($m[1]);

        $width  = $attributes['width'];
        $height = $attributes['height'];

        if(substr($width, -1, 1) == '%' || substr($height, -1, 1) == '%') {
            // dimensions are in percent, try viewBox instead
            list(, , $width, $height) = explode(' ', $attributes['viewbox']);
        }

        // fix units
        $width  = $this->convertToPixel($width);
        $height = $this->convertToPixel($height);

        // if calculation failed use default
        if(!$width) $width = $default[0];
        if(!$height) $height = $default[0];

        // fix the SVG to be autoscaling
        if($fix) {
            if(isset($attributes['viewbox'])) unset($attributes['viewbox']);
            if(isset($attributes['preserveaspectratio'])) unset($attributes['preserveaspectratio']);

            $attributes['width']   = '100%';
            $attributes['height']  = '100%';
            $attributes['viewBox'] = "0 0 $width $height";
            $attributes['preserveAspectRatio'] = 'xMidYMid slice';

            $svg  = '<svg ' . buildAttributes($attributes) . '>';
            $data = preg_replace('/<svg([^>]*)>/s', $svg, $data, 1);
            io_saveFile($file, $data);
        }

        return array(
            $width,
            $height
        );
    }

}
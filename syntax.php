<?php
/**
 * Plugin svgpureInsert: Inserts a non png or other modified svg file, just its pure version
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Leszek Piatek <lpiatek@gmail.com>
 */

if(!defined('DOKU_INC'))
    exit;

if(!defined('DOKU_PLUGIN'))
    define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

require_once(DOKU_PLUGIN . 'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_svgpureinsert extends DokuWiki_Syntax_Plugin {

    function getType() {
        return 'substition';
    }

    /**
     * Returns a lower sort than image syntax
     *
     * @return int 319
     */
    function getSort() {
        return 319;
    }

    /**
     * Register pattern
     *
     * Just like image syntax but grab any .svg
     *
     * @param string $mode
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{[^\}]+?(?:\.svg)[^\}]*?\}\}', $mode, 'plugin_svgpureinsert');
    }

    /**
     * Parse parameters from syntax
     *
     * @param string $match
     * @param int $state
     * @param int $pos
     * @param Doku_Handler $handler
     * @return array|bool
     */
    function handle($match, $state, $pos, Doku_Handler $handler) {
        // default data
        $data = array(
            'id'     => '',
            'title'  => '',
            'align'  => '',
            'width'  => 0,
            'height' => 0,
            'cache'  => 'cache'
        );

        $match = substr($match, 2, -2);
        list($id, $title) = explode('|', $match, 2);

        // alignment
        if(substr($id, 0, 1) == ' ') {
            if(substr($id, -1, 1) == ' ') {
                $data['align'] = 'center';
            } else {
                $data['align'] = 'right';
            }
        } elseif(substr($id, -1, 1) == ' ') {
            $data['align'] = 'left';
        }

        list($id, $params) = explode('?', $id, 2);

        // id and title
        $data['id'] = ($id);
        $data['title'] = trim($title);

        // size
        if(preg_match('/(\d+)(x(\d+))?/', $params, $m)) {
            $data['width']  = (int) $m[1];
            $data['height'] = (int) $m[3];
        }

        return $data;
    }

    function render($format, Doku_Renderer $renderer, $data) {
        if($format == 'xhtml' && $data) {
            $path = ($this->is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . str_replace('doku.php', '', $_SERVER['SCRIPT_NAME']) . 'lib/plugins/svgpureInsert/';
            $renderer->doc .= '<iframe src="' . $path . 'svgpureInsert.php?url=' . $data['src'] . '&width=' . $data['width'] . '&height=' . $data['height'] . '" ' . $data['align'] . ' width="' . $data['width'] . '" height="' . $data['height'] . '" title="' . $data['caption'] . '" frameborder="0"></iframe>';
            return true;
        }
        return false;
    }

    //to support older dokuwikis just repeat function
    function is_ssl() {
        if(isset($_SERVER['HTTPS'])) {
            if('on' == strtolower($_SERVER['HTTPS']))
                return true;
            if('1' == $_SERVER['HTTPS'])
                return true;
        } elseif(isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
            return true;
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
    public static function parseAttributes($input) {
        $dom = new DomDocument();
        $dom->loadHtml("<html $input />");
        $attributes = array();
        foreach ($dom->documentElement->attributes as $name => $attr) {
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
    public static function convertToPixel($value) {
        if(!preg_match('/^(\d+?(\.\d*)?)(in|em|ex|px|pt|pc|cm|mm)?$/', $value, $m)) return 0;

        $digit = (double) $m[1];
        $unit  = (string) $m[3];

        $dpi = 72;
        $conversions = array(
            'in' => $dpi,
            'em' => 16,
            'ex' => 12,
            'px' => 1,
            'pt' => $dpi/72, # 1/27 of an inch
            'pc' => $dpi/6, # 1/6 of an inch
            'cm' => $dpi/2.54, # inch to cm
            'mm' => $dpi/(2.54*10), # inch to cm,
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
     * @return array
     */
    public static function readSVGsize($file) {
        $default = array(100, 100);

        $file = io_readFile($file, false);
        if(!$file) return $default;
        if(!preg_match('/<svg([^>]*)>/s', $file, $m)) return $default;
        $attributes = self::parseAttributes($m[1]);

        $width = $attributes['width'];
        $height = $attributes['height'];

        if(substr($width,-1,1) == '%' || substr($height,-1,1) == '%') {
            // dimensions are in percent, try viewBox instead
            list(,,$width, $height) = explode(' ', $attributes['viewbox']);
        }

        // fix units
        $width  = self::convertToPixel($width);
        $height = self::convertToPixel($height);

        // if calculation failed use default
        if(!$width) $width = $default[0];
        if(!$height) $height = $default[0];

        return array(
            $width,
            $height
        );
    }
}

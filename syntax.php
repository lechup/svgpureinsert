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

    function readSVGsize($file) {
        $file = urldecode($file);

        $exp = explode('.', $file);
        $exp = end($exp);

        if($exp != 'svg') {
            return array(
                1,
                1
            );
        }

        $fp = @fopen($file, 'r');
        if($fp) {
            $buff = '';
            while(!feof($fp)) {
                $buff .= fread($fp, 128);
                if(strpos($buff, "width=") !== false && strpos($buff, "height=") !== false) {
                    $buff .= fread($fp, 128);
                    break;
                }
            }
            fclose($fp);
            preg_match("#[\s]width=[\"]([0-9]++)(\.[0-9]++)??(.*)??[\"]#msiU", $buff, $match);

            #if size with dot, just round whole image 1px...
            if(!$match[2])
                $width = $match[1];
            else
                $width = $match[1] + 1;

            #change mm to pixels
            if($match[3] == 'mm')
                $width = round($width * 3);

            preg_match("#[\s]height=[\"]([0-9]++)(\.[0-9]++)??(.*)??[\"]#msiU", $buff, $match);

            if(!$match[2])
                $height = $match[1];
            else
                $height = $match[1] + 1;
            #change mm to pixels
            if($match[3] == 'mm')
                $height = round($height * 3);
        } else
            return array(
                1,
                1
            );
        return array(
            $width,
            $height
        );
    }
}

<?php
/**
 * Plugin svgpureInsert: Inserts a non png or other modified svg file, just its pure version
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Leszek Piatek <lpiatek@gmail.com>
 */

if(!defined('DOKU_INC')) exit;
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');


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
        $data['id'] = trim($id);
        $data['title'] = trim($title);

        // size
        if(preg_match('/(\d+)(x(\d+))?/', $params, $m)) {
            $data['width']  = (int) $m[1];
            $data['height'] = (int) $m[3];
        }

        // read missing size values from the file itself
        if(!$data['width'] || !$data['height']) {
            /** @var helper_plugin_svgpureinsert $hlp */
            $hlp = plugin_load('helper', 'svgpureinsert');
            $res = $hlp->getAdjustedSVG($data['id']);
            if($res) {
                list(, $w, $h) = $res;

                if(!$data['width']) {
                    $data['width']  = $w;
                    $data['height'] = $h;
                } else {
                    $data['height'] = ceil($data['width'] * $h / $w);
                }
            }
        }

        return $data;
    }

    function render($format, Doku_Renderer $renderer, $data) {
        if($format != 'xhtml') return false;

        $attr = array(
            'src' => ml($data['id'], array('w'=>$data['width'], 'h'=>$data['height']), true, '&'),
            'width' => $data['width'],
            'height' => $data['height'],
            'class' => 'svgpureinsert media'.$data['align'],
            'frameborder' => 0,
            'title' => $data['title']
        );

        $renderer->doc .= '<iframe '.buildAttributes($attr).'></iframe>';
        return true;
    }
}

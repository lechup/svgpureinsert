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
class syntax_plugin_svgpureInsert extends DokuWiki_Syntax_Plugin {

    function getType() {
        return 'substition';
    }

    function getSort() {
        return 319;
    }

    function connectTo($mode) {
        $this->Lexer->addEntryPattern('\{\{[^\}\{]*?\.svg(?=.*?\}\})', $mode, 'plugin_svgpureInsert');
        $this->Lexer->addPattern('[^\}\{]+', 'plugin_svgpureInsert');
        $this->Lexer->addExitPattern('.*?\}\}', 'plugin_svgpureInsert');
    }

    function handle($match, $state, $pos, &$handler) {
        switch($state) {
            case DOKU_LEXER_ENTER:
                $GLOBALS['data_svgpureInsert'] = array();
                if(preg_match("#([ ]+)?([A-Za-z0-9\-_\*&\^%\?\$\#\.@\!:/]+)\.svg#", $match, $p_match)) {
                    //align
                    if($p_match[1])
                        $GLOBALS['data_svgpureInsert']['align'] = ' align="right"';
                    else
                        $GLOBALS['data_svgpureInsert']['align'] = '';
                    //

                    //src
                    $GLOBALS['data_svgpureInsert']['src'] = $p_match[2];
                    if(strpos($GLOBALS['data_svgpureInsert']['src'], "http://") === false && strpos($GLOBALS['data_svgpureInsert']['src'], "ftp://") === false)
                        $GLOBALS['data_svgpureInsert']['src'] = 'data/media' . str_replace(':', '/', $GLOBALS['data_svgpureInsert']['src']);
                    $GLOBALS['data_svgpureInsert']['src'] = urlencode($GLOBALS['data_svgpureInsert']['src'] . '.svg');
                    //
                }
                break;

            case DOKU_LEXER_MATCHED:
                if(preg_match("#(\?[0-9]+)?(x[0-9]+)?([ ]+)?(\|.*)?#", $match, $p_match)) {
                    //align
                    if($GLOBALS['data_svgpureInsert']['align'] && $p_match[3])
                        $GLOBALS['data_svgpureInsert']['align'] = ' class="mediacenter"';
                    elseif($p_match[3])
                        $GLOBALS['data_svgpureInsert']['align'] = ' align="left"';
                    //

                    //caption
                    if($p_match[4])
                        $GLOBALS['data_svgpureInsert']['caption'] = trim(substr($p_match[4], 1));
                    //

                    //width
                    if($p_match[1])
                        $GLOBALS['data_svgpureInsert']['width'] = trim(substr($p_match[1], 1));
                    //

                    //height
                    if($p_match[2])
                        $GLOBALS['data_svgpureInsert']['height'] = trim(substr($p_match[2], 1));
                    //

                    //get proper image size when only width given
                    if($GLOBALS['data_svgpureInsert']['width'] && !$GLOBALS['data_svgpureInsert']['height']) {
                        $dimension                               = $this->readSVGsize($GLOBALS['data_svgpureInsert']['src']);
                        $prop                                    = $GLOBALS['data_svgpureInsert']['width'] / $dimension[0];
                        $GLOBALS['data_svgpureInsert']['height'] = round($dimension[1] * $prop);
                    } elseif(!$GLOBALS['data_svgpureInsert']['width'] && !$GLOBALS['data_svgpureInsert']['height']) {
                        $dimension                               = $this->readSVGsize($GLOBALS['data_svgpureInsert']['src']);
                        $GLOBALS['data_svgpureInsert']['height'] = $dimension[1];
                        $GLOBALS['data_svgpureInsert']['width']  = $dimension[0];
                    }
                    //
                }
                break;

            case DOKU_LEXER_EXIT:
                if(!$GLOBALS['data_svgpureInsert']['width'] or !$GLOBALS['data_svgpureInsert']['height']) {
                    $dimension                               = $this->readSVGsize($GLOBALS['data_svgpureInsert']['src']);
                    $GLOBALS['data_svgpureInsert']['height'] = $dimension[1];
                    $GLOBALS['data_svgpureInsert']['width']  = $dimension[0];
                }
                return $GLOBALS['data_svgpureInsert'];
            default:
                return 0;
        }
    }

    function render($mode, &$renderer, $data) {
        if($mode == 'xhtml' && $data) {
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

?>

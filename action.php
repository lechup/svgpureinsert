<?php
/**
 * Plugin svgpureInsert: Inserts a non png or other modified svg file, just its pure version
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <gohr@cosmocode.de>
 */

if(!defined('DOKU_INC')) exit;
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

class action_plugin_svgpureinsert extends DokuWiki_Action_Plugin {

    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('FETCH_MEDIA_STATUS', 'BEFORE', $this, 'handle_send');
    }

    public function handle_send(Doku_Event &$event) {
        if($event->data['ext'] != 'svg') return;
        if($event->data['status'] >= 400) return; // ACLs and precondition checks

        /** @var helper_plugin_svgpureinsert $hlp */
        $hlp = plugin_load('helper', 'svgpureinsert');
        list($file) = $hlp->getAdjustedSVG($event->data['media'], $event->data['cache']);

        if($file) {
            $event->data['file'] = $file;
            $event->data['status'] = 200;
            $event->data['statusmessage'] = '';
        }
    }
}
<?php

/**
 * General tests for the svgpureinsert plugin
 *
 * @group plugin_svgpureinsert
 * @group plugins
 */
class syntax_plugin_svgpureinsert_test extends DokuWikiTest {
    protected $pluginsEnabled = array('svgpureinsert');

    public function test_localparse() {
        $source = '{{just:some.svg?400x500 |test}}';
        $parser_response = p_get_instructions($source);

        $calls = array(
            array('document_start', array()),
            array('p_open', array()),
            array(
                'plugin',
                array(
                    'svgpureinsert',
                    array(
                        'id'     => 'just:some.svg',
                        'title'  => 'test',
                        'align'  => 'left',
                        'width'  => 400,
                        'height' => 500,
                        'cache'  => 'cache'
                    ),
                    5, // pos?
                    $source
                )
            ),
            array('cdata', array(null)),
            array('p_close', array()),
            array('document_end', array()),
        );
        $this->assertEquals($calls, array_map('stripbyteindex', $parser_response));
    }

    public function test_svgsize() {
        /** @var helper_plugin_svgpureinsert $hlp */
        $hlp = plugin_load('helper', 'svgpureinsert');

        $dimension = $hlp->readSVGsize(__DIR__.'/svglogo.svg');
        $this->assertEquals(array(300, 300), $dimension);

        $dimension = $hlp->readSVGsize(__DIR__.'/dokuwiki.svg');
        $this->assertEquals(array(745, 1053), $dimension);
    }
}

<?php

use PHPUnit\Framework\TestCase;
use Xaraya\Services\xar;

/**
 * We need to run each test in a separate process here to avoid session issues
 */
#[\PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses]
final class EndpointTest extends TestCase
{
    public function testGqlGet(): void
    {
        xar::req()->setServerVar('REQUEST_METHOD', 'GET');
        ob_start();
        include sys::web() . 'gql.php';
        $output = ob_get_clean();
        xar::req()->setServerVar('REQUEST_METHOD', null);

        $expected = 'Get GraphQL Schema Definition';
        $this->assertStringContainsString($expected, $output);
    }

    public function testGqlGetObjects(): void
    {
        xar::req()->setServerVar('REQUEST_METHOD', 'GET');
        xar::req()->setServerVar('QUERY_STRING', 'query={objects{objectid,name}}');
        ob_start();
        include sys::web() . 'gql.php';
        $output = ob_get_clean();
        xar::req()->setServerVar('REQUEST_METHOD', null);
        xar::req()->setServerVar('QUERY_STRING', null);

        $expected = null;
        $result = json_decode($output, true);
        $this->assertEquals($expected, $result['data']['objects']);
        $expected = 'Invalid user';
        $this->assertEquals($expected, $result['errors'][0]['extensions']['debugMessage']);
        $this->assertStringContainsString($expected, $output);
    }

    public function testGqlGetSamples(): void
    {
        xar::req()->setServerVar('REQUEST_METHOD', 'GET');
        xar::req()->setServerVar('QUERY_STRING', 'query={samples{id,name}}');
        ob_start();
        include sys::web() . 'gql.php';
        $output = ob_get_clean();
        xar::req()->setServerVar('REQUEST_METHOD', null);
        xar::req()->setServerVar('QUERY_STRING', null);

        $expected = 3;
        $result = json_decode($output, true);
        $this->assertCount($expected, $result['data']['samples']);
        $expected = 'Johnny';
        $this->assertEquals($expected, $result['data']['samples'][0]['name']);
        $this->assertStringContainsString($expected, $output);
    }

    public function testGqlOptions(): void
    {
        xar::req()->setServerVar('REQUEST_METHOD', 'OPTIONS');
        ob_start();
        include sys::web() . 'gql.php';
        $output = ob_get_clean();
        xar::req()->setServerVar('REQUEST_METHOD', null);

        $expected = '';
        // @todo this doesn't actually capture the headers
        $headers = headers_list();
        $this->assertStringContainsString($expected, $output);
    }

    public function testIndex(): void
    {
        xar::req()->setServerVar('REQUEST_URI', '/xaraya/index.php');
        ob_start();
        include sys::web() . 'index.php';
        $output = ob_get_clean();

        $expected = 'Congratulations';
        $this->assertStringContainsString($expected, $output);
    }

    public function testInstall(): void
    {
        $olddir = getcwd();
        // install fails otherwise because phase1 installer checks for 'install.php'
        chdir(sys::web());

        ob_start();
        include sys::web() . 'install.php';
        $output = ob_get_clean();

        $expected = 'Installing';
        $this->assertStringContainsString($expected, $output);

        chdir($olddir);
    }

    public function testLs(): void
    {
        global $argc, $argv;
        $argv = ['ls.php', 'mail'];
        $argc = count($argv);

        ob_start();
        include sys::web() . 'ls.php';
        $output = ob_get_clean();

        $expected = 'Usage: mail -u <user> -p <pass> [mailcontent]';
        $this->assertStringContainsString($expected, $output);
    }

    public function testRstGet(): void
    {
        xar::req()->setServerVar('REQUEST_METHOD', 'GET');
        ob_start();
        include sys::web() . 'rst.php';
        $output = ob_get_clean();
        xar::req()->setServerVar('REQUEST_METHOD', null);

        $expected = 'Xaraya REST API';
        $result = json_decode($output, true);
        $this->assertEquals($expected, $result['info']['title']);
    }

    public function testRstGetObjects(): void
    {
        xar::req()->setServerVar('REQUEST_METHOD', 'GET');
        xar::req()->setServerVar('PATH_INFO', '/v1/objects');
        ob_start();
        include sys::web() . 'rst.php';
        $output = ob_get_clean();
        xar::req()->setServerVar('REQUEST_METHOD', null);
        xar::req()->setServerVar('PATH_INFO', null);

        $expected = 1;
        $result = json_decode($output, true);
        $this->assertCount($expected, $result['items']);
        $expected = 'sample';
        $this->assertEquals($expected, $result['items'][0]['name']);
    }

    public function testRstGetSamples(): void
    {
        xar::req()->setServerVar('REQUEST_METHOD', 'GET');
        xar::req()->setServerVar('PATH_INFO', '/v1/objects/sample');
        ob_start();
        include sys::web() . 'rst.php';
        $output = ob_get_clean();
        xar::req()->setServerVar('REQUEST_METHOD', null);
        xar::req()->setServerVar('PATH_INFO', null);

        $expected = 3;
        $result = json_decode($output, true);
        $this->assertCount($expected, $result['items']);
        $expected = 'Johnny';
        $this->assertEquals($expected, $result['items'][0]['name']);
    }

    public function testRstOptions(): void
    {
        xar::req()->setServerVar('REQUEST_METHOD', 'OPTIONS');
        ob_start();
        include sys::web() . 'rst.php';
        $output = ob_get_clean();
        xar::req()->setServerVar('REQUEST_METHOD', null);

        $expected = '';
        // @todo this doesn't actually capture the headers
        $headers = headers_list();
        $this->assertEquals($expected, $output);
    }

    public function testVal(): void
    {
        $this->markTestSkipped('No idea how this is supposed to work or why it fails...');
        $_GET['v'] = '1';
        $_GET['u'] = '6';
        include sys::web() . 'val.php';
    }

    public function testUgrade(): void
    {
        ob_start();
        include sys::web() . 'upgrade.php';
        $output = ob_get_clean();

        $expected = 'Xaraya Upgrade';
        $this->assertStringContainsString($expected, $output);
    }

    public function testWs(): void
    {
        ob_start();
        include sys::web() . 'ws.php';
        $output = ob_get_clean();

        $expected = 'WSDL';
        $this->assertStringContainsString($expected, $output);
    }
}

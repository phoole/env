<?php declare(strict_types=1);

namespace Phoole\Tests;

use Phoole\Env\Environment;
use PHPUnit\Framework\TestCase;

class EnvironmentTest extends TestCase
{
    private $obj;
    private $ref;
    private $file = __DIR__ . '/sample.env';

    protected function setUp(): void
    {
        parent::setUp();
        $this->obj = new Environment();
        $this->ref = new \ReflectionClass(get_class($this->obj));
    }

    public function invokeMethod($methodName, array $parameters = array())
    {
        $method = $this->ref->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($this->obj, $parameters);
    }
    
    public function testLoad()
    {
        # overwrite is TRUE
        $this->invokeMethod('load', [$this->file, true]);
        $this->assertTrue(getenv('ROOT_DIR') === '/usr/local');
        putenv('ROOT_DIR');

        $this->assertTrue(getenv('BIN_DIR') === '/usr/local/bin');
        putenv('BIN_DIR');

        $this->assertTrue(getenv('TMP_DIR') === '/tmp');
        $this->assertTrue(getenv('MY_TMP_DIR') === false);
        putenv('TMP_DIR');

        $this->assertTrue(getenv('MY_DIR') === '/usr/local/my');
        $this->assertTrue(getenv('CONF_DIR') === '/usr/local/my/etc');
        putenv('MY_DIR');
        putenv('CONF_DIR');
        putenv('EXT_DIR');

        # overwrite is FALSE;
        putenv('ROOT_DIR=/usr');
        $this->invokeMethod('load', [$this->file, false]);

        $this->assertTrue(getenv('ROOT_DIR') === '/usr');

        $this->assertTrue(getenv('BIN_DIR') === '/usr/bin');
        putenv('BIN_DIR');

        $this->assertTrue(getenv('MY_DIR') === '/usr/my');
        $this->assertTrue(getenv('CONF_DIR') === '/usr/my/etc');
        putenv('MY_DIR');
        putenv('CONF_DIR');
        putenv('EXT_DIR');
        putenv('ROOT_DIR');
    }

    public function testParse()
    {
        $arr = [
            'ROOT_DIR' => '/',
            'BIN_DIR'  => '${ROOT_DIR}bin',
            'ETC_DIR'  => '${MY_ROOT:-${HOME_DIR:=/home}/my}/${ETC_NAME:=etc}'
        ];

        # overwrite
        $this->invokeMethod('parse', [$arr, true]);
        $this->assertTrue(getenv('ROOT_DIR') === '/');
        $this->assertTrue(getenv('BIN_DIR') === '/bin');
        $this->assertTrue(getenv('HOME_DIR') === '/home');
        $this->assertTrue(getenv('MY_ROOT') === false);
        $this->assertTrue(getenv('ETC_NAME') === 'etc');
        $this->assertTrue(getenv('ETC_DIR') === '/home/my/etc');
        putenv('ROOT_DIR');
        putenv('BIN_DIR');
        putenv('HOME_DIR');
        putenv('ETC_NAME');
        putenv('ETC_DIR');

        # not to overwrite
        putenv('ROOT_DIR=/mnt/');
        putenv('MY_ROOT=/myroot');

        $this->invokeMethod('parse', [$arr, false]);
        $this->assertTrue(getenv('ROOT_DIR') === '/mnt/');
        $this->assertTrue(getenv('BIN_DIR') === '/mnt/bin');
        $this->assertTrue(getenv('HOME_DIR') === '/home');
        $this->assertTrue(getenv('MY_ROOT') === '/myroot');
        $this->assertTrue(getenv('ETC_NAME') === 'etc');
        $this->assertTrue(getenv('ETC_DIR') === '/myroot/etc');
        putenv('ROOT_DIR');
        putenv('BIN_DIR');
        putenv('HOME_DIR');
        putenv('ETC_NAME');
        putenv('ETC_DIR');
        putenv('MY_ROOT');

    }

    public function testLoadPath()
    {
        $this->assertEquals(
            $this->invokeMethod('loadPath', [$this->file]),
            [
                'ROOT_DIR' => '/usr/local',
                'BIN_DIR'  => '${ROOT_DIR}/bin',
                'TMP_DIR'  => '${MY_TMP_DIR:-/tmp}',
                'CONF_DIR' => '${MY_DIR:=${ROOT_DIR}/my}/etc',
                'EXT_DIR'  => "test 'one'"
            ]
        );
    }

    public function testParseString()
    {
        $this->assertEquals(
            $this->invokeMethod('parseString', ["PATH=  # comment"]),
            ['PATH' => '']
        );

        $this->assertEquals(
            $this->invokeMethod('parseString', ["PATH=/usr/local/bin"]),
            ['PATH' => '/usr/local/bin']
        );

        $this->assertEquals(
            $this->invokeMethod('parseString', [" PATH = '/usr/local/bin' #comment"]),
            ['PATH' => '/usr/local/bin']
        );

        $this->assertEquals(
            $this->invokeMethod('parseString', [" PATH = \"/usr/local/bin\" #comment"]),
            ['PATH' => '/usr/local/bin']
        );

        $this->assertEquals(
            $this->invokeMethod('parseString', ['PATH = ${ENV_TEST}/bin #comment']),
            ['PATH' => '${ENV_TEST}/bin']
        );

        $this->assertEquals(
            $this->invokeMethod('parseString', ['PATH = ${ENV_TEST:=test}/bin #comment']),
            ['PATH' => '${ENV_TEST:=test}/bin']
        );

        $this->assertEquals(
            $this->invokeMethod('parseString', ['PATH = ${ENV_TEST:-test}/bin #comment']),
            ['PATH' => '${ENV_TEST:-test}/bin']
        );
    }

    public function testDeReference()
    {
        # not set
        $this->assertEquals(
            $this->invokeMethod('deReference', ['${ENV_TEST}/bin']),
            '/bin'
        );

        # set
        putenv('ENV_TEST=/usr/local');
        $this->assertEquals(
            $this->invokeMethod('deReference', ['${ENV_TEST}/bin']),
            '/usr/local/bin'
        );
        putenv('ENV_TEST');

        # 2 set
        putenv('ENV_TEST=/usr/local');
        putenv('ENV_TEST_BIN=bin');
        $this->assertEquals(
            $this->invokeMethod('deReference', ['${ENV_TEST}/${ENV_TEST_BIN}']),
            '/usr/local/bin'
        );
        putenv('ENV_TEST');
        putenv('ENV_TEST_BIN');

        # recursive set
        putenv('ENV_TEST=/usr/local');
        putenv('TEST_USER=TEST');
        $this->assertEquals(
            $this->invokeMethod('deReference', ['${ENV_${TEST_USER}}/bin/${TEST_USER}']),
            '/usr/local/bin/TEST'
        );
        putenv('ENV_TEST');
        putenv('TEST_USER');
    }

    public function testExpandValue()
    {
        # not set, use provided value
        putenv('ENV_TEST');
        $this->assertEquals(
            $this->invokeMethod('expandValue', ['ENV_TEST:-test']),
            'test'
        );
        $this->assertTrue(getenv('ENV_TEST') === false);

        # use preset value
        putenv('ENV_TEST=bingo');
        $this->assertEquals(
            $this->invokeMethod('expandValue', ['ENV_TEST:-test']),
            'bingo'
        );
        putenv('ENV_TEST');

        # use set value
        putenv('ENV_TEST=bingo');
        $this->assertEquals(
            $this->invokeMethod('expandValue', ['ENV_TEST:=test']),
            'bingo'
        );

        # set if not set yet
        putenv('ENV_TEST');
        $this->assertEquals(
            $this->invokeMethod('expandValue', ['ENV_TEST:=test']),
            'test'
        );
        $this->assertTrue(getenv('ENV_TEST') === 'test');
        putenv('ENV_TEST');
    }

    public function testSetEnv()
    {
        putenv('ENV_TEST');
        $this->invokeMethod('setEnv', ['ENV_TEST', 'test', true]);
        $this->assertEquals(getenv('ENV_TEST'), 'test');

        putenv('ENV_TEST');
        $this->invokeMethod('setEnv', ['ENV_TEST', 'test', false]);
        $this->assertEquals(getenv('ENV_TEST'), 'test');

        putenv('ENV_TEST=bingo');
        $this->invokeMethod('setEnv', ['ENV_TEST', 'test', false]);
        $this->assertEquals(getenv('ENV_TEST'), 'bingo');
        putenv('ENV_TEST');
    }
}
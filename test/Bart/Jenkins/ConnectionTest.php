<?php
namespace Bart\Jenkins;


use Bart\BaseTestCase;
use Bart\Diesel;

class ConnectionTest extends BaseTestCase
{
    private static $fakeDomain = 'example';
    private static $fakeApiPath = 'fake/api/path';

    private static $fakeApiResponseSuccess = [
        'info' => ['http_code' => 200],
        'content' => '{"key":"value"}',
    ];

    private static $fakeApiResponseFailure = [
        'info' => ['http_code' => 403],
        'content' => 'error message',
    ];

    public function testCurlBaseCase()
    {
        $connection = new Connection(self::$fakeDomain);
        $this->mockCurl(
            function($mockCurl) {$mockCurl->get()->once()->return_value(self::$fakeApiResponseSuccess);}
        );
        $connection->curlJenkinsApi(self::$fakeApiPath);
    }

    public function testCurlWithHTTPS()
    {
        $expectedProtocol = 'https';
        $expectedPort = 443;
        $connection = new Connection(self::$fakeDomain, $expectedProtocol);
        $this->mockCurl(
            function($mockCurl) {$mockCurl->get()->once()->return_value(self::$fakeApiResponseSuccess);},
            $expectedProtocol,
            $expectedPort
        );
        $connection->curlJenkinsApi(self::$fakeApiPath);
    }

    public function testCurlWithCustomPort()
    {
        $expectedProtocol = 'http';
        $expectedPort = 43000;
        $connection = new Connection(self::$fakeDomain, $expectedProtocol, $expectedPort);
        $this->mockCurl(
            function($mockCurl) {$mockCurl->get()->once()->return_value(self::$fakeApiResponseSuccess);},
            $expectedProtocol,
            $expectedPort
        );
        $connection->curlJenkinsApi(self::$fakeApiPath);
    }

    public function testCurlWithAuthSet()
    {
        $expectedProtocol = 'http';
        $expectedPort = 8080;
        $connection = new Connection(self::$fakeDomain);

        $fakeUser = 'user';
        $fakeToken = 'token';
        $connection->setAuth($fakeUser, $fakeToken);

        $this->mockCurl(
            function($mockCurl) use ($fakeUser, $fakeToken) {
                $mockCurl->setPhpCurlOpts([CURLOPT_USERPWD => "{$fakeUser}:{$fakeToken}"])->once();
                $mockCurl->get()->once()->return_value(self::$fakeApiResponseSuccess);
            },
            $expectedProtocol,
            $expectedPort
        );
        $connection->curlJenkinsApi(self::$fakeApiPath);
    }

    public function testCurlWithPostDataSet()
    {
        $postData = ['fake_key' => 'fake_value'];
        $connection = new Connection(self::$fakeDomain);
        $this->mockCurl(
            function($mockCurl) use ($postData) {
                $mockCurl->post('', [], $postData)->once()->return_value(self::$fakeApiResponseSuccess);
            }
        );
        $connection->curlJenkinsApi(self::$fakeApiPath, $postData);
    }

    public function testCurlFailure()
    {
        $connection = new Connection(self::$fakeDomain);
        $this->mockCurl(
            function($mockCurl) {
                $mockCurl->get()->once()->return_value(self::$fakeApiResponseFailure);
            }
        );
        $this->setExpectedException('\Bart\Jenkins\JenkinsApiException');
        $connection->curlJenkinsApi(self::$fakeApiPath);
    }

    /**
     * Mocks out the Diesel Curl class
     * @param callable $configure
     * @param string $expectedProtocol
     * @param string $expectedPort
     * @throws \Bart\DieselException
     */
    private function mockCurl($configure, $expectedProtocol = 'http', $expectedPort = '8080')
    {
        $fullUrl = "${expectedProtocol}://" . self::$fakeDomain . ":{$expectedPort}/". self::$fakeApiPath;
        $mockCurl = $this->shmock('\Bart\Curl', function ($curlStub) use ($configure) {
            $configure($curlStub);
        }, true);

        Diesel::registerInstantiator('\Bart\Curl', function ($url, $port) use ($mockCurl, $fullUrl, $expectedPort) {
            $this->assertEquals($fullUrl, $url, 'Full Jenkins REST API URL');
            $this->assertEquals($expectedPort, $port, 'Port');
            return $mockCurl;
        });

    }

}

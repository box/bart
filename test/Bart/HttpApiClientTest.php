<?php
namespace Bart;

/**
 * PHPUnit Test class for HttpApiClient
 *
 * @author Jeremy Pollard <jpollard@bpx.com>
 */ 
class HttpApiClientTest extends BaseTestCase{

	public function testConstructor()
	{


		$this->assertThrows('Bart\HttpApiClientException','Invalid URI',
			function () {
				$a = new HttpApiClient("http://localhost:4743/:80");
			}, "Accepted faulty url");
	}

	public function testSetHeaders()
	{

		//headers
		$this->assertThrows('Bart\HttpApiClientException','$globalHeaders',
			function () {
				$a = new HttpApiClient("https://localhost");
				$a->setGlobalHeaders(array('lol' => new \ArrayObject()));
			}, "Accepted faulty headers: object in array");

		$this->assertThrows('Bart\HttpApiClientException','$globalHeaders',
			function () {
				$a = new HttpApiClient("http://localhost");
				$a->setGlobalHeaders(12);
			}, "Accepted faulty headers: 12");

		$this->assertThrows('Bart\HttpApiClientException','$globalHeaders',
			function () {
				$a = new HttpApiClient("https://localhost");
				$a->setGlobalHeaders(array(1=> array()));
			}, "Accepted faulty headers: multi-D array");
	}

	public function testSetTimeout()
	{
		//timeout
		$this->assertThrows('Bart\HttpApiClientException','Timeout',
			function () {
				$a = new HttpApiClient("http://localhost");
				$a->setGlobalTimeout(-1);
			}, "negative timeout accepted");

		$this->assertThrows('Bart\HttpApiClientException','Timeout',
			function () {
				$a = new HttpApiClient("http://localhost");
				$a->setGlobalTimeout("lol");
			}, "invalid timeout: non numic");

	}

	public function testInitCurlChoosesPort443WhenHttps()
	{
		// https scheme should default to port 443
		$mockCurl = $this->setupMockCurl('https://localhost/', 443);

		$mockCurl->expects($this->exactly(1))
			->method('Get')
			->with($this->equalTo('v1/device'),
				$this->equalTo(array()),
				$this->anything())
			->will($this->returnValue(array(
				'headers' => array(),
				'content' => 'asdf', 'info' => array('http_code' => 200) ))
			);

		$hac = new HttpApiClient('https://localhost/');
		$response = $hac->get('v1/device');

		$this->assertEquals('asdf', $response->get_body());
	}

	public function testHttpApiClientBasicGET()
	{
		$mockCurl = $this->setupMockCurl("https://localhost:4743/", 4743);

		$mockCurl->expects($this->exactly(1))
			->method('Get')
			->with($this->equalTo('v1/device'),
				$this->equalTo(array()),
				$this->anything())
			->will($this->returnValue(array('headers' => array(), 'content' => "asdf", 'info' => array('http_code' => 200) )) );

		$hac = new HttpApiClient("https://localhost:4743/");

		$response = $hac->get("v1/device");

		$this->assertEquals('asdf', $response->get_body());
	}

	/**
	 * Test general header insertion
	 */
	public function testHttpApiClientHeaders()
	{
		$responseArray = $this->getResponseArray();

		$headers = array('Header1' => 'Value1', 'Header2' => 'Value2');

		$mockCurl = $this->setupMockCurl("http://localhost", 80);

		$mockCurl->expects($this->once())
			->method("Get")
			->with(
				$this->equalTo("/"),
				$this->equalTo(array()),
				$this->equalTo($headers)
			)
			->will($this->returnValue($responseArray));

		$mockCurl->expects($this->once())
			->method("Post")
			->with(
				$this->equalTo("/"),
				$this->equalTo(array()),
				$this->equalTo(array()),
				$this->equalTo($headers)
			)
			->will($this->returnValue($responseArray));

		$hac = new HttpApiClient("http://localhost");

		$hac->get("/", array(), $headers);
		$hac->post("/", array(), array(), $headers);
	}

	/**
	 * Test global header usage
	 */
	public function testHttpApiClientGlobalHeaders()
	{
		$responseMap = array(
			// plan request
			array("/", array(), array('Header1' => 'Value1', 'Header2' => 'Value2'), null,
				array(
					'headers' => array('Header3' => 'Value3'),
					'content' => 'Without Header4',
					'info' => array('http_code' => 200)
				)
			),
			//request with additional header
			array("/", array(), array('Header1' => 'Value1', 'Header2' => 'Value2', 'Header4' => 'Value4'), null,
				array(
					'headers' => array('Header3' => 'Value3'),
					'content' => 'With Header4',
					'info' => array('http_code' => 200)
				)
			)
		);
		$mockCurl = $this->setupMockCurl("http://localhost",80);

		$mockCurl->expects($this->exactly(3))
			->method('Get')
			->will($this->returnValueMap($responseMap));


		$hac = new HttpApiClient("http://localhost");

		$hac->setGlobalHeaders(array('Header1' => 'Value1', 'Header2' => 'Value2'));

		$res = $hac->get();
		$this->assertEquals("Without Header4", $res->get_body());


		$res2 = $hac->get("/", array(), array('Header4' => 'Value4'));
		$this->assertEquals("With Header4", $res2->get_body());

		$res3 = $hac->get();
		$this->assertEquals("Without Header4", $res3->get_body());
	}


	public function testHttpApiClientSetCookies()
	{
		$response = $this->getResponseArray();

		$cookies = array('Cookie1' => 'Value1', 'Cookie2' => 'Value2');



		$mockCurl = $this->setupMockCurl("http://localhost/",80);

		$hac = new HttpApiClient("http://localhost/");

		$mockCurl->expects($this->exactly(2))
			->method('Get')
			->with($this->equalTo("/"),
				$this->equalTo(array()),
				$this->equalTo(array()),
				$this->equalTo($cookies))
			->will($this->returnValue($response));

		$mockCurl->expects($this->exactly(2))
			->method('Post')
			->with($this->equalTo("/"),
				$this->equalTo(array()),
				$this->equalTo(null),
				$this->equalTo(array()),
				$this->equalTo($cookies))
			->will($this->returnValue($response));


		//do each call twice to verify cookies stay

		$hac->setCookies($cookies);

		$this->assertEquals("Huzzah!", $hac->get()->get_body());
		$this->assertEquals("Huzzah!", $hac->get()->get_body());

		$this->assertEquals("Huzzah!", $hac->post()->get_body());
		$this->assertEquals("Huzzah!", $hac->post()->get_body());


	}

	public function testHttpApiClientTrackCookies()
	{
		$responseNoCookies = $this->getResponseArray();
		$responseNoCookies['content'] = "NoCookies";

		$responseSetCookies = $this->getResponseArray();
		$responseSetCookies['headers']['Set-Cookie'][] = "Cookie1=Value1;";
		$responseSetCookies['headers']['Set-Cookie'][] = "Cookie2=Value2;";
		$responseSetCookies['content'] = "SetCookies";

		$rMap = array(
			array("/",array(), array(), null, $responseSetCookies),
			array("/",array(), array(), array('Cookie1' => 'Value1', 'Cookie2' => 'Value2'),
				$responseNoCookies)
		);

		$mockCurl = $this->setupMockCurl("http://localhost/",80);

		$mockCurl->expects($this->exactly(3))
			->method("Get")
			->will($this->returnValueMap($rMap));

		$hac = new HttpApiClient("http://localhost/");

		//disable cookie tacking
		$hac->trackCookies(false);
		$this->assertEquals("SetCookies", $hac->get()->get_body(), "Not seeing cookies sent" );
		$this->assertEquals(0, count($hac->getCookies()), "Cookies set are not zero");

		//enable cookie tracking
		$hac->trackCookies(true);
		// This one should set the cookies
		$this->assertEquals("SetCookies", $hac->get()->get_body(), "Not seeing cookies set" );
		$this->assertEquals(2, count($hac->getCookies()), "There are not 2 cookies sent");

		// This one will not have cookies, but expects cookies
		$this->assertEquals("NoCookies", $hac->get()->get_body(), "Seeing cookies when they shouldn't be");
	}

	public function testHttpApiClientSetAuth()
	{
		$mockCurl = $this->setupMockCurl("http://localhost",80);

		$mockCurl->expects($this->once())
			->method("setAuth")
			->with($this->equalTo("user"),
				$this->equalTo("password"),
				$this->equalTo(CURLAUTH_BASIC));

		$mockCurl->expects($this->once())
			->method("Get")
			->will($this->returnValue($this->getResponseArray()));

		$hac = new HttpApiClient("http://localhost");

		$hac->setAuth("user", "password");

		$hac->get();
	}


	public function testHttpApiClientDelete()
	{
		$mockCurl = $this->setupMockCurl("http://localhost",80);
		$mockCurl->expects($this->once())
			->method("Delete")
			->will($this->returnValue($this->getResponseArray()));

		$hac = new HttpApiClient("http://localhost");

		$hac->delete();


	}


	public function testHttpApiClientPut()
	{
		$mockCurl = $this->setupMockCurl("http://localhost",80);
		$mockCurl->expects($this->once())
			->method("Put")
			->will($this->returnValue($this->getResponseArray()));

		$hac = new HttpApiClient("http://localhost");

		$hac->put();


	}

	// setup a mock curl object and register it with diesel
    private function setupMockCurl($testUrl, $testPort)
	{
		$mockCurl = $this->getMock('\Bart\Curl', array(), array($testUrl, $testPort));

		$phpu = $this;
		Diesel::registerInstantiator('\Bart\Curl',
			function($urlParam,$portParam) use($phpu,$mockCurl, $testUrl, $testPort) {
				$phpu->assertEquals($testUrl, $urlParam, 'url');
				$phpu->assertEquals($testPort, $portParam, 'port');
				return $mockCurl;
			});
        return $mockCurl;
	}

	/**
	 * @return array basic response array for mocked curl
	 */
	private function getResponseArray()
	{
		return array(
			'headers' => array('Header3' => 'Value3'),
			'content' => 'Huzzah!',
			'info' => array('http_code' => 200)
		);
	}
}

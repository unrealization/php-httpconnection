<?php

use PHPUnit\Framework\TestCase;
use unrealization\PHPClassCollection\HTTPConnection;

/**
 * HTTPConnection test case.
 * @covers unrealization\PHPClassCollection\HTTPConnection
 * @uses unrealization\PHPClassCollection\TCPConnection
 */
class HTTPConnectionTest extends TestCase
{
	private function getMockConnection(array $constructorArgs, string $response)
	{
		if (!empty($response))
		{
			$mockBuilder = $this->getMockBuilder(HTTPConnection::class);
			$connection = $mockBuilder->setConstructorArgs($constructorArgs)->setMethods(array('connect', 'disconnect', 'write', 'read'))->getMock();
			$connection->method('connect')->willReturn(true);
			$connection->method('disconnect');
			$connection->method('write');
			$connection->method('read')->willReturn($response);
		}
		else
		{
			$mockBuilder = $this->getMockBuilder(HTTPConnection::class);
			$connection = $mockBuilder->setConstructorArgs($constructorArgs)->setMethods(array('connect'))->getMock();
			$connection->method('connect')->willReturn(false);
		}

		return $connection;
	}

	private function getResponse(bool $headerOnly = false): string
	{
		$response = 'HTTP/1.1 200 OK'."\r\n";
		$response .= 'Date: Fri, 10 May 2019 08:34:52 GMT'."\r\n";
		$response .= 'Server: Apache/2.4.38 (FreeBSD) OpenSSL/1.0.2o-freebsd PHP/7.2.15'."\r\n";

		if ($headerOnly === false)
		{
			$response .= 'Set-Cookie: cookie1=abcd; path=/; max-age=864000'."\r\n";
			$response .= 'Set-Cookie: cookie2=1234; path=/; max-age=864000'."\r\n";
			$response .= 'Content-Length: 16'."\r\n";
		}

		//$response .= 'Strict-Transport-Security: max-age=17280000'."\r\n";
		$response .= 'Connection: close'."\r\n";

		if ($headerOnly === false)
		{
			$response .= 'Content-Type: application/json; charset=utf-8'."\r\n\r\n";
			$response .= '{"info" : "OK"}'."\n";
		}
		else
		{
			$response .= "\r\n";
		}

		return $response;
	}

	/**
	 * Tests HTTPConnection->__construct()
	 */
	public function test__construct()
	{
		$conn = new HTTPConnection('some.host');
		$this->assertInstanceOf(HTTPConnection::class, $conn);
	}

	public function test__constructWithProxy()
	{
		$conn = new HTTPConnection('some.host', 80, false, 'some.proxy');
		$this->assertInstanceOf(HTTPConnection::class, $conn);
	}

	/**
	 * Tests HTTPConnection->setUserAgent()
	 */
	public function testSetUserAgent()
	{
		$conn = new HTTPConnection('some.host', 80, false, 'some.proxy');
		$conn->setUserAgent('Test');
		$this->assertTrue(true);
	}

	/**
	 * Tests HTTPConnection->enableAutoUnchunk()
	 */
	public function testEnableAutoUnchunk()
	{
		$conn = new HTTPConnection('some.host', 80, false, 'some.proxy');
		$conn->enableAutoUnchunk(false);
		$this->assertTrue(true);
	}

	/**
	 * Tests HTTPConnection->head()
	 */
	public function testHead()
	{
		$connection = $this->getMockConnection(array('some.host', 80, false, 'some.proxy'), $this->getResponse(true));
		$response = $connection->head('/');
		$this->assertEquals(200, $response['header']['http']['code']);
	}

	public function testHeadException()
	{
		$connection = $this->getMockConnection(array('some.host', 443, true), '');
		$this->expectException(\Exception::class);
		$connection->head('', array('someKey' => 'someValue'));
	}

	/**
	 * Tests HTTPConnection->get()
	 */
	public function testGet()
	{
		$connection = $this->getMockConnection(array('some.host'), $this->getResponse());
		$response = $connection->get('/', array('someKey' => array('value' => 'someValue')));
		$this->assertEquals(200, $response['header']['http']['code']);
	}

	public function testGetException()
	{
		$connection = $this->getMockConnection(array('some.host', 443, true, 'some.proxy'), '');
		$this->expectException(\Exception::class);
		$connection->get('/', array(), array('someCookie' => 'someValue'));
	}

	/**
	 * Tests HTTPConnection->post()
	 */
	public function testPost()
	{
		$connection = $this->getMockConnection(array('some.host'), $this->getResponse());
		$response = $connection->post('/', array('someGetKey' => 'someValue'), array('somePostKey', 'someValue'));
		$this->assertEquals(200, $response['header']['http']['code']);
	}

	public function testPostException()
	{
		$connection = $this->getMockConnection(array('some.host'), '');
		$this->expectException(\Exception::class);
		$connection->post('/');
	}

	/**
	 * Tests HTTPConnection->delete()
	 */
	public function testDelete()
	{
		$connection = $this->getMockConnection(array('some.host'), $this->getResponse());
		$response = $connection->delete('/', array(), array(), 'someUser', 'somePassword');
		$this->assertEquals(200, $response['header']['http']['code']);
	}

	public function testDeleteException()
	{
		$connection = $this->getMockConnection(array('some.host'), '');
		$this->expectException(\Exception::class);
		$connection->delete('/');
	}

	/**
	 * Tests HTTPConnection->put()
	 */
	public function testPut()
	{
		$connection = $this->getMockConnection(array('some.host'), $this->getResponse());
		$response = $connection->put('/');
		$this->assertEquals(200, $response['header']['http']['code']);
	}

	public function testPutException()
	{
		$connection = $this->getMockConnection(array('some.host'), '');
		$this->expectException(\Exception::class);
		$connection->put('/');
	}
}


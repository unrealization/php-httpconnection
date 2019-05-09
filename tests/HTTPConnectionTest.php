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
			$mock = $mockBuilder->setConstructorArgs($constructorArgs)->setMethods(array('connect', 'disconnect', 'write', 'read'))->getMock();
			$mock->method('connect')->willReturn(true);
			$mock->method('disconnect');
			$mock->method('write');
			$mock->method('read')->willReturn($response);
		}
		else
		{
			$mockBuilder = $this->getMockBuilder(HTTPConnection::class);
			$mock = $mockBuilder->setConstructorArgs($constructorArgs)->setMethods(array('connect'))->getMock();
			$mock->method('connect')->willReturn(false);
		}

		return $mock;
	}

	private function getHeadResponse(): string
	{
		$response = 'HTTP/1.1 200 OK'."\r\n";
		$response .= 'Content-Length: 0'."\r\n";
		//$response .= 'Content-Type: application/json'."\r\n";
		$response .= 'Date: Sat, 04 May 2019 01:21:47 GMT'."\r\n"."\r\n";
		//$response .= '{"id":1,"jsonrpc":"2.0","result":[]}';
		return $response;
	}

	private function getGetResponse(): string
	{
		$response = 'HTTP/1.1 200 OK'."\r\n";
		$response .= 'Content-Length: 36'."\r\n";
		$response .= 'Content-Type: application/json'."\r\n";
		$response .= 'Date: Sat, 04 May 2019 01:21:47 GMT'."\r\n"."\r\n";
		$response .= '{"id":1,"jsonrpc":"2.0","result":[]}';
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
		$mock = $this->getMockConnection(array('some.host', 80, false, 'some.proxy'), $this->getHeadResponse());
		$response = $mock->head('/');
		$this->assertEquals(200, $response['header']['http']['code']);
	}

	public function testHeadException()
	{
		$mock = $this->getMockConnection(array('some.host', 443, true), '');
		$this->expectException(\Exception::class);
		$mock->head('', array('someKey' => 'someValue'));
	}

	/**
	 * Tests HTTPConnection->get()
	 */
	public function testGet()
	{
		$mock = $this->getMockConnection(array('some.host'), $this->getGetResponse());
		$response = $mock->get('/', array('someKey' => array('value' => 'someValue')));
		$this->assertEquals(200, $response['header']['http']['code']);
	}

	public function testGetException()
	{
		$mock = $this->getMockConnection(array('some.host', 443, true, 'some.proxy'), '');
		$this->expectException(\Exception::class);
		$mock->get('/', array(), array('someCookie' => 'someValue'));
	}

	/**
	 * Tests HTTPConnection->post()
	 */
	public function testPost()
	{
		// TODO Auto-generated HTTPConnectionTest->testPost()
		$this->markTestIncomplete("post test not implemented");

		$this->hTTPConnection->post(/* parameters */);
	}

	public function testPostException()
	{
		$mock = $this->getMockConnection(array('some.host'), '');
		$this->expectException(\Exception::class);
		$mock->post('/');
	}

	/**
	 * Tests HTTPConnection->delete()
	 */
	public function testDelete()
	{
		$mock = $this->getMockConnection(array('some.host'), $this->getHeadResponse());
		$response = $mock->delete('/', array(), array(), 'someUser', 'somePassword');
		$this->assertEquals(200, $response['header']['http']['code']);
	}

	public function testDeleteException()
	{
		$mock = $this->getMockConnection(array('some.host'), '');
		$this->expectException(\Exception::class);
		$mock->delete('/');
	}

	/**
	 * Tests HTTPConnection->put()
	 */
	public function testPut()
	{
		// TODO Auto-generated HTTPConnectionTest->testPut()
		$this->markTestIncomplete("put test not implemented");

		$this->hTTPConnection->put(/* parameters */);
	}

	public function testPutException()
	{
		$mock = $this->getMockConnection(array('some.host'), '');
		$this->expectException(\Exception::class);
		$mock->put('/');
	}
}


<?php
declare(strict_types=1);
/**
 * @package PHPClassCollection
 * @subpackage HTTPConnection
 * @link http://php-classes.sourceforge.net/ PHP Class Collection
 * @author Dennis Wronka <reptiler@users.sourceforge.net>
 */
namespace unrealization\PHPClassCollection;

use unrealization\PHPClassCollection\HTTPConnection\HTTPRequest;
use unrealization\PHPClassCollection\HTTPConnection\HTTPResponse;

/**
 * @package PHPClassCollection
 * @subpackage HTTPConnection
 * @link http://php-classes.sourceforge.net/ PHP Class Collection
 * @author Dennis Wronka <reptiler@users.sourceforge.net>
 * @version 3.99.8
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL 2.1
 * @todo Finish the rewrite of decodeResponse()
 */
class HTTPConnection extends TCPConnection
{
	/**
	 * The address of the web-server.
	 * @var string
	 */
	private $httpHost;
	/**
	 * The port of the web-server.
	 * @var int
	 */
	private $httpPort;
	/**
	 * The address of the proxy-server.
	 * @var string
	 */
	private $proxyHost;
	/**
	 * The port of the proxy-server.
	 * @var int
	 */
	private $proxyPort;
	/**
	 * The user-agent used for identification.
	 * @var string
	 */
	private $userAgent = 'PHP/unrealization/HTTPConnection';
	/**
	 * Whether or not to automatically unchunk a chunked response.
	 * @var bool
	 */
	private $autoUnchunk = true;

	/**
	 * Constructor
	 * @param string $httpHost
	 * @param int $httpPort
	 * @param bool $ssl
	 * @param string $proxyHost
	 * @param int $proxyPort
	 */
	public function __construct(string $httpHost, int $httpPort = 80, bool $ssl = false, ?string $proxyHost = null, int $proxyPort = 3128)
	{
		$this->httpHost = $httpHost;
		$this->httpPort = $httpPort;
		$this->proxyHost = $proxyHost;
		$this->proxyPort = $proxyPort;

		if (!is_null($this->proxyHost))
		{
			parent::__construct($this->proxyHost, $this->proxyPort, $ssl);
		}
		else
		{
			parent::__construct($this->httpHost, $this->httpPort, $ssl);
		}
	}

	/**
	 * Set the user-agent
	 * @param string $userAgent
	 * @return HTTPConnection
	 */
	public function setUserAgent(string $userAgent = 'PHP/unrealization/HTTPConnection'): HTTPConnection
	{
		$this->userAgent = $userAgent;
		return $this;
	}

	/**
	 * Enable or disable automatic unchunking.
	 * @param bool $autoUnchunk
	 * @return HTTPConnection
	 */
	public function enableAutoUnchunk(bool $autoUnchunk = true): HTTPConnection
	{
		$this->autoUnchunk = $autoUnchunk;
		return $this;
	}

	/**
	 * Create the parameter string from the parameter array.
	 * @param array $parameters
	 * @param string $separator
	 * @return string
	 */
	private function createParamString(array $parameters, string $separator = '&'): string
	{
		$paramList = array();

		foreach ($parameters as $key => $value)
		{
			$paramString = $key;

			if (is_array($value))
			{
				$paramString .= '='.$value['value'];
			}
			elseif (strlen((string)$value) > 0)
			{
				$paramString .= '='.$value;
			}

			$paramList[] = $paramString;
		}

		return implode($separator, $paramList);
	}

	/**
	 * Send and process the request.
	 * @param string $request
	 * @param string $content
	 * @return \unrealization\PHPClassCollection\HTTPConnection\HTTPResponse
	 * @throws \Exception
	 */
	private function sendRequest(string $request, string $content = '', bool $waitForResponse = true): ?HTTPResponse
	{
		$connected = $this->connect();

		if ($connected === false)
		{
			throw new \Exception('Not connected');
		}

		$requestString = $request.$content;
		$this->write($requestString);

		if ($waitForResponse === true)
		{
			$response = $this->read();
		}

		$this->disconnect();

		if ($waitForResponse === false)
		{
			return null;
		}

		$response = new HTTPResponse($response, $this->autoUnchunk);
		return $response;
	}

	private function httpRequest(string $requestType, HTTPRequest $request, bool $waitForResponse): ?HTTPResponse
	{
		$uri = $request->getUri();

		if (!mb_ereg_match('^\/', $uri))
		{
			$uri = '/'.$uri;
		}

		$getParameters = $request->getGetParameters();

		if (!empty($getParameters))
		{
			$getParamString = '?'.$this->createParamString($getParameters);
		}
		else
		{
			$getParamString = '';
		}

		if (!mb_ereg_match('^unix:', $this->httpHost))
		{
			$host = $this->httpHost;

			if ((($this->ssl === false) && ($this->httpPort !== 80)) || (($this->ssl === true) && ($this->httpPort !== 443)))
			{
				$host .= ':'.$this->httpPort;
			}
		}
		else
		{
			$host = 'Socket';
		}

		if (!is_null($this->proxyHost))
		{
			if ($this->ssl === true)
			{
				$uri = 'https://'.$host.$uri;
			}
			else
			{
				$uri = 'http://'.$host.$uri;
			}
		}

		$requestString = $requestType.' '.$uri.$getParamString.' HTTP/1.1'."\r\n";
		$requestString .= 'Host: '.$host."\r\n";
		$requestString .= 'User-Agent: '.$this->userAgent."\r\n";

		$cookies = $request->getCookies();

		if (!empty($cookies))
		{
			$requestString .= 'Cookie: '.$this->createParamString($cookies, '; ')."\r\n";
		}

		$authUser = $request->getAuthenticationUser();

		if (!empty($authUser))
		{
			$requestString .= 'Authorization: Basic '.base64_encode($authUser.':'.$request->getAuthenticationPassword())."\r\n";
		}

		$extraHeaders = $request->getExtraHeaders();

		foreach ($extraHeaders as $name => $value)
		{
			$requestString .= $name.': '.$value."\r\n";
		}

		$content = '';
		$postParameters = $request->getPostParameters();
		$fileParameters = $request->getFiles();

		if (!empty($fileParameters))
		{
			$boundary = '-------------------------'.mb_substr(md5(uniqid()), 0, 15);
			$requestString .= 'Content-Type: multipart/form-data; boundary='.$boundary."\r\n";

			foreach ($fileParameters as $key => $value)
			{
				$postFile = fopen($value['fileName'], 'r');
				$fileContent = fread($postFile, filesize($value['fileName']));
				fclose($postFile);
				$content .= '--'.$boundary."\r\n";
				$content .= 'Content-Disposition: form-data; name="'.$key.'"; filename="'.basename($value['fileName']).'"'."\r\n";

				if (!empty($value['mimeType']))
				{
					$content .= 'Content-Type: '.$value['mimeType']."\r\n\r\n";
				}
				else
				{
					$content .= 'Content-Type: application/octet-stream'."\r\n\r\n";
				}

				$content .= $fileContent."\r\n";
			}

			foreach ($postParameters as $key => $value)
			{
				$content .= '--'.$boundary."\r\n";
				$content .= 'Content-Disposition: form-data; name="'.$key.'"'."\r\n\r\n";
				$content .= $value."\r\n";
			}

			$content .= '--'.$boundary.'--'."\r\n";
		}
		elseif (!empty($postParameters))
		{
			$requestString .= 'Content-Type: application/x-www-form-urlencoded'."\r\n";
			$content .= $this->createParamString($postParameters);
		}

		if (!empty($content))
		{
			$requestString .= 'Content-Length: '.mb_strlen($content)."\r\n";
		}

		$requestString .= 'Connection: close'."\r\n\r\n";

		return $this->sendRequest($requestString, $content, $waitForResponse);
	}

	public function head(HTTPRequest $request, bool $waitForResponse = true): ?HTTPResponse
	{
		return $this->httpRequest('HEAD', $request, $waitForResponse);
	}

	public function get(HTTPRequest $request, bool $waitForResponse = true): ?HTTPResponse
	{
		return $this->httpRequest('GET', $request, $waitForResponse);
	}

	public function post(HTTPRequest $request, bool $waitForResponse = true): ?HTTPResponse
	{
		return $this->httpRequest('POST', $request, $waitForResponse);
	}

	public function delete(HTTPRequest $request, bool $waitForResponse = true): ?HTTPResponse
	{
		return $this->httpRequest('DELETE', $request, $waitForResponse);
	}

	public function put(HTTPRequest $request, bool $waitForResponse = true): ?HTTPResponse
	{
		return $this->httpRequest('PUT', $request, $waitForResponse);
	}
}
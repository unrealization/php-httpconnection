<?php
declare(strict_types=1);
/**
 * @package PHPClassCollection
 * @subpackage HTTPConnection
 * @link http://php-classes.sourceforge.net/ PHP Class Collection
 * @author Dennis Wronka <reptiler@users.sourceforge.net>
 */
namespace unrealization\PHPClassCollection;
/**
 * @package PHPClassCollection
 * @subpackage HTTPConnection
 * @link http://php-classes.sourceforge.net/ PHP Class Collection
 * @author Dennis Wronka <reptiler@users.sourceforge.net>
 * @version 3.0.0
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL 2.1
 * @todo Finish the rewrite decodeResponse()
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
	 */
	public function setUserAgent(string $userAgent = 'PHP/unrealization/HTTPConnection')
	{
		$this->userAgent = $userAgent;
	}

	/**
	 * Enable or disable automatic unchunking.
	 * @param bool $autoUnchunk
	 */
	public function enableAutoUnchunk(bool $autoUnchunk = true)
	{
		$this->autoUnchunk = $autoUnchunk;
	}

	/**
	 * Decode the response
	 * @param string $response
	 * @return array
	 */
	private function decodeResponse(string $response): array
	{
		$data = array(
				'raw'		=> $response,
				'body'		=> '',
				'header'	=> array(
						'raw'		=> '',
						'server'	=> '',
						'cookies'	=> array(),
						'http'		=> array(
								'version'	=> '',
								'code'		=> '',
								'status'	=> ''
						),
						'content'	=> array(
								'length'	=> '',
								'type'		=> '',
								'charset'	=> '',
								'encoding'	=> ''
						),
						'location'	=> array(
								'ssl'			=> false,
								'protocol'		=> '',
								'host'			=> '',
								'port'			=> '',
								'uri'			=> '',
								'parameters'	=> ''
						)
				)
		);
		$matches = array();

		if (preg_match('@^.*((?|\r)?\n)@Um', $data['raw'], $matches))
		{
			$lineBreak = $matches[1];
		}
		else
		{
			return $data;
		}

		if (preg_match('@^((.+)(?|'.$lineBreak.'){2})(.+)?$@sU', $data['raw'], $matches))
		{
			$data['header']['raw'] = $matches[2].$lineBreak;

			if (isset($matches[3]))
			{
				$data['body'] = $matches[3];
			}
		}
		else
		{
			return $data;
		}

		if (preg_match('@HTTP\/([\d]\.[\d]) ([\d]+) (.+)'.$lineBreak.'@', $data['header']['raw'], $matches))
		{
			$data['header']['http']['version'] = $matches[1];
			$data['header']['http']['code'] = $matches[2];
			$data['header']['http']['status'] = $matches[3];
		}

		if (preg_match('@Server: (.+)'.$lineBreak.'@', $data['header']['raw'], $matches))
		{
			$data['header']['server'] = $matches[1];
		}

		if (preg_match('@Content-Length: ([\d]+)'.$lineBreak.'@', $data['header']['raw'], $matches))
		{
			$data['header']['content']['length'] = $matches[1];
		}

		if (preg_match('@Content-Type: ([\w]+\/[\w-]+)(?|;(?| )?charset=(.+))?'.$lineBreak.'@', $data['header']['raw'], $matches))
		{
			$data['header']['content']['type'] = $matches[1];

			if (!empty($matches[2]))
			{
				$data['header']['content']['charset'] = $matches[2];
			}
		}

		if (preg_match('@Transfer-Encoding: (.+)'.$lineBreak.'@', $data['header']['raw'], $matches))
		{
			$data['header']['content']['encoding'] = $matches[1];

			if (($data['header']['content']['encoding'] == 'chunked') && ($this->autoUnchunk == true))
			{
				$oldBody = $data['body'];
				$newBody = '';

				while ((!empty($oldBody)) && (preg_match('@^([\dA-Fa-f]+)'.$lineBreak.'@', $oldBody, $matches) != false))
				{
					$chunkLength = hexdec($matches[1]);
					$oldBody = preg_replace('@^[\dA-Fa-f]+'.$lineBreak.'@', '', $oldBody, 1);
					$newBody .= substr($oldBody, 0, $chunkLength);
					$oldBody = substr($oldBody, $chunkLength + strlen($lineBreak));
				}

				$data['body'] = $newBody;
			}
		}

		if (preg_match('@Location: (.+)'.$lineBreak.'@', $data['header']['raw'], $matches))
		{
			//TODO: This regex probably still needs some work
			if (preg_match('@(?|(http(s)?):\/\/)?((?|[\w\d\.\-\_])*)(?|:((?|[\d])+))?(\/(?|[^\?])*)?(?|\?(.+)?)?@', $matches[1], $matches))
			{
				if (empty($matches[2]))
				{
					$data['header']['location']['ssl'] = false;
				}
				else
				{
					$data['header']['location']['ssl'] = true;
				}

				$data['header']['location']['protocol'] = $matches[1];
				$data['header']['location']['host'] = $matches[3];
				$data['header']['location']['port'] = $matches[4];
				$data['header']['location']['uri'] = $matches[5];

				if (!empty($matches[6]))
				{
					//TODO: Parse parameters
					$data['header']['location']['parameters'] = $matches[6];
				}
			}
		}

		//TODO: Test, maybe rewrite
		if (preg_match_all('@Set-Cookie: (.+)'.$lineBreak.'@', $data['header']['raw'], $matches))
		{
			$cookieStringList = $matches[1];

			foreach ($cookieStringList as $cookieString)
			{
				if (preg_match('@([^\=]+)=([^\;]*)(;.+)*@', $cookieString, $matches))
				{
					$data['header']['cookies'][$matches[1]] = array(
							'value'			=> $matches[2],
							'parameters'	=> array()
					);
					$parameters = explode($lineBreak, preg_replace('@;( )?@', $lineBreak, $matches[3]));

					foreach ($parameters as $parameter)
					{
						$parameterData = explode('=', $parameter);

						if (empty($parameterData[0]))
						{
							continue;
						}

						if (empty($parameterData[1]))
						{
							$data['header']['cookies'][$matches[1]]['parameters'][$parameterData[0]] = '';
						}
						else
						{
							$data['header']['cookies'][$matches[1]]['parameters'][$parameterData[0]] = $parameterData[1];
						}
					}
				}
			}
		}

		return $data;
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
	 * Create an open request of the specified type
	 * @param string $requestType
	 * @param string $uri
	 * @param array $getParameters
	 * @param array $cookies
	 * @param string $authUser
	 * @param string $authPassword
	 * @return string
	 */
	private function createOpenRequest(string $requestType = 'GET', string $uri = '/', array $getParameters = array(), array $cookies = array(), string $authUser = '', string $authPassword = ''): string
	{
		if (!preg_match('@^\/@', $uri))
		{
			$uri = '/'.$uri;
		}

		if (!empty($getParameters))
		{
			$getParamString = '?'.$this->createParamString($getParameters);
		}
		else
		{
			$getParamString = '';
		}

		$host = $this->httpHost;

		if ($this->httpPort != 80)
		{
			$host .= ':'.$this->httpPort;
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

		$request = $requestType.' '.$uri.$getParamString.' HTTP/1.1'."\r\n";
		$request .= 'Host: '.$host."\r\n";
		$request .= 'User-Agent: '.$this->userAgent."\r\n";

		if (!empty($cookies))
		{
			$request .= 'Cookie: '.$this->createParamString($cookies, '; ')."\r\n";
		}

		if (!empty($authUser))
		{
			$request .= 'Authorization: Basic '.base64_encode($authUser, ':'.$authPassword)."\r\n";
		}

		return $request;
	}

	/**
	 * Send and process the request.
	 * @param string $request
	 * @param string $content
	 * @return array
	 * @throws \Exception
	 */
	private function sendRequest(string $request, string $content = ''): array
	{
		$connected = $this->connect();

		if ($connected === false)
		{
			throw new \Exception('Not connected');
		}

		$requestString = $request.'Connection: close'."\r\n\r\n".$content;
		$this->write($requestString);
		$response = $this->read();
		$this->disconnect();
		return $this->decodeResponse($response);
	}

	/**
	 * Prepare and send the request.
	 * @param string $type
	 * @param string $uri
	 * @param array $getParameters
	 * @param array $cookies
	 * @param string $authUser
	 * @param string $authPassword
	 * @param array $postParameters
	 * @param array $fileParameters
	 * @param array $mimeTypes
	 * @return array
	 * @throws \Exception
	 */
	private function httpRequest(string $type, string $uri, array $getParameters, array $cookies, string $authUser, string $authPassword, array $postParameters = array(), array $fileParameters = array(), array $mimeTypes = array()): array
	{
		$request = $this->createOpenRequest($type, $uri, $getParameters, $cookies, $authUser, $authPassword);
		$content = '';

		if ((empty($fileParameters)) && (!empty($postParameters)))
		{
			$request .= 'Content-Type: application/x-www-form-urlencoded'."\r\n";
			$content .= $this->createParamString($postParameters);
		}
		else
		{
			$boundary = '-------------------------'.substr(md5(uniqid()), 0, 15);
			$request .= 'Content-Type: multipart/form-data; boundary='.$boundary."\r\n";

			foreach ($fileParameters as $key => $value)
			{
				$postFile = fopen($value,'r');
				$fileContent = fread($postFile,filesize($value));
				fclose($postFile);
				$content .= '--'.$boundary."\r\n";
				$content .= 'Content-Disposition: form-data; name="'.$key.'"; filename="'.$value.'"'."\r\n";

				if (!empty($mimeTypes[$key]))
				{
					$content .= 'Content-Type: '.$mimeTypes[$key]."\r\n\r\n";
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

		if (!empty($content))
		{
			$request .= 'Content-Length: '.strlen($content)."\r\n";
		}

		try
		{
			return $this->sendRequest($request, $content);
		}
		catch (\Exception $e)
		{
			throw new \Exception('Cannot send request', 0, $e);
		}
	}

	/**
	 * Send a HEAD request.
	 * @param string $uri
	 * @param array $getParameters
	 * @param array $cookies
	 * @param string $authUser
	 * @param string $authPassword
	 * @return array
	 * @throws \Exception
	 */
	public function head(string $uri = '/', array $getParameters = array(), array $cookies = array(), string $authUser = '', string $authPassword = ''): array
	{
		try
		{
			return $this->httpRequest('HEAD', $uri, $getParameters, $cookies, $authUser, $authPassword);
		}
		catch (\Exception $e)
		{
			throw new \Exception('Cannot send HEAD request', 0, $e);
		}
	}

	/**
	 * Send a GET request.
	 * @param string $uri
	 * @param array $getParameters
	 * @param array $cookies
	 * @param string $authUser
	 * @param string $authPassword
	 * @return array
	 * @throws \Exception
	 */
	public function get(string $uri = '/', array $getParameters = array(), array $cookies = array(), string $authUser = '', string $authPassword = ''): array
	{
		try
		{
			return $this->httpRequest('GET', $uri, $getParameters, $cookies, $authUser, $authPassword);
		}
		catch (\Exception $e)
		{
			throw new \Exception('Cannot send GET request', 0, $e);
		}
	}

	/**
	 * Send a POST request.
	 * @param string $uri
	 * @param array $getParameters
	 * @param array $postParameters
	 * @param array $cookies
	 * @param array $fileParameters
	 * @param array $mimeTypes
	 * @param string $authUser
	 * @param string $authPassword
	 * @return array
	 * @throws \Exception
	 */
	public function post(string $uri = '/', array $getParameters = array(), array $postParameters = array(), array $cookies = array(), array $fileParameters = array(), array $mimeTypes = array(), string $authUser = '', string $authPassword = ''): array
	{
		try
		{
			return $this->httpRequest('POST', $uri, $getParameters, $cookies, $authUser, $authPassword, $postParameters, $fileParameters, $mimeTypes);
		}
		catch (\Exception $e)
		{
			throw new \Exception('Cannot send POST request', 0, $e);
		}
	}

	/**
	 * Send a DELETE request.
	 * @param string $uri
	 * @param array $getParameters
	 * @param array $cookies
	 * @param string $authUser
	 * @param string $authPassword
	 * @return array
	 * @throws \Exception
	 */
	public function delete(string $uri = '/', array $getParameters = array(), array $cookies = array(), string $authUser = '', string $authPassword = ''): array
	{
		try
		{
			return $this->httpRequest('DELETE', $uri, $getParameters, $cookies, $authUser, $authPassword);
		}
		catch (\Exception $e)
		{
			throw new \Exception('Cannot send DELETE request', 0, $e);
		}
	}

	/**
	 * Send a PUT request.
	 * @param string $uri
	 * @param array $getParameters
	 * @param array $postParameters
	 * @param array $cookies
	 * @param array $fileParameters
	 * @param array $mimeTypes
	 * @param string $authUser
	 * @param string $authPassword
	 * @throws \Exception
	 * @return array
	 */
	public function put(string $uri = '/', array $getParameters = array(), array $postParameters = array(), array $cookies = array(), array $fileParameters = array(), array $mimeTypes = array(), string $authUser = '', string $authPassword = ''): array
	{
		try
		{
			return $this->httpRequest('PUT', $uri, $getParameters, $cookies, $authUser, $authPassword, $postParameters, $fileParameters, $mimeTypes);
		}
		catch (\Exception $e)
		{
			throw new \Exception('Cannot send PUT request', 0, $e);
		}
	}
}
?>
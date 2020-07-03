<?php
declare(strict_types=1);

namespace unrealization\PHPClassCollection\HTTPConnection\HTTPResponse;

class Header
{
	private $rawHeader		= '';
	private $httpStatus		= null;
	private $server			= '';
	private $contentInfo	= null;
	private $locationInfo	= null;
	private $cookies		= array();

	public function __construct($header)
	{
		$this->rawHeader = $header;
		$matches = array();

		if (preg_match('@^.*((?|\r)?\n)@Um', $header, $matches))
		{
			$lineBreak = $matches[1];
		}
		else
		{
			throw new \Exception('Unable to find line break.');
		}

		if (preg_match('@HTTP\/([\d]\.[\d]) ([\d]+) (.+)'.$lineBreak.'@', $header, $matches))
		{
			$this->httpStatus = new \unrealization\PHPClassCollection\HTTPConnection\HTTPResponse\Header\HTTPStatus($matches[1], (int)$matches[2], $matches[3]);
		}

		if (preg_match('@Server: (.+)'.$lineBreak.'@', $header, $matches))
		{
			$this->server = $matches[1];
		}

		if (preg_match('@Content-Length: ([\d]+)'.$lineBreak.'@', $header, $matches))
		{
			$contentLength = (int)$matches[1];
		}
		else
		{
			$contentLength = 0;
		}

		if (preg_match('@Content-Type: ([\w]+\/[\w-]+)(?|;(?| )?charset=(.+))?'.$lineBreak.'@', $header, $matches))
		{
			$contentType = $matches[1];

			if (!empty($matches[2]))
			{
				$contentCharset = $matches[2];
			}
			else
			{
				$contentCharset = '';
			}
		}
		else
		{
			$contentType = '';
			$contentCharset = '';
		}

		if (preg_match('@Transfer-Encoding: (.+)'.$lineBreak.'@', $header, $matches))
		{
			$transferEncoding = $matches[1];
		}
		else
		{
			$transferEncoding = '';
		}

		$this->contentInfo = new \unrealization\PHPClassCollection\HTTPConnection\HTTPResponse\Header\ContentInfo($contentLength, $contentType, $contentCharset, $transferEncoding);

		if (preg_match('@Location: (.+)'.$lineBreak.'@', $header, $matches))
		{
			//TODO: This regex probably still needs some work
			if (preg_match('@(?|(http(s)?):\/\/)?((?|[\w\d\.\-\_])*)(?|:((?|[\d])+))?(\/(?|[^\?])*)?(?|\?(.+)?)?@', $matches[1], $matches))
			{
				if (empty($matches[2]))
				{
					$ssl = false;
				}
				else
				{
					$ssl = true;
				}

				if (!empty($matches[4]))
				{
					$port = (int)$matches[4];
				}
				else
				{
					$port = null;
				}

				if (!empty($matches[6]))
				{
					//TODO: Parse parameters
					$parameters = $matches[6];
				}
				else
				{
					$parameters = '';
				}

				$this->locationInfo = new \unrealization\PHPClassCollection\HTTPConnection\HTTPResponse\Header\LocationInfo($matches[1], $matches[3], $port, $ssl, $matches[5], $parameters);
			}
		}

		//TODO: Test, maybe rewrite
		if (preg_match_all('@Set-Cookie: (.+)'.$lineBreak.'@', $header, $matches))
		{
			$cookieStringList = $matches[1];

			foreach ($cookieStringList as $cookieString)
			{
				if (preg_match('@([^\=]+)=([^\;]*)(;.+)*@', $cookieString, $matches))
				{
					$name = $matches[1];
					$value = $matches[2];
					$paramStringList = explode($lineBreak, preg_replace('@;( )?@', $lineBreak, $matches[3]));
					$parameters = array();

					foreach ($paramStringList as $paramString)
					{
						$parameterData = explode('=', $paramString);

						if (empty($parameterData[0]))
						{
							continue;
						}

						$parameter = array(
							'name'	=> $parameterData[0],
							'value'	=> ''
						);

						if (!empty($parameterData[1]))
						{
							$parameter['value'] = $parameterData[1];
						}

						$parameters[] = $parameter;
					}

					$cookie = new \unrealization\PHPClassCollection\HTTPConnection\HTTPResponse\Header\Cookie($name, $value, $parameters);
					$this->cookies[] = $cookie;
				}
			}
		}
	}

	public function getRawHeader(): string
	{
		return $this->rawHeader;
	}

	public function getHttpStatus(): \unrealization\PHPClassCollection\HTTPConnection\HTTPResponse\Header\HTTPStatus
	{
		return $this->httpStatus;
	}

	public function getServer(): string
	{
		return $this->server;
	}

	public function getContentInfo(): \unrealization\PHPClassCollection\HTTPConnection\HTTPResponse\Header\ContentInfo
	{
		return $this->contentInfo;
	}

	public function getLocationInfo()
	{
		return $this->locationInfo;
	}

	public function getCookies(): array
	{
		return $this->cookies;
	}
}
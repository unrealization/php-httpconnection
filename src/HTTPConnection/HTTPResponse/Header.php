<?php
declare(strict_types=1);

namespace unrealization\PHPClassCollection\HTTPConnection\HTTPResponse;

use unrealization\PHPClassCollection\HTTPConnection\HTTPResponse\Header\ContentInfo;
use unrealization\PHPClassCollection\HTTPConnection\HTTPResponse\Header\Cookie;
use unrealization\PHPClassCollection\HTTPConnection\HTTPResponse\Header\HTTPStatus;
use unrealization\PHPClassCollection\HTTPConnection\HTTPResponse\Header\LocationInfo;
use unrealization\PHPClassCollection\HTTPConnection\HTTPResponse;
use unrealization\PHPClassCollection\MbRegEx;

class Header
{
	private $rawHeader		= '';
	private $httpStatus		= null;
	private $server			= '';
	private $contentInfo	= null;
	private $locationInfo	= null;
	private $cookies		= array();

	public function __construct(string $header)
	{
		$this->rawHeader = $header;
		$lineBreak = HTTPResponse::detectLineBreak($header);

		if (!is_null($matches = MbRegEx::match('HTTP\/([\d]\.[\d]) ([\d]+) (.+)'.$lineBreak, $header)))
		{
			$this->httpStatus = new HTTPStatus($matches[1], (int)$matches[2], $matches[3]);
		}

		if (!is_null($matches = MbRegEx::match('Server: (.+)'.$lineBreak, $header)))
		{
			$this->server = $matches[1];
		}

		if (!is_null($matches = MbRegEx::match('Content-Length: ([\d]+)'.$lineBreak, $header)))
		{
			$contentLength = (int)$matches[1];
		}
		else
		{
			$contentLength = 0;
		}

		if (!is_null($matches = MbRegEx::match('Content-Type: ([\w]+\/[\w-]+)(;( )?charset=(.+))?'.$lineBreak, $header)))
		{
			$contentType = $matches[1];

			if ($matches[4] !== false)
			{
				$contentCharset = $matches[4];
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

		if (!is_null($matches = MbRegEx::search('Transfer-Encoding: (.+)'.$lineBreak, $header)))
		{
			$transferEncoding = $matches[1];
		}
		else
		{
			$transferEncoding = '';
		}

		$this->contentInfo = new ContentInfo($contentLength, $contentType, $contentCharset, $transferEncoding);

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

				$this->locationInfo = new LocationInfo($matches[1], $matches[3], $port, $ssl, $matches[5], $parameters);
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

					$cookie = new Cookie($name, $value, $parameters);
					$this->cookies[] = $cookie;
				}
			}
		}
	}

	public function getRawHeader(): string
	{
		return $this->rawHeader;
	}

	public function getHttpStatus(): HTTPStatus
	{
		return $this->httpStatus;
	}

	public function getServer(): string
	{
		return $this->server;
	}

	public function getContentInfo(): ContentInfo
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
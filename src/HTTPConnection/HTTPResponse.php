<?php
declare(strict_types=1);

namespace unrealization\PHPClassCollection\HTTPConnection;

use unrealization\PHPClassCollection\HTTPConnection\HTTPResponse\Header;
use unrealization\PHPClassCollection\MbRegEx;

class HTTPResponse
{
	private $rawResponse	= '';
	private $header			= null;
	private $body			= '';

	public static function detectLineBreak(string $content): string
	{
		$longBreak = MbRegEx::search('\r\n', $content);
		$shortBreak = MbRegEx::search('\n', $content);

		if ((!is_null($longBreak)) && (!is_null($shortBreak)))
		{
			if ($longBreak <= $shortBreak)
			{
				$lineBreak = "\r\n";
			}
			else
			{
				$lineBreak = "\n";
			}
		}
		elseif (!is_null($longBreak))
		{
			$lineBreak = "\r\n";
		}
		elseif (!is_null($shortBreak))
		{
			$lineBreak = "\n";
		}
		else
		{
			throw new \Exception('Unable to find line break.');
		}

		return $lineBreak;
	}

	public function __construct(string $response, bool $autoUnchunk = true)
	{
		$this->rawResponse = $response;
		$lineBreak = self::detectLineBreak($response);

		if (!is_null($matches = MbRegEx::match('^((.+)('.$lineBreak.'){2})(.+)?$', $response, 'm')))
		{
			$header = $matches[2].$lineBreak;
			$this->header = new Header($header);

			if (!empty($matches[4]))
			{
				$body = $matches[4];

				if (($this->header->getContentInfo()->getTransferEncoding() === 'chunked') && ($autoUnchunk === true))
				{
					$encoding = mb_detect_encoding($body, mb_list_encodings());
					mb_regex_encoding($encoding);
					$chunkedBody = $body;
					$body = '';

					while ((!empty($chunkedBody)) && (!is_null($matches = MbRegEx::match('^([\dA-Fa-f]+)'.$lineBreak, $chunkedBody))))
					{
						$chunkLength = hexdec($matches[1]);
						$chunkedBody = mb_ereg_replace('^'.$matches[1].$lineBreak, '', $chunkedBody);
						$body .= mb_substr($chunkedBody, 0, $chunkLength);
						$chunkedBody = mb_substr($chunkedBody, $chunkLength + mb_strlen($lineBreak));
					}
				}

				$this->body = $body;
			}
		}
		else
		{
			throw new \Exception('Unable to split header and body.');
		}
	}

	public function getRawResponse(): string
	{
		return $this->rawResponse;
	}

	public function getHeader(): Header
	{
		return $this->header;
	}

	public function getBody(): string
	{
		return $this->body;
	}
}
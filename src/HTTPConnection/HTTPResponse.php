<?php
declare(strict_types=1);

namespace unrealization\PHPClassCollection\HTTPConnection;

use unrealization\PHPClassCollection\HTTPConnection\HTTPResponse\Header;

class HTTPResponse
{
	private $rawResponse	= '';
	private $header			= null;
	private $body			= '';

	public function __construct(string $response, bool $autoUnchunk = true)
	{
		$encoding = mb_detect_encoding($response, mb_list_encodings());
		$response = mb_convert_encoding($response, 'UTF-8', $encoding);
		$this->rawResponse = $response;
		$matches = array();
		mb_regex_set_options('md');

		if (mb_ereg('^.*((\r)?\n)', $response, $matches))
		{
			$lineBreak = $matches[1];
		}
		else
		{
			throw new \Exception('Unable to find line break.');
		}

		if (mb_ereg('^((.+)('.$lineBreak.'){2})(.+)?$', $response, $matches))
		{
			$header = $matches[2].$lineBreak;
			$this->header = new Header($header);

			if (!empty($matches[4]))
			{
				$body = $matches[4];

				if (($this->header->getContentInfo()->getTransferEncoding() === 'chunked') && ($autoUnchunk === true))
				{
					$chunkedBody = $body;
					$body = '';

					/**
					 * TODO: Check !==
					 */
					while ((!empty($chunkedBody)) && (mb_ereg('^([\dA-Fa-f]+)'.$lineBreak, $chunkedBody, $matches)))
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
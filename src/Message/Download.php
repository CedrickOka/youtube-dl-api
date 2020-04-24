<?php
namespace App\Message;

/**
 *
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 *
 */
class Download
{
	private $url;
	private $requestContent;
	
	public function __construct(string $url, array $requestContent = [])
	{
		$this->url = $url;
		$this->requestContent = $requestContent;
	}
	
	public function getUrl() :string
	{
		return $this->url;
	}
	
	public function getRequestContent() :array
	{
		return $this->requestContent;
	}
}

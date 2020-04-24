<?php
namespace App\MessageHandler;

use App\Message\Download;
use App\Service\DownloadManager;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

/**
 * 
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 * 
 */
class DownloadHandler implements MessageHandlerInterface
{
    private $downloadManager;
	
	public function __construct(DownloadManager $downloadManager)
	{
	    $this->downloadManager = $downloadManager;
	}
	
	public function __invoke(Download $message)
	{
	    $requestContent = $message->getRequestContent();
	    unset($requestContent['url']);
	    
	    $this->downloadManager->execute($message->getUrl(), $requestContent);
	}
}

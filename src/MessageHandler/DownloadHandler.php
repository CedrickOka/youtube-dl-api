<?php
namespace App\MessageHandler;

use App\Command\DownloadCommand;
use App\Message\Download;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

/**
 * 
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 * 
 */
class DownloadHandler implements MessageHandlerInterface
{
	/**
	 * @var string $projectDir
	 */
	private $projectDir;
	
	/**
	 * @var LoggerInterface $logger
	 */
	private $logger;
	
	public function __construct(string $projectDir, LoggerInterface $logger) {
		$this->projectDir = $projectDir;
		$this->logger = $logger;
	}
	
	public function __invoke(Download $message) {
		$options = [];
		$requestContent = $message->getRequestContent();
		
		if (true === isset($requestContent['extractAudio'])) {
			$options[] = '-x';
			$options[] = sprintf('--audio-format=%s', $requestContent['audioFormat'] ?? 'mp3');
		}
		
		if (true === isset($requestContent['redirectUrl'])) {
			$options[] = sprintf('--redirect-url="%s"', $requestContent['redirectUrl']);
		}
		
		$this->logger->info(sprintf('URL downloading "%s" has starting.', $message->getUrl()), $requestContent);
		
		shell_exec(sprintf('php %s/bin/console %s %s %s &', $this->projectDir, DownloadCommand::getDefaultName(), implode(' ', $options), escapeshellarg($message->getUrl())));
	}
}
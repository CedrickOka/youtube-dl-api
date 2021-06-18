<?php

namespace App\Service;

use App\Command\WebhookCommand;
use Psr\Log\LoggerInterface;

/**
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 */
class DownloadManager
{
    protected $webhookManager;
    protected $logger;
    protected $binDir;
    protected $unixOwner;

    public function __construct(WebhookManager $webhookManager, LoggerInterface $logger, string $binDir, string $unixOwner = null)
    {
        $this->webhookManager = $webhookManager;
        $this->logger = $logger;
        $this->binDir = $binDir;
        $this->unixOwner = $unixOwner;
    }

    public function execute(string $url, array $options = [])
    {
        if ($diff = array_diff(array_keys($options), ['proxyUrl', 'eventUrl', 'extractAudio', 'audioFormat', 'unixOwner'])) {
            throw new \InvalidArgumentException(sprintf('The following configuration are not supported "%s".', implode(', ', $diff)));
        }

        $context = array_merge(['url' => $url], $options);
        $this->logger->info('The download of the URL "{url}" has started.', $context);

        $youtubeDlOptions = [];
        $commands = ['chmod -R 0755 {}'];
        $escapedUrl = escapeshellarg($url);

        if (true === isset($options['unixOwner']) || null !== $this->unixOwner) {
            $commands[] = sprintf('chown -R %1$s:%1$s {}', $options['unixOwner'] ?? $this->unixOwner);
        }
        if (true === isset($options['eventUrl'])) {
            $commands[] = sprintf('php %s/console %s --eventType=DOWNLOAD.SUCCESSFULLY --sourceUrl=%s --filename="{}" %s', $this->binDir, WebhookCommand::getDefaultName(), $escapedUrl, escapeshellarg($options['eventUrl']));
        }

        if (true === isset($options['proxyUrl'])) {
            $youtubeDlOptions[] = sprintf('--proxy %s', escapeshellarg($options['proxyUrl']));
        }
        if (true === ($options['extractAudio'] ?? false) && true === isset($options['audioFormat'])) {
            $youtubeDlOptions[] = '-x';
            $youtubeDlOptions[] = sprintf('--audio-format %s', $options['audioFormat']);
        }

        $youtubeDlOptions[] = sprintf('--exec \'%s\'', implode(' && ', $commands));
        $output = $status = null;

        exec(sprintf('youtube-dl -f best --audio-quality 0 --restrict-filenames --yes-playlist %s %s', implode(' ', $youtubeDlOptions), $escapedUrl), $output, $status);
        $this->logger->info('The download of the URL "{url}" has been executed by the "youtube-dl" programm with status [{status}].', array_merge(['output' => $output, 'status' => $status], $context));

        if (((int) $status) > 0 && true === isset($options['eventUrl'])) {
            $this->webhookManager->send($options['eventUrl'], 'DOWNLOAD.FAILED', '1', $url);
        }

        $this->logger->info('The download of the URL "{url}" is completed.', $context);
    }
}

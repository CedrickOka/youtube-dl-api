<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;

/**
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 */
class WebhookManager
{
    protected $httpClient;
    protected $logger;

    public function __construct(Client $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    public function send(string $url, string $eventType, string $eventVersion, string $sourceUrl, string $filename = null)
    {
        $body = [
            'createdAt' => date('c'),
            'eventType' => $eventType,
            'eventVersion' => $eventVersion,
            'resource' => [
                'sourceUrl' => $sourceUrl,
            ],
        ];

        if (null !== $filename) {
            $body['resource']['filename'] = basename($filename);

            if (true === file_exists($filename)) {
                $body['resource']['size'] = (int) @filesize($filename);
            }
        }

        try {
            $this->httpClient->post($url, [RequestOptions::JSON => $body]);
            $this->logger->info('Instant Download Notification has been sended on URL "{url}".', ['url' => $url, 'body' => $body]);
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                '%s: %s (uncaught exception) at %s line %s',
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
           ), ['url' => $url, 'body' => $body]);
        }
    }
}

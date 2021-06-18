<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 */
class DownloadControllerTest extends WebTestCase
{
    protected $client;

    public function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function tearDown(): void
    {
        $this->client = null;
    }

    public function testThatWeCanCreateDownload()
    {
        $busMock = $this->createMock(MessageBusInterface::class);
        $busMock
        ->method('dispatch')
        ->withAnyParameters()
        ->willReturn(new Envelope(new \stdClass()));

        $this->client->getContainer()->set('message_bus', $busMock);
        $this->client->getContainer()->set('messenger.default_bus', $busMock);

        $this->client->request('POST', '/v1/rest/downloads', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Accept' => 'application/json',
        ], '{"url": "https://www.youtube.com/watch?v=CEXKUyUukoA"}');

        $this->assertResponseStatusCodeSame(204);
    }

    public function testThatWeCanListDownload()
    {
        $this->client->request('GET', sprintf('/v1/rest/downloads', ''));
        $content = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseStatusCodeSame(200);
        $this->assertGreaterThanOrEqual(1, count($content));
    }
}

<?php
namespace App\Controller;

use Oka\ApiBundle\Annotation\AccessControl;
use Oka\ApiBundle\Annotation\RequestContent;
use Oka\ApiBundle\Service\ErrorResponseFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 *
 * @author Cedrick Oka Baidai <baidai.cedric@veone.net>
 *
 */
class DownloadController extends AbstractController
{
	
	/**
	 * @var ErrorResponseFactory $errorFactory
	 */
	protected $errorFactory;
	
	public function __construct(ErrorResponseFactory $errorFactory) {
		$this->errorFactory = $errorFactory;
	}
	
	/**
	 * Create a download
	 * 
	 * @param Request $request
	 * @param string $version
	 * @param string $protocol
	 * @param string $format
	 * @param array $requestContent
	 * @Route(name="app_create_downloads", path="/downloads", methods="POST")
	 * @AccessControl(version="v1", protocol="rest", formats="json")
	 * @RequestContent(constraints="itemConstraints", enable_validation=false)
	 */
	public function create(Request $request, $version, $protocol, $format, array $requestContent, EventDispatcherInterface $dispatcher) {
		if (!shell_exec('command -v youtube-dl')) {
			return $this->errorFactory->create($this->get('translator')->trans('download.dependency_failed', [], 'app'), 424, null, [], 424);
		}
		
		$dispatcher->addListener(KernelEvents::TERMINATE, function(PostResponseEvent $event) use ($requestContent) {
			$options = [];
			$commands = ['chmod -R 0755 {}'];
			
			if (true === isset($requestContent['extractAudio'])) {
				$options[] = '-x';
				$options[] = sprintf('--audio-format %s', $requestContent['audioFormat'] ?? 'mp3');
			}
			
			if (true === isset($requestContent['redirectUrl'])) {
				$commands[] = sprintf('curl -X POST %s -H \'Accept: application/json\' -H \'Content-Type: application/json\' -d \'{"filename": "{}"}\'', $requestContent['redirectUrl']);
			}
			
			shell_exec(sprintf('youtube-dl -f best --audio-quality 0 %s --exec \'%s\' %s >> /dev/stdout 2>> /dev/stderr &', implode(' ', $options), implode(' && ', $commands), $requestContent['url']));
		});
		
		return new Response(null, 204, ['Content-Type' => 'application/json']);
	}
	
	/**
	 * Get file downloaded
	 * 
	 * @param Request $request
	 * @param string $version
	 * @param string $protocol
	 * @param string $format
	 * @param array $requestContent
	 * @Route(name="app_read_downloads", path="/downloads/{filename}", methods={"GET", "HEAD"}, requirements={"filename": ".+"})
	 * @AccessControl(version="v1", protocol="rest", formats="json")
	 */
	public function read(Request $request, $version, $protocol, $format, string $filename) {
		if (false === file_exists($filename)) {
			return $this->errorFactory->create($this->get('translator')->trans('download.file.not_found', ['%address%' => $filename], 'app'), 404, null, [], 404);
		}
		
		if (true === $request->isMethod('HEAD')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			
			return new Response(null, 204, ['Content-Type' => filesize($filename), 'Content-Length' => finfo_file($finfo, $filename)]);
		}
		
		$response = new BinaryFileResponse($filename, 200, [], ResponseHeaderBag::DISPOSITION_ATTACHMENT);
		
		if (true === $request->query->has('deleteFileAfterSend')) {
			$response->deleteFileAfterSend(true);
		}
		
		return $response;
	}
	
	public static function getSubscribedServices() {
		return array_merge(parent::getSubscribedServices(), ['translator' => '?'.TranslatorInterface::class]);
	}
	
	private static function itemConstraints() :Assert\Collection {
		return new Assert\Collection([
				'url' => new Assert\Required([new Assert\NotBlank(), new Assert\NotNull()]),
				'extractAudio' => new Assert\Optional(new Assert\Type(['type' => 'boolean'])),
				'audioFormat' => new Assert\Optional(new Assert\Choice(['choices' => ['best', 'aac', 'flac', 'mp3', 'm4a', 'opus', 'vorbis', 'wav']])),
				'redirectUrl' => new Assert\Optional(new Assert\Url())
		]);
	}
}

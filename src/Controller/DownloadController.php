<?php
namespace App\Controller;

use App\Command\DownloadCommand;
use Oka\ApiBundle\Annotation\AccessControl;
use Oka\ApiBundle\Annotation\RequestContent;
use Oka\ApiBundle\Service\ErrorResponseFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

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
	 * @RequestContent(constraints="itemConstraints")
	 */
	public function create(Request $request, $version, $protocol, $format, array $requestContent, EventDispatcherInterface $dispatcher) {
		if (!shell_exec('command -v youtube-dl')) {
			return $this->errorFactory->create($this->get('translator')->trans('download.dependency_failed', [], 'app'), 424, null, [], 424);
		}
		
		if (true === $request->query->has('simulate')) {
			$output = shell_exec(sprintf('youtube-dl -s -j --no-warnings "%s"', $requestContent['url']));
			
			return new JsonResponse(trim($output), 201);
		}
		
		$dispatcher->addListener(KernelEvents::TERMINATE, function(PostResponseEvent $event) use ($requestContent) {
			$options = [];
			
			if (true === isset($requestContent['extractAudio'])) {
				$options[] = '-x';
				$options[] = sprintf('--audio-format=%s', $requestContent['audioFormat'] ?? 'mp3');
			}
			if (true === isset($requestContent['redirectUrl'])) {
				$options[] = sprintf('--redirect-url="%s"', $requestContent['redirectUrl']);
			}
			
			shell_exec(sprintf('php %s/bin/console %s %s "%s" >> /dev/stdout 2>&1 &', $this->getParameter('kernel.project_dir'), DownloadCommand::getDefaultName(), implode(' ', $options), $requestContent['url']));
		});
		
		return new JsonResponse(null, 204);
	}
	
	/**
	 * List files downloaded
	 *
	 * @param Request $request
	 * @param string $version
	 * @param string $protocol
	 * @param string $format
	 * @param array $requestContent
	 * @Route(name="app_list_downloads", path="/downloads", methods="GET")
	 * @AccessControl(version="v1", protocol="rest", formats="json")
	 */
	public function list(Request $request, $version, $protocol, $format) {
		if (!$resource = @opendir($this->getParameter('assets_dir'))) {
			return $this->errorFactory->create($this->get('translator')->trans('download.unexpected_error', [], 'app'), 500, null, [], 500);
		}
		
		$files = [];
		
		while (false !== ($entry = readdir($resource))) {
			if ('.' === $entry || '..' === $entry) {
				continue;
			}
			
			$path = sprintf('%s/%s', $this->getParameter('assets_dir'), $entry);
			$files[] = [
					'name' => basename($entry),
					'directory' => is_dir($path),
					'size' => (int) @filesize($path)
			];
		}
		closedir($resource);
		
		return new JsonResponse($files, 200);
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
		$path = sprintf('%s/%s', $this->getParameter('assets_dir'), $filename);
		
		if (false === file_exists($path)) {
			return $this->errorFactory->create($this->get('translator')->trans('download.file.not_found', ['%filename%' => $filename], 'app'), 404, null, [], 404);
		}
		
		if (true === $request->isMethod('HEAD')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			
			return new Response(null, 204, ['Content-Type' => filesize($path), 'Content-Length' => finfo_file($finfo, $path)]);
		}
		
		$response = new BinaryFileResponse($path, 200, [], ResponseHeaderBag::DISPOSITION_ATTACHMENT);
		
		if (true === $request->query->has('deleteFileAfterSend')) {
			$response->deleteFileAfterSend(true);
		}
		
		return $response;
	}
	
	/**
	 * Delete file downloaded
	 *
	 * @param Request $request
	 * @param string $version
	 * @param string $protocol
	 * @param string $format
	 * @param array $requestContent
	 * @Route(name="app_delete_downloads", path="/downloads/{filename}", methods="DELETE", requirements={"filename": ".+"})
	 * @AccessControl(version="v1", protocol="rest", formats="json")
	 */
	public function delete(Request $request, $version, $protocol, $format, string $filename) {
		$path = sprintf('%s/%s', $this->getParameter('assets_dir'), $filename);
		
		if (false === file_exists($path)) {
			return $this->errorFactory->create($this->get('translator')->trans('download.file.not_found', ['%filename%' => $filename], 'app'), 404, null, [], 404);
		}
		
		if (false === unlink($path)) {
			return $this->errorFactory->create($this->get('translator')->trans('download.unexpected_error', [], 'app'), 400, null, [], 400);
		}
		
		return new JsonResponse(null, 204);
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

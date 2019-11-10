<?php
namespace App\Controller;

use App\Message\Download;
use Oka\RESTRequestValidatorBundle\Annotation\AccessControl;
use Oka\RESTRequestValidatorBundle\Annotation\RequestContent;
use Oka\RESTRequestValidatorBundle\Service\ErrorResponseFactory;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 *
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 *
 */
class DownloadController extends AbstractController
{
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
	public function create(Request $request, $version, $protocol, $format, array $requestContent, EventDispatcherInterface $dispatcher, LoggerInterface $logger) {
		if (!shell_exec('command -v youtube-dl')) {
			return $this->get('oka_rest_request_validator.error_response.factory')->create($this->get('translator')->trans('download.dependency_failed', [], 'app'), 424, null, [], 424);
		}
		
		if (true === $request->query->has('simulate')) {
			if ($output = shell_exec(sprintf('youtube-dl -s -j --no-warnings "%s"', $requestContent['url']))) {
				$output = trim($output);
				
				if (!preg_match('#^(.*)ERROR:(.*)$#i', $output)) {
					return new Response($output, 200, ['Content-Type' => 'application/json']);
				}
			}
			
			return new JsonResponse(['error' => ['message' => $output ?? 'Bad URL.']], 400);
		}
		
		/** @var \Symfony\Component\Messenger\MessageBusInterface $bus */
		$this->get('message_bus')->dispatch(new Download($requestContent['url'], $requestContent));
		
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
			return $this->get('oka_rest_request_validator.error_response.factory')->create($this->get('translator')->trans('download.unexpected_error', [], 'app'), 500, null, [], 500);
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
			return $this->get('oka_rest_request_validator.error_response.factory')->create($this->get('translator')->trans('download.file.not_found', ['%filename%' => $filename], 'app'), 404, null, [], 404);
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
			return $this->get('oka_rest_request_validator.error_response.factory')->create($this->get('translator')->trans('download.file.not_found', ['%filename%' => $filename], 'app'), 404, null, [], 404);
		}
		
		if (false === unlink($path)) {
			return $this->get('oka_rest_request_validator.error_response.factory')->create($this->get('translator')->trans('download.unexpected_error', [], 'app'), 400, null, [], 400);
		}
		
		return new JsonResponse(null, 204);
	}
	
	public static function getSubscribedServices() {
		return array_merge(parent::getSubscribedServices(), [
				'translator' => '?'.TranslatorInterface::class,
				'oka_rest_request_validator.error_response.factory' => '?'.ErrorResponseFactory::class
		]);
	}
	
	private static function itemConstraints() :Assert\Collection {
		return new Assert\Collection([
				'url' => new Assert\Required([new Assert\Url()]),
				'redirectUrl' => new Assert\Optional(new Assert\Url()),
				'extractAudio' => new Assert\Optional(new Assert\Type(['type' => 'boolean'])),
				'audioFormat' => new Assert\Optional(new Assert\Choice(['choices' => ['best', 'aac', 'flac', 'mp3', 'm4a', 'opus', 'vorbis', 'wav']]))
		]);
	}
}

<?php
namespace App\Controller;

use App\Message\Download;
use Oka\RESTRequestValidatorBundle\Annotation\AccessControl;
use Oka\RESTRequestValidatorBundle\Annotation\RequestContent;
use Oka\RESTRequestValidatorBundle\Service\ErrorResponseFactory;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
 * @Route(name="app_downloads_", path="/downloads", requirements={"filename": ".+"})
 */
class DownloadController extends AbstractController
{
	/**
	 * Create a download
	 * 
	 * @param string $version
	 * @param string $protocol
	 * @param string $format
	 * @Route(name="create", methods="POST")
	 * @AccessControl(version="v1", protocol="rest", formats="json")
	 * @RequestContent(constraints="createConstraints")
	 * @SWG\Parameter(name="url", type="string", in="formData", required=true)
	 * @SWG\Parameter(name="eventUrl", type="string", in="formData")
	 * @SWG\Parameter(name="extractAudio", type="boolean", in="formData")
	 * @SWG\Parameter(name="audioFormat", enum={"best", "aac", "flac", "mp3", "m4a", "opus", "vorbis", "wav"}, type="string", in="formData")
	 * @SWG\Parameter(name="simulate", type="string", in="query")
	 * @SWG\Response(response="204", description="No Content")
	 * @SWG\Tag(name="downloads")
	 */
	public function create(Request $request, $version, $protocol, $format, array $requestContent) :Response
	{
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
	 * @param string $version
	 * @param string $protocol
	 * @param string $format
	 * @Route(name="list", methods="GET")
	 * @AccessControl(version="v1", protocol="rest", formats="json")
	 * @SWG\Response(response="200", description="List files downloaded")
	 * @SWG\Response(response="206", description="Partial List files downloaded")
	 * @SWG\Tag(name="downloads")
	 */
	public function list(Request $request, $version, $protocol, $format) :Response
	{
		if (!$resource = @opendir($this->getParameter('assets_dir'))) {
			return $this->get('oka_rest_request_validator.error_response.factory')->create($this->get('translator')->trans('download.unexpected_error', [], 'app'), 500, null, [], 500);
		}
		
		$files = [];
		
		while (false !== ($entry = readdir($resource))) {
			if ('.' === $entry || '..' === $entry) {
				continue;
			}
			
			$path = $this->getPath($entry);
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
	 * @param string $version
	 * @param string $protocol
	 * @param string $format
	 * @param string $filename
	 * @Route(name="read", path="/{filename}", methods={"GET", "HEAD"})
	 * @AccessControl(version="v1", protocol="rest", formats="json")
	 * @SWG\Parameter(name="filename", type="string", in="path", required=true, description="The file name")
	 * @SWG\Parameter(name="deleteFileAfterSend", type="boolean", in="query", description="Allows to indicate if it is necessary to delete the file after its download")
	 * @SWG\Response(response="200", description="Download the file content")
	 * @SWG\Response(
	 *     response="204", 
	 *     headers={
	 *         @SWG\Header(header="Content-Type", type="string", description="The file mimetype"),
	 *         @SWG\Header(header="Content-Length", type="integer", description="The file content length")
	 *     }, 
	 *     description="Get file information"
	 * )
	 * @SWG\Tag(name="downloads")
	 */
	public function read(Request $request, $version, $protocol, $format, string $filename) :Response
	{
	    $path = $this->getPath($filename);
	    
		if (false === file_exists($path)) {
			return $this->get('oka_rest_request_validator.error_response.factory')->create($this->get('translator')->trans('download.file.not_found', ['%filename%' => $filename], 'app'), 404, null, [], 404);
		}
		
		if (true === $request->isMethod('HEAD')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			
			return new Response(null, 204, [
			    'Content-Type' => $finfo ? finfo_file($finfo, $path) : 'application/octet-stream', 
			    'Content-Length' => (int) @filesize($path)
			]);
		}
		
		$response = new BinaryFileResponse($path, 200, [], ResponseHeaderBag::DISPOSITION_ATTACHMENT);
		$response->deleteFileAfterSend($request->query->has('deleteFileAfterSend'));
		
		return $response;
	}
	
	/**
	 * Delete file downloaded
	 *
	 * @param string $version
	 * @param string $protocol
	 * @param string $format
	 * @param string $filename
	 * @Route(name="delete", path="/{filename}", methods="DELETE")
	 * @AccessControl(version="v1", protocol="rest", formats="json")
	 * @SWG\Parameter(name="filename", type="string", in="path", required=true, description="The file name")
	 * @SWG\Response(response="200", description="Delete the file downloaded")
	 * @SWG\Tag(name="downloads")
	 */
	public function delete(Request $request, $version, $protocol, $format, string $filename) :Response
	{
	    $path = $this->getPath($filename);
		
		if (false === file_exists($path)) {
			return $this->get('oka_rest_request_validator.error_response.factory')->create($this->get('translator')->trans('download.file.not_found', ['%filename%' => $filename], 'app'), 404, null, [], 404);
		}
		
		if (false === @unlink($path)) {
		    return $this->get('oka_rest_request_validator.error_response.factory')->create($this->get('translator')->trans('download.unexpected_error', [], 'app'), 500, null, [], 500);
		}
		
		return new JsonResponse(null, 204);
	}
	
	public static function getSubscribedServices()
	{
		return array_merge(parent::getSubscribedServices(), [
			'translator' => '?'.TranslatorInterface::class,
			'oka_rest_request_validator.error_response.factory' => '?'.ErrorResponseFactory::class
		]);
	}
	
	private function getPath(string $filename) :string
	{
	    return sprintf('%s/%s', $this->getParameter('assets_dir'), $filename);
	}
	
	private static function createConstraints() :Assert\Collection
	{
		return new Assert\Collection([
		    'url' => new Assert\Required([new Assert\Url()]),
		    'proxyUrl' => new Assert\Optional(new Assert\Url()),
		    'eventUrl' => new Assert\Optional(new Assert\Url()),
			'extractAudio' => new Assert\Optional(new Assert\Type(['type' => 'boolean'])),
			'audioFormat' => new Assert\Optional(new Assert\Choice(['choices' => ['best', 'aac', 'flac', 'mp3', 'm4a', 'opus', 'vorbis', 'wav']]))
		]);
	}
}

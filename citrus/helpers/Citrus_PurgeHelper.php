<?php
namespace Craft;

class Citrus_PurgeHelper
{
	use Citrus_BaseHelper;

	public function purge(array $uri, $debug = false)
	{
		$response = array();

		foreach ($this->getUrls($uri) as $url) {
			array_push(
				$response,
				$this->sendPurge(
					$url['hostId'],
					$url['hostName'],
					$url['url'],
					$debug
				)
			);
		}

		return $response;
	}

	private function sendPurge($id, $host, $url, $debug = false)
	{

		$response = new Citrus_ResponseHelper(
			Citrus_ResponseHelper::CODE_OK
		);

		$client = new \Guzzle\Http\Client();
		$client->setDefaultOption('headers/Accept', '*/*');
		$headers = array(
			'Host' => $host
		);

		CitrusPlugin::log(
			'Adding "' . $id . '" url to purge: ' . $url,
			LogLevel::Info,
			craft()->citrus->getSetting('logAll'),
			$debug
		);

		$request = $client->createRequest('PURGE', $url, $headers);

		try {
			$httpResponse = $request->send();
			return $this->parseGuzzleResponse($request, $httpResponse, true);
		} catch (\Guzzle\Http\Exception\BadResponseException $e) {
			return $this->parseGuzzleError($id, $e, $debug);
		} catch (\Guzzle\Http\Exception\CurlException $e) {
			return $this->parseGuzzleError($id, $e, $debug);
		} catch (Exception $e) {
			return $this->parseGuzzleError($id, $e, $debug);
		}
	}
}

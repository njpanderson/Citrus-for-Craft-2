<?php
namespace Craft;

use \njpanderson\VarnishConnect;

class Citrus_BanHelper
{
	use Citrus_BaseHelper;

	private $_socket = array();

	const BAN_PREFIX = 'req.http.host == ${hostname} && req.url ~ ';

	public function ban(array $ban, $debug = false)
	{
		$response = array();

		foreach ($this->getVarnishHosts() as $id => $host) {
			if ($id === $ban['hostId'] || $ban['hostId'] === null) {
				if ($host['canDoAdminBans']) {
					array_push(
						$response,
						$this->sendAdmin($id, $host, $ban['query'], $ban['full'], $debug)
					);
				} else {
					array_push(
						$response,
						$this->sendHTTP($id, $host, $ban['query'], $ban['full'], $debug)
					);
				}
			}
		}

		return $response;
	}

	private function sendHTTP($id, $host, $query, $isFullQuery = false, $debug = false)
	{
		$response = new Citrus_ResponseHelper(
			Citrus_ResponseHelper::CODE_OK
		);

		$client = new \Guzzle\Http\Client();
		$client->setDefaultOption('headers/Accept', '*/*');

		$banQueryHeader = craft()->citrus->getSetting('banQueryHeader');
		$headers = array(
			'Host' => $host['hostName']
		);

		$banQuery = $this->_parseBan($host, $query, $isFullQuery);

		$headers[$banQueryHeader] = $banQuery;

		CitrusPlugin::log(
			"Sending BAN query to '{$host['url'][craft()->language]}': '{$banQuery}'",
			LogLevel::Info,
			craft()->citrus->getSetting('logAll'),
			$debug
		);

		// Ban requests always go to / but with a header determining the ban query
		$request = $client->createRequest(
			'BAN',
			$host['url'][craft()->language],
			$headers
		);

		try {
			$httpResponse = $request->send();
			return $this->parseGuzzleResponse($request, $httpResponse);
		} catch (\Guzzle\Http\Exception\BadResponseException $e) {
			return $this->parseGuzzleError($id, $e, $debug);
		} catch(\Guzzle\Http\Exception\CurlException $e) {
			return $this->parseGuzzleError($id, $e, $debug);
		} catch(Exception $e) {
			return $this->parseGuzzleError($id, $e, $debug);
		}
	}

	private function sendAdmin($id, $host, $query, $isFullQuery = false, $debug = false) {
		$response = new Citrus_ResponseHelper(
			Citrus_ResponseHelper::CODE_OK
		);

		try {
			$socket = $this->_getSocket($host['adminIP'], $host['adminPort'], $host['adminSecret']);

			$banQuery = $this->_parseBan($host, $query, $isFullQuery);

			CitrusPlugin::log(
				"Adding BAN query to '{$host['adminIP']}': {$banQuery}",
				LogLevel::Info,
				craft()->citrus->getSetting('logAll'),
				$debug
			);

			$result = $socket->addBan($banQuery);

			if ($result !== true) {
				if ($result !== null) {
					$response->code = $result['code'];
					$response->message = "Ban error: {$result['code']} - '" .
						join($result['message'], '" "') .
						"'";

					CitrusPlugin::log(
						$response->message,
						LogLevel::Error,
						true,
						$debug
					);
				} else {
					$response->code = Citrus_ResponseHelper::CODE_ERROR_GENERAL;
					$response->message = "Ban error: could not send to '{$host['adminIP']}'";

					CitrusPlugin::log(
						$response->message,
						LogLevel::Error,
						true,
						$debug
					);
				}
			} else {
				$response->message = sprintf('BAN "%s" added successfully', $banQuery);
			}
		} catch(\Exception $e) {
			$response->code = Citrus_ResponseHelper::CODE_ERROR_GENERAL;
			$response->message = 'Ban error: ' . $e->getMessage();

			CitrusPlugin::log(
				$response->message,
				LogLevel::Error,
				true,
				$debug
			);
		}

		return $response;
	}

	private function _getSocket($ip, $port, $secret) {
		if (isset($this->_socket[$ip])) {
			return $this->_socket[$ip];
		}

		$this->_socket[$ip] = new VarnishConnect\Socket(
			$ip,
			$port,
			$secret
		);

		$this->_socket[$ip]->connect();

		return $this->_socket[$ip];
	}

	private function _parseBan($host, $query, $isFullQuery = false) {
		if (!$isFullQuery) {
			$query = self::BAN_PREFIX . $query;
		}

		$find = ['${hostname}'];
		$replace = [$host['hostName']];

		foreach (craft()->i18n->getEditableLocales() as $locale) {
			array_push($find, '${baseUrl-' . $locale->id . '}');

			if (isset($host['url'][$locale->id])) {
				array_push($replace, $host['url'][$locale->id]);
			}
		}

		// run through parsing steps
		$query = str_replace($find, $replace, $query);
		$query = str_replace('\\', '\\\\', $query);

		return $query;
	}
}
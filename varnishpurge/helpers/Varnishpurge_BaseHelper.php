<?php
namespace Craft;

trait Varnishpurge_BaseHelper
{
   public $hashAlgo = 'crc32';

	public function getPostWithDefault($var, $default = null) {
		$value = craft()->request->getPost($var);
		return (!empty($value) ? $value : $default);
	}

   protected function hash($str)
	{
      return hash($this->hashAlgo, $str);
   }

	protected function uuid()
	{
		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),

			// 16 bits for "time_mid"
			mt_rand(0, 0xffff),

			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand(0, 0x0fff) | 0x4000,

			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand(0, 0x3fff) | 0x8000,

			// 48 bits for "node"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}

	protected function getUrls(array $uri)
	{
		$urls = array();
		$hosts = $this->getVarnishHosts();

		// Sanity check uri
		$uri['uri'] = $uri['uri'] == '__home__' ? '' : rtrim($uri['uri'], '/');

		foreach ($hosts as $id => $host) {
			foreach ($host['url'] as $hostLocale => $hostUrl) {
				if (
					($hostLocale === $uri['locale'] || $uri['locale'] === null) &&
					($uri['host'] === $id || $uri['host'] === null)
				) {
					$url = rtrim($hostUrl, '/') . '/' . ltrim($uri['uri'], '/');

					if ($uri['uri'] && craft()->config->get('addTrailingSlashesToUrls')) {
							$url .= '/';
					}

					array_push($urls, array(
						'hostId' => $id,
						'hostName' => $host['hostName'],
						'locale' => $uri['locale'],
						'url' => $url
					));
				}
			}
		}

		$urls = $this->_uniqueUrls($urls);

		return $urls;
	}

	protected function getVarnishHosts()
	{
		$hosts = craft()->varnishpurge->getSetting('varnishHosts');

		if (!is_array($hosts)) {
			// Hosts is not an array - make into one using the global settings
			return array(
				'public' => array(
					'url' => craft()->varnishpurge->getSetting('varnishUrl'),
					'hostName' => craft()->varnishpurge->getSetting('varnishHostName'),
					'adminIP' => craft()->varnishpurge->getSetting('adminIP'),
					'adminPort' => craft()->varnishpurge->getSetting('adminPort'),
					'adminSecret' => craft()->varnishpurge->getSetting('adminSecret')
					// 'banPrefix' => craft()->varnishpurge->getSetting('banPrefix')
				)
			);
		} else {
			// Gather hosts and sanity check
			foreach ($hosts as &$host) {
				// if (!isset($host['banPrefix'])) {
				// 	$host['banPrefix'] = craft()->varnishpurge->getSetting('banPrefix');
				// }

				if (!$host['url']) {
					$host['url'] = array_fill_keys(array(craft()->language), craft()->getSiteUrl());
				}

				if (!is_array($host['url'])) {
					// URL array is not split by locale, create with current locale
					$host['url'] = array_fill_keys(array(craft()->language), $host['url']);
				}
			}
		}

		return $hosts;
	}

	protected function parseGuzzleResponse($httpRequest, $httpResponse, $showUri = false) {
		$response = new Varnishpurge_ResponseHelper(
			Varnishpurge_ResponseHelper::CODE_OK
		);

		if ($showUri) {
			$response->message = sprintf(
				'%s %s',
				$httpRequest->getUrl(),
				$httpResponse->getReasonPhrase()
			);
		} else {
			$response->message = sprintf(
				'%s:%s %s',
				$httpRequest->getHost(),
				$httpRequest->getPort(),
				$httpResponse->getReasonPhrase()
			);
		}

		if (!$httpResponse->isSuccessful()) {
			$resopnse->code = $httpResponse->getStatusCode();
		}

		return $response;
	}

	protected function parseGuzzleError($hostId, $e, $debug = false)
	{
		$response = new Varnishpurge_ResponseHelper(
			Varnishpurge_ResponseHelper::CODE_ERROR_GENERAL
		);

		if ($e instanceof \Guzzle\Http\Exception\BadResponseException) {
			$response->message = 'Error on "' . $hostId . '" URL "' .
					$e->getRequest()->getUrl() . '"' .
					' (' . $e->getResponse()->getStatusCode() . ' - ' .
					$e->getResponse()->getReasonPhrase() . ')';

			VarnishpurgePlugin::log(
				$response->message,
				LogLevel::Error,
				true,
				$debug
			);
		} else if ($e instanceof \Guzzle\Http\Exception\CurlException) {
			$response->code = Varnishpurge_ResponseHelper::CODE_ERROR_CURL;
			$response->message = 'cURL Error on "' . $hostId . '" URL "' . $e->getMessage();

			VarnishpurgePlugin::log(
				$response->message,
				LogLevel::Error,
				true,
				$debug
			);
		} else if ($e instanceof \Exception) {
			$response->message = 'Error on "' . $hostId . '" URL "' . $e->getMessage();

			VarnishpurgePlugin::log(
				$response->message,
				LogLevel::Error,
				true,
				$debug
			);
		}

		return $response;
	}

	private function _uniqueUrls($urls)
	{
		$found = array();

		return array_filter($urls, function($url) use ($found) {
			if (!in_array($url['url'], $found)) {
					array_push($found, $url['url']);
					return true;
			}
		});
   }
}
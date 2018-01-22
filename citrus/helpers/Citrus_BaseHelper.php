<?php
namespace Craft;

trait Citrus_BaseHelper
{
   public $hashAlgo = 'crc32';

	public function getPostWithDefault($var, $default = null) {
		$value = craft()->request->getPost($var);
		return (!empty($value) ? $value : $default);
	}

	public function getParamWithDefault($var, $default = null) {
		$value = craft()->request->getParam($var);
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
		$hosts = craft()->citrus->getSetting('varnishHosts');

		if (!is_array($hosts) || !empty($hosts)) {
			// Hosts is not an array - make into one using the global settings
			$canDoAdminBans = (
				!empty(craft()->citrus->getSetting('adminIP')) &&
				!empty(craft()->citrus->getSetting('adminPort')) &&
				!empty(craft()->citrus->getSetting('adminSecret'))
			);

			$hosts = [
				'public' => [
					'url' => craft()->citrus->getSetting('varnishUrl'),
					'hostName' => craft()->citrus->getSetting('varnishHostName'),
					'adminIP' => craft()->citrus->getSetting('adminIP'),
					'adminPort' => craft()->citrus->getSetting('adminPort'),
					'adminSecret' => craft()->citrus->getSetting('adminSecret'),
					'canDoAdminBans' => $canDoAdminBans
				]
			];
		}

		// Normalise and sanity check hosts before returning
		foreach ($hosts as &$host) {
			$host['canDoAdminBans'] = (
				!empty($host['adminIP']) &&
				!empty($host['adminPort']) &&
				!empty($host['adminSecret'])
			);

			if (!$host['url']) {
				$host['url'] = array_fill_keys([craft()->language], craft()->getSiteUrl());
			}

			if (!is_array($host['url'])) {
				// URL array is not split by locale, create with current locale
				$host['url'] = array_fill_keys([craft()->language], $host['url']);
			}
		}

		return $hosts;
	}

	protected function getVarnishAdminHosts()
	{
		$hosts = $this->getVarnishHosts();

		return array_filter($hosts, function($host) {
			return $host['canDoAdminBans'];
		});
	}

	protected function parseGuzzleResponse($httpRequest, $httpResponse, $showUri = false) {
		$response = new Citrus_ResponseHelper(
			Citrus_ResponseHelper::CODE_OK
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
		$response = new Citrus_ResponseHelper(
			Citrus_ResponseHelper::CODE_ERROR_GENERAL
		);

		if ($e instanceof \Guzzle\Http\Exception\BadResponseException) {
			$response->message = 'Error on "' . $hostId . '" URL "' .
					$e->getRequest()->getUrl() . '"' .
					' (' . $e->getResponse()->getStatusCode() . ' - ' .
					$e->getResponse()->getReasonPhrase() . ')';

			CitrusPlugin::log(
				$response->message,
				LogLevel::Error,
				true,
				$debug
			);
		} else if ($e instanceof \Guzzle\Http\Exception\CurlException) {
			$response->code = Citrus_ResponseHelper::CODE_ERROR_CURL;
			$response->message = 'cURL Error on "' . $hostId . '" URL "' . $e->getMessage();

			CitrusPlugin::log(
				$response->message,
				LogLevel::Error,
				true,
				$debug
			);
		} else if ($e instanceof \Exception) {
			$response->message = 'Error on "' . $hostId . '" URL "' . $e->getMessage();

			CitrusPlugin::log(
				$response->message,
				LogLevel::Error,
				true,
				$debug
			);
		}

		return $response;
	}

	protected function getTemplateStandardVars(array $customVariables)
	{
		$variables = array();
		$locales = array();

		foreach (craft()->i18n->getEditableLocales() as $locale) {
			array_push($locales, $locale);
		}

		$variables['locales'] = $locales;

		$variables = array_merge($customVariables, $variables);

		return $variables;
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
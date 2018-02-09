<?php
namespace Craft;

class Citrus_uriService extends BaseApplicationComponent
{
	use Citrus_BaseHelper;

	public function saveURIEntry(string $pageUri, int $entryId, string $locale)
	{
		$uriHash = $this->hash($pageUri);

		// Save URI record
		$uri = $this->getURIByURIHash(
			$uriHash
		);

		$uri->uri = $pageUri;
		$uri->uriHash = $uriHash;
		$uri->locale = (!empty($locale) ? $locale : null);

		$this->saveURI($uri);

		// Save Entry record
		$entry = new Citrus_EntryRecord();

		$entry->uriId = $uri->id;
		$entry->entryId = $entryId;

		craft()->citrus_entry->saveEntry($entry);
	}

	public function deleteURI(string $pageUri)
	{
		$uriHash = $this->hash($pageUri);

		// Save URI record
		$uri = $this->getURIByURIHash(
			$uriHash
		);

		if (!$uri->isNewRecord) {
			$uri->delete();
		}
	}

	public function getURI($id)
	{
		return Citrus_uriRecord::model()->findAllByPk($id);
	}

	public function getURIByURIHash($uriHash = '')
	{
		if (empty($uriHash)) {
			throw new Exception('$uriHash cannot be blank.');
		}

		$uri = Citrus_uriRecord::model()->findByAttributes(array(
		  'uriHash' => $uriHash
		));

		if ($uri !== null) {
			return $uri;
		}

		return new Citrus_uriRecord();
	}

	public function getAllURIsByEntryId(int $entryId)
	{
		return Citrus_uriRecord::model()->with(array(
			'entries' => array(
				'select' => false,
				'condition' => 'entryId = ' . $entryId
			)
		))->findAll();
	}

	public function saveURI(
		Citrus_uriRecord $uri
	) {
		$uri->save();
	}
}

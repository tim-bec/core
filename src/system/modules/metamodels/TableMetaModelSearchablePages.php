<?php

/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 * @package	   MetaModels
 * @subpackage Core
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Stefan Heimes <cms@men-at-work.de>
 * @copyright  The MetaModels team.
 * @license    LGPL.
 * @filesource
 */

/**
 * This class is used from DCA tl_metamodel_searchable_pages for various callbacks.
 *
 * @package	   MetaModels
 * @subpackage Backend
 * @author     Stefan Heimes <cms@men-at-work.de>
 */
class TableMetaModelSearchablePages extends Backend
{

	protected $arrErrorLogs = array();

	/**
	 * Class constructor, imports the Backend user.
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Add all pages from MM lister/reader to the search index for the contao 
	 * search and all sitemaps.
	 * 
	 * @param array $arrPages
	 * @param int $intRoot
	 * @param boolean $blnSitemap
	 * @param string $strLanguage
	 * 
	 * @return array A list with all pages.
	 */
	public function addSearchablePages($arrPages, $intRoot = null, $blnSitemap = false, $strLanguage = null)
	{
		// Neues Teil!
		$objSearchablePages = Database::getInstance()
				->prepare('SELECT * FROM tl_metamodel_searchable_pages')
				->execute();

		// Check if we have list.
		if ($objSearchablePages->numRows == 0)
		{
			die('no lists');
			return $arrPages;
		}

		while ($objSearchablePages->next())
		{
			// Search for the MM id.
			$intMMId = $this->getMMId($objSearchablePages->pid);
			if ($intMMId == null)
			{
				$this->addError('MM', 'Could not find the MM id from the filter with id: ' . $objSearchablePages->pid);
				continue;
			}

			// Load the MM.
			$objMetaModel = MetaModelFactory::byId($intMMId);
			if ($objMetaModel == null)
			{
				$this->addError('MM', 'Could not find the MM with id: ' . $intMMId);
				continue;
			}

			// Load the render settings.
			$objRenderSettings = MetaModelRenderSettingsFactory::byId($objMetaModel, 1);
			if ($objRenderSettings == null)
			{
				$this->addError('Rendersettings', 'Could not find the rendersettings with id: ' . 1);
				continue;
			}

			// User the all to jump to if we have no language.
			if ($strLanguage == null && $this->containsLanguage($objRenderSettings->get('jumpTo'), 'xx'))
			{
				// ToDo
			}
			else if ($strLanguage == null)
			{
				$this->addError('Language', 'Could not find the jump to for all languages.');
				continue;
			}
			// Use current language.
			else if ($strLanguage != null && $this->containsLanguage($objRenderSettings->get('jumpTo'), $strLanguage))
			{
				// ToDo
			}
			else if ($strLanguage != null)
			{
				$this->addError('Language', 'Could not find the jump to for the language ' . $strLanguage);
				continue;
			}
		}

		var_dump($arrPages);
		var_dump($intRoot);
		var_dump($blnSitemap);
		var_dump($strLanguage);
		var_dump($this->arrErrorLogs);
		die();

		return $arrPages;
	}

	/**
	 * generate an url determined by the given params and configured jumpTo page.
	 *
	 * @param array $arrParams the URL parameters to use.
	 *
	 * @return string the generated URL.
	 *
	 */
	protected function getJumpToUrl($arrJumpTo, $arrParams)
	{
		// Get jump to array.
		foreach ($arrJumpTo as $intKey => $arrJumpTo)
		{
			// Check if we have the right language tags.
			if ($arrJumpTo['langcode'] != 'xx' && $strLanguage != null && $strLanguage != $arrJumpTo['langcode'])
			{
				continue;
			}

			// ToDo build jumpTo;
			var_dump($this->getJumpToUrl($arrJumpTo, array()));
		}


//		$strReturn = array();
//		var_dump($arrJumpTo);
//		var_dump($arrParams);
//		die();


		return '';
	}

	////////////////////////////////////////////////////////////////////////////
	// Helper
	////////////////////////////////////////////////////////////////////////////

	/**
	 * Get the MM id.
	 * 
	 * @param int $intId
	 * 
	 * @return null/int
	 */
	protected function getMMId($intId)
	{
		// Neues Teil!
		$objFilter = Database::getInstance()
				->prepare('SELECT pid FROM tl_metamodel_filter WHERE id=?')
				->execute($intId);

		if ($objFilter->numRows == 0)
		{
			return null;
		}

		return $objFilter->pid;
	}

	/**
	 * Check if we have a language in the jump to url.
	 * 
	 * @param array $arrJumpToRules
	 * @param string $strLanguage
	 * 
	 * @return boolean
	 */
	protected function containsLanguage($arrJumpToRules, $strLanguage)
	{
		foreach ($arrJumpToRules as $value)
		{
			if ($value['langcode'] == $strLanguage)
			{
				return true;
			}

			return false;
		}
	}

	////////////////////////////////////////////////////////////////////////////
	// Error
	////////////////////////////////////////////////////////////////////////////

	protected function addError($strKlass, $strMsg)
	{
		$this->arrErrorLogs[$strKlass][] = $strMsg;
	}

}


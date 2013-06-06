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

	////////////////////////////////////////////////////////////////////////////
	// Callbacks
	////////////////////////////////////////////////////////////////////////////

	/**
	 * Try to get the current parent Filtersetting.
	 * 
	 * @return null/array
	 */
	protected function getFilterSetting($intPid = null)
	{
		$intCurrentID = Input::getInstance()->get('id');
		$intCurrentPID = Input::getInstance()->get('pid');
		$intFilterID = '';

		// If set by param, use it.
		if (!is_null($intPid))
		{
			$intFilterID = $intPid;
		}
		// Get the parent ID.
		else if (!empty($intCurrentID))
		{
			$objCurrentRow = Database::getInstance()
					->prepare('SELECT * FROM tl_metamodel_searchable_pages WHERE id=?')
					->execute($intCurrentID);

			// Check if we have data.
			if ($objCurrentRow->numRows == 0)
			{
				return null;
			}

			$intFilterID = $objCurrentRow->pid;
		}
		else if (!empty($intCurrentPID))
		{
			$intFilterID = $intCurrentPID;
		}
		else
		{
			return null;
		}

		return MetaModelFilterSettingsFactory::byId($intFilterID);
	}

	/**
	 * Get a list with all parameters. 
	 * 
	 * @param mixed $objDC
	 * 
	 * @return array
	 */
	public function getParameter($objDC)
	{
		$arrReturn = array();

		$objFilter = $this->getFilterSetting();

		if (is_null($objFilter))
		{
			return $arrReturn;
		}

		foreach ($objFilter->getParameterFilterNames() as $strParameter => $strDesricption)
		{
			$arrReturn[$strParameter] = vsprintf('%s (Param.: %s)', array($strDesricption, $strParameter));
		}

		return $arrReturn;
	}

	/**
	 * Get a list with all parameters and there fitting values.
	 * 
	 * @param mixed $objDC
	 * 
	 * @return array
	 */
	public function getValues($objDC)
	{
		$arrReturn = array();

		// Get filtersettings or return an empty array.
		$objFilter = $this->getFilterSetting();
		if (is_null($objFilter))
		{
			return $arrReturn;
		}

		// Get elements and more.
		$objMM = $objFilter->getMetaModel();
		$arrParameters = $objFilter->getParameters();
		$arrAttributes = $objFilter->getReferencedAttributes();
		$arrParameterFilterNames = $objFilter->getParameterFilterNames();

		foreach ($arrParameters as $intKey => $strPrameter)
		{
			// Get elements and more.
			$arrFilterValues = array();
			$strFilter = vsprintf('%s (Param.: %s)', array($arrParameterFilterNames[$strPrameter], $strPrameter));
			$arrAttribute = $objMM->getAttribute($arrAttributes[$intKey]);
			$arrFilterOption = $arrAttribute->getFilterOptions(null, true);
			
			// If we have a checkbox write 'True' or 'False'. 
			if (in_array($arrAttribute->get('type'), array('checkbox')))
			{
				foreach ($arrFilterOption as $mixFilterKey => $mixFilterValue)
				{
					if ($mixFilterValue)
					{
						$arrFilterValues[$mixFilterKey] = 'True';
					}
					else
					{
						$arrFilterValues[$mixFilterKey] = 'False';
					}
				}
			}
			// Else just create a normal array.
			else
			{
				foreach ($arrFilterOption as $mixFilterKey => $mixFilterValue)
				{
					$arrFilterValues[$mixFilterKey] = $mixFilterValue;
				}
			}

			// Merge or create
			if (is_array($arrReturn[$strFilter]))
			{
				$arrReturn[$strFilter] = array_merge($arrReturn[$strFilter], $arrFilterValues);
			}
			else
			{
				$arrReturn[$strFilter] = $arrFilterValues;
			}
		}

		return $arrReturn;
	}

	/**
	 * Get a list with all rendersettings for the current filter.
	 * 
	 * @param mixed $objDC
	 * 
	 * @return array
	 */
	public function getRendersettings($objDC)
	{
		$arrReturn = array();

		// Get filtersettings or return an empty array.
		$objFilter = $this->getFilterSetting();
		if (is_null($objFilter))
		{
			return $arrReturn;
		}

		$objMetaModel = $objFilter->getMetaModel();
		$intMMID = $objMetaModel->get('id');

		$objRenderSettings = Database::getInstance()
				->prepare('SELECT * FROM tl_metamodel_rendersettings WHERE pid=?')
				->execute($intMMID);

		if ($objRenderSettings->numRows == 0)
		{
			return $arrReturn;
		}

		while ($objRenderSettings->next())
		{
			$arrReturn[$objRenderSettings->id] = vsprintf('%s - %s', array($objMetaModel->get('name'), $objRenderSettings->name));
		}

		return $arrReturn;
	}

	////////////////////////////////////////////////////////////////////////////
	// Hook
	////////////////////////////////////////////////////////////////////////////

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
		$strMasterLanguage = $GLOBALS['TL_LANGUAGE'];
		
		// Neues Teil!
		$objSearchablePages = Database::getInstance()
				->prepare('SELECT * FROM tl_metamodel_searchable_pages')
				->execute();

		// Check if we have list.
		if ($objSearchablePages->numRows == 0)
		{
			return $arrPages;
		}

		while ($objSearchablePages->next())
		{
			// Search for the MM id and load it
			$intMMId = $this->getMMId($objSearchablePages->pid);
			if ($intMMId == null)
			{
				$this->addError('MM', 'Could not find the MM id from the filter with id: ' . $objSearchablePages->pid);
				continue;
			}
			$objMetaModel = MetaModelFactory::byId($intMMId);
			if ($objMetaModel == null)
			{
				$this->addError('MM', 'Could not find the MM with id: ' . $intMMId);
				continue;
			}

			// Load the render settings.
			$objRenderSettings = MetaModelRenderSettingsFactory::byId($objMetaModel, $objSearchablePages->rendersetting);
			if ($objRenderSettings == null)
			{
				$this->addError('Rendersettings', 'Could not find the rendersettings with id: ' . 1);
				continue;
			}

			$arrLanguages = deserialize($objRenderSettings->get('jumpTo'));
						
			// Load the filter.
			$objFilter = $this->getFilterSetting($objSearchablePages->pid);
			$arrParametersNames = $objFilter->getParameters();
			$arrAttributesNames = $objFilter->getReferencedAttributes();

			// Okay load the parameters, build all combinations and build the jump to.
			$arrParameters = deserialize($objSearchablePages->parameter);
			
			foreach ($arrLanguages as $arrLanguage)
			{
				// check if we have a jump to. 
				if(empty($arrLanguage['value']))
				{
					continue;
				}
				
				// Set current language to the jump to.
				$GLOBALS['TL_LANGUAGE'] = $arrLanguage['langcode'];				
				
				foreach ($this->buildAllCombinations($arrParameters) as $arrData)
				{
					$objItemFilter = $objMetaModel->getEmptyFilter();
					$objFilter->addRules($objItemFilter, $arrData);

					/** var IMetaModelItems $objItems */
					$objItems = $objMetaModel->findByFilter($objItemFilter);

					foreach ($objItems as $objItem)
					{						
						/** var MetaModelItem $objItem */
						$arrJumpTo = $objItem->buildJumpToLink($objRenderSettings);
						// FIXME: determine real url as we might have a domain specified in the root page.
						$arrPages[] = Environment::getInstance()->base . $arrJumpTo['url'];
						
					}
				}
			}
		}
		
		$GLOBALS['TL_LANGUAGE'] = $strMasterLanguage;

		return $arrPages;
	}

	protected function buildAllCombinations($arrParam, $intCurrentKey = 0)
	{
		$arrReturn = array();
		$arrSubValues = array();

		// Build all other combinations first.
		if (key_exists($intCurrentKey + 1, $arrParam))
		{
			$arrSubValues = $this->buildAllCombinations($arrParam, $intCurrentKey + 1);
		}

		if (key_exists('values', $arrParam[$intCurrentKey]) && !empty($arrParam[$intCurrentKey]['values']))
		{
			foreach ($arrParam[$intCurrentKey]['values'] as $mixKey => $mixValue)
			{
				// If empty we could not find the attribute so go to the next one.
				if (!$arrParam[$intCurrentKey]['parameter'])
				{
					continue;
				}

				if (is_array($arrSubValues) && !empty($arrSubValues))
				{
					foreach ($arrSubValues as $arrValue)
					{
						$arrReturn[] = array_merge(array(
							$arrParam[$intCurrentKey]['parameter'] => $mixValue['type']
								), $arrValue);
					}
				}
				else
				{
					$arrReturn[] = array($arrParam[$intCurrentKey]['parameter'] => $mixValue['type']);
				}
			}

			return $arrReturn;
		}
		else
		{
			return $arrSubValues;
		}
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


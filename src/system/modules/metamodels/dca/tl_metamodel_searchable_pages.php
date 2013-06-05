<?php
/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 * @package	   MetaModels
 * @subpackage Backend
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  The MetaModels team.
 * @license    LGPL.
 * @filesource
 */

/**
 * Table tl_metamodel_attribute
 */

$GLOBALS['TL_DCA']['tl_metamodel_searchable_pages'] = array
(
	'config' => array
	(
		'dataContainer'               => 'General',
		'ptable'                      => 'tl_metamodel',
		'switchToEdit'                => false,
		'enableVersioning'            => false,
	),

	// List
	'list' => array
	(
		'presentation' => array
		(
			'breadcrumb_callback'     => array('MetaModelBreadcrumbBuilder', 'generateBreadcrumbItems'),
		),
		
		'sorting' => array
		(
			'mode'                    => 1,
			'fields'                  => array('name'),
			'panelLayout'             => 'filter,limit',
			'headerFields'            => array('name'),
			'flag'                    => 1,
		),

		'label' => array
		(
			'fields'                  => array('name'),
			'format'                  => '%s'
		),

		'global_operations' => array
		(
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset();"'
			)
		),

		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_metamodel_filter']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_metamodel_filter']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_metamodel_filter']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		)
	),

	'metapalettes' => array
	(
		'default' => array
		(
			'title' => array('name'),
			'parameter' => array('parameter'),
		),
	),

	// Fields
	'fields' => array
	(
		'name' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_metamodel_searchable_pages']['name'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50')
		),
		
		'parameter' =>  array
		(
			'label'         => &$GLOBALS['TL_LANG']['tl_theme']['parameter'],
			'exclude'       => true,
			'inputType'     => 'multiColumnWizard',
			'eval'          => array
			(
				'columnFields' => array
				(
					'parametersettings' =>  array
					(
						'label'         => &$GLOBALS['TL_LANG']['tl_theme']['parametersettings'],
						'exclude'       => true,
						'inputType'     => 'multiColumnWizard',
						'eval'          => array
						(
							'columnFields' => array
							(
								'type' => array
								(
									'label'                   => &$GLOBALS['TL_LANG']['tl_metamodel_filtersetting']['type'],
									'exclude'                 => true,
									'inputType'               => 'select',
									'options_callback'        => array('TableMetaModelFilterSetting', 'getSettingTypes'),
									'reference'               => &$GLOBALS['TL_LANG']['tl_metamodel_filtersetting']['typenames'],
									'eval'                    => array
									(
										'doNotSaveEmpty'      => true,
										'includeBlankOption'  => true,
										'mandatory'           => true,
										'tl_class'            => 'w50',
//										'chosen'              => true
									),
								),

								'types' => array
								(
									'label'                   => &$GLOBALS['TL_LANG']['tl_metamodel_filtersetting']['type'],
									'exclude'                 => true,
									'inputType'               => 'select',
									'options_callback'        => array('TableMetaModelFilterSetting', 'getSettingTypes'),
									'reference'               => &$GLOBALS['TL_LANG']['tl_metamodel_filtersetting']['typenames'],
									'eval'                    => array
									(
										'doNotSaveEmpty'      => true,										
										'includeBlankOption'  => true,
										'mandatory'           => true,
										'tl_class'            => 'w50',
//										'chosen'              => true
									),
								),
							)
						)
					)
				)
			)
		)
	)
);


<?php
/**
 * @package     RedCORE.Plugin
 * @subpackage  System.MVCOverride
 *
 * @copyright   Copyright (C) 2008 - 2017 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\CMS\MVC\Model;

defined('JPATH_PLATFORM') or die;

use Joomla\Utilities\ArrayHelper;

/**
 * Base class for a database aware Joomla Model
 *
 * Acts as a Factory class for application specific objects and provides many supporting API functions.
 *
 * @since  1.5.0
 */
abstract class ListModel extends LIB_ListModelDefault
{
	/**
	 * Array to registry paths from component form and gields
	 *
	 * @var array
	 * @since  1.5.0
	 */
	static private $codePaths = array('form' => array(), 'fields' => array());

	/**
	 * Method to get a form object.
	 *
	 * @param   string          $name     The name of the form.
	 * @param   string          $source   The form source. Can be XML string if file flag is set to false.
	 * @param   array           $options  Optional array of options for the form creation.
	 * @param   boolean         $clear    Optional argument to force load a new form.
	 * @param   string|boolean  $xpath    An optional xpath to search for the fields.
	 *
	 * @return  \JForm|boolean  \JForm object on success, False on error.
	 *
	 * @see     \JForm
	 * @since  1.5.0
	 */
	protected function loadForm($name, $source = null, $options = array(), $clear = false, $xpath = false)
	{
		// Handle the optional arguments.
		$options['control'] = ArrayHelper::getValue((array) $options, 'control', false);

		// Create a signature hash.
		$hash = md5($source . serialize($options));

		// Check if we can use a previously loaded form.
		if (!$clear && isset($this->_forms[$hash]))
		{
			return $this->_forms[$hash];
		}

		// Get the form.
		\JForm::addFormPath(JPATH_COMPONENT . '/models/forms');
		\JForm::addFieldPath(JPATH_COMPONENT . '/models/fields');

		// Sync with codepools
		\JForm::addFormPath(self::addComponentFormPath());
		\JForm::addFieldPath(self::addComponentFieldPath());

		try
		{
			$form = \JForm::getInstance($name, $source, $options, false, $xpath);

			if (isset($options['load_data']) && $options['load_data'])
			{
				// Get the data for the form.
				$data = $this->loadFormData();
			}
			else
			{
				$data = array();
			}

			// Allow for additional modification of the form, and events to be triggered.
			// We pass the data because plugins may require it.
			$this->preprocessForm($form, $data);

			// Load the data into the form after the plugins have operated.
			$form->bind($data);
		}
		catch (\Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		// Store the form for later.
		$this->_forms[$hash] = $form;

		return $form;
	}

	/**
	 * Add new path to search component forms
	 *
	 * @param   string  $path  Path
	 *
	 * @return string
	 * @since  1.5.0
	 */
	static public function addComponentFormPath($path = null)
	{
		if (is_null($path))
		{
			return self::$codePaths['form'];
		}

		array_push(self::$codePaths['form'], $path);

		return self::$codePaths['form'];
	}

	/**
	 * Add new path to search component form fields
	 *
	 * @param   string  $path  Path
	 *
	 * @return  string
	 * @since  1.5.0
	 */
	static public function addComponentFieldPath($path = null)
	{
		if (is_null($path))
		{
			return self::$codePaths['fields'];
		}

		array_push(self::$codePaths['fields'], $path);

		return self::$codePaths['fields'];
	}
}

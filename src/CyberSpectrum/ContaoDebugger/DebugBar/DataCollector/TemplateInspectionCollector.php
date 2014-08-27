<?php
/**
 * Contao Debugger
 *
 * Copyright (c) 2014 Christian Schiffler
 *
 * @package     ContaoDebugger
 * @author      Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright   2014 CyberSpectrum
 * @license     LGPL-3+
 * @filesource
 */

namespace CyberSpectrum\ContaoDebugger\DebugBar\DataCollector;

use CyberSpectrum\ContaoDebugger\DebugBar\DataFormatter\DataFormatter;
use DebugBar\DataFormatter\DataFormatterInterface;

/**
 * Traces Template variables.
 */
class TemplateInspectionCollector extends \DebugBar\DataCollector\RequestDataCollector
{
	/**
	 * The messages.
	 *
	 * @var array
	 */
	protected $messages = array();

	/**
	 * Returns the default data formater.
	 *
	 * @return DataFormatterInterface
	 */
	public function getDataFormatter()
	{
		if ($this->dataFormater === null)
		{
			$this->dataFormater = new DataFormatter();
		}

		return $this->dataFormater;
	}

	/**
	 * Adds a template.
	 *
	 * @param \Template $template The message.
	 *
	 * @return void
	 */
	public function addTemplate($template)
	{
		$this->messages[] = array(
			'message' => $this->getDataFormatter()->formatVar($template->getData()),
			'label' => $template->getName(),
			'time' => microtime(true)
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function collect()
	{
		$data = array();
		foreach ($this->messages as $i => $var)
		{
			$label = $var['label'];

			$label = $i . ' ' . $label;

			$data[$label] = $var['message'];
		}

		return array(
			'templates' => $data,
			'nb_templates' => count($this->messages)
		);
	}

	/**
	 * Retrieve the name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'template-inspection';
	}

	/**
	 * Retrieve the widget information.
	 *
	 * @return array
	 */
	public function getWidgets()
	{
		return array(
			'template-inspection'       => array(
				'icon'              => 'tags',
				'widget'            => 'PhpDebugBar.Widgets.VariableListWidget',
				'map'               => 'template-inspection.templates',
				'default'           => '{}'
			),
			'template-inspection:badge' => array
			(
				'map'               => 'template-inspection.nb_templates',
				'default'           => 0
			)
		);
	}
}

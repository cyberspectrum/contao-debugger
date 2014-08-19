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

use CyberSpectrum\ContaoDebugger\Database\DatabaseDebugger;
use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use DebugBar\DataCollector\TimeDataCollector;

/**
 * Collects data about SQL statements executed within Contao.
 */
class ContaoSQLCollector extends DataCollector implements Renderable, AssetProvider
{
	/**
	 * Optional time collector.
	 *
	 * @var TimeDataCollector
	 */
	protected $timeCollector;

	/**
	 * All executed statements.
	 *
	 * @var array
	 */
	protected $statementInformation = array();

	/**
	 * Create a new instance.
	 *
	 * @param TimeDataCollector $timeCollector The optional time collector.
	 */
	public function __construct(TimeDataCollector $timeCollector = null)
	{
		$this->timeCollector = $timeCollector;
		DatabaseDebugger::attach($this);
	}

	/**
	 * Log a statement.
	 *
	 * @param array $info The information.
	 *
	 * @return void
	 */
	public function addStatement($info)
	{
		$this->statementInformation[] = $info;
	}

	/**
	 * Convert a single statement information into a javascript parsable array.
	 *
	 * @param array $stmt The statement information.
	 *
	 * @return array
	 */
	protected function convertStatement($stmt)
	{
		$newstmt = array(
			'sql'           => $stmt['realquery'],
			'row_count'     => $stmt['return_count'] ?: $stmt['affected_count'],
			'prepared_stmt' => $stmt['query'],
			'params'        => (object)$stmt['params'],
			'duration'      => $stmt['duration'],
			'duration_str'  => $this->getDataFormatter()->formatDuration($stmt['duration']),
			'is_success'    => true,
			'error_code'    => 0,
			'error_message' => ''
		);

		if ($this->timeCollector !== null)
		{
			$this->timeCollector->addMeasure($stmt['query'], $stmt['timeStart'], $stmt['timeStop']);
		}

		return $newstmt;
	}

	/**
	 * {@inheritDoc}
	 */
	public function collect()
	{
		$data = array
		(
			'nb_statements'        => count($this->statementInformation),
			'nb_failed_statements' => 0,
			'accumulated_duration' => 0,
			'statements'           => array()
		);

		foreach ($this->statementInformation as $statement)
		{
			$converted                     = $this->convertStatement($statement);
			$data['statements'][]          = $converted;
			$data['accumulated_duration'] += $converted['duration'];
		}

		$data['accumulated_duration_str'] = $this->getDataFormatter()->formatDuration($data['accumulated_duration']);

		return $data;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		return 'contao-sql';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getWidgets()
	{
		return array(
			'database'       => array
			(
				'icon'       => 'inbox',
				'widget'     => 'PhpDebugBar.Widgets.SQLQueriesWidget',
				'map'        => 'contao-sql',
				'default'    => '[]'
			),
			'database:badge' => array
				(
				'map'        => 'contao-sql.nb_statements',
				'default'    => 0
			)
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAssets()
	{
		return array(
			'css' => 'widgets/sqlqueries/widget.css',
			'js' => 'widgets/sqlqueries/widget.js'
		);
	}
}

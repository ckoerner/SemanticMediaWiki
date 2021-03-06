<?php

namespace SMW\Tests\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\SqlQueryPart;

/**
 * @covers \SMW\SQLStore\QueryEngine\SqlQueryPart
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class SqlQueryPartTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\SqlQueryPart',
			new SqlQueryPart()
		);
	}

	public function testResetState() {

		$instance = new SqlQueryPart();
		$instance->reset();

		$this->assertEquals(
			0,
			$instance->queryNumber
		);

		$this->assertEquals(
			't0',
			$instance->alias
		);

		$this->assertEquals(
			1,
			$instance::$qnum
		);

		$this->assertEquals(
			$instance::Q_TABLE,
			$instance->type
		);

		$this->assertEquals(
			array(),
			$instance->components
		);

		$this->assertEquals(
			array(),
			$instance->sortfields
		);

		$this->assertEquals(
			'',
			$instance->joinfield
		);

		$this->assertEquals(
			'',
			$instance->joinTable
		);

		$this->assertEquals(
			'',
			$instance->from
		);

		$this->assertEquals(
			'',
			$instance->where
		);
	}

}

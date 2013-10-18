<?php

namespace SMW\Test;

use SMW\ShowParserFunction;
use SMW\MessageFormatter;
use SMW\QueryData;

use Title;
use ParserOutput;

/**
 * @covers \SMW\ShowParserFunction
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ShowParserFunctionTest extends ParserTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMW\ShowParserFunction';
	}

	/**
	 * Helper method that returns a ShowParserFunction object
	 *
	 * @since 1.9
	 *
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 *
	 * @return ShowParserFunction
	 */
	private function newInstance( Title $title = null, ParserOutput $parserOutput = null ) {

		if ( $title === null ) {
			$title = $this->newTitle();
		}

		if ( $parserOutput === null ) {
			$parserOutput = $this->newParserOutput();
		}

		$settings = $this->newSettings();

		return new ShowParserFunction(
			$this->newParserData( $title, $parserOutput ),
			new QueryData( $title ),
			new MessageFormatter( $title->getPageLanguage() ),
			$settings
		 );
	}

	/**
	 * @test ShowParserFunction::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @test ShowParserFunction::parse
	 * @dataProvider queryDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 * @param array $expected
	 */
	public function testParse( array $params, array $expected ) {

		$instance = $this->newInstance( $this->newTitle(), $this->newParserOutput() );
		$result   = $instance->parse( $params, true );

		if (  $expected['output'] === '' ) {
			$this->assertEmpty( $result );
		} else {
			$this->assertContains( $expected['output'], $result );
		}

	}

	/**
	 * @test ShowParserFunction::parse (Test $GLOBALS['smwgQEnabled'] = false)
	 * @dataProvider queryDataProvider
	 *
	 * @since 1.9
	 */
	public function testParseDisabledsmwgQEnabled() {

		$title    = $this->newTitle();
		$message  = new MessageFormatter( $title->getPageLanguage() );
		$expected = $message->addFromKey( 'smw_iq_disabled' )->getHtml();

		$instance = $this->newInstance( $title, $this->getParserOutput() );

		// Make protected method accessible
		$reflector = $this->newReflector();
		$method = $reflector->getMethod( 'disabled' );
		$method->setAccessible( true );

		$result = $method->invoke( $instance );
		$this->assertEquals( $expected , $result );
	}

	/**
	 * @test ShowParserFunction::parse (Test generated query data)
	 * @dataProvider queryDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 * @param array $expected
	 */
	public function testInstantiatedQueryData( array $params, array $expected ) {

		$parserOutput = $this->newParserOutput();
		$title        = $this->newTitle();

		// Initialize and parse
		$instance = $this->newInstance( $title, $parserOutput );
		$instance->parse( $params );

		// Get semantic data from the ParserOutput
		$parserData = $this->newParserData( $title, $parserOutput );

		// Check the returned instance
		$this->assertInstanceOf( '\SMW\SemanticData', $parserData->getData() );

		// Confirm subSemanticData objects for the SemanticData instance
		foreach ( $parserData->getData()->getSubSemanticData() as $containerSemanticData ){
			$this->assertInstanceOf( 'SMWContainerSemanticData', $containerSemanticData );
			$this->assertSemanticData( $containerSemanticData, $expected );
		}

	}

	/**
	 * Provides data sample normally found in connection with the {{#show}}
	 * parser function. The first array contains parametrized input value while
	 * the second array contains expected return results for the instantiated
	 * object.
	 *
	 * @return array
	 */
	public function queryDataProvider() {

		$provider = array();

		// #0
		// {{#show: Foo
		// |?Modification date
		// }}
		$provider[] = array(
			array(
				'Foo',
				'?Modification date',
			),
			array(
				'output' => '',
				'propertyCount' => 4,
				'propertyKey' => array( '_ASKFO', '_ASKDE', '_ASKSI', '_ASKST' ),
				'propertyValue' => array( 'list', 0, 1, '[[:Foo]]' )
			)
		);

		// #1
		// {{#show: Help:Bar
		// |?Modification date
		// |default=no results
		// }}
		$provider[] = array(
			array(
				'Help:Bar',
				'?Modification date',
				'default=no results'
			),
			array(
				'output' => 'no results',
				'propertyCount' => 4,
				'propertyKey' => array( '_ASKFO', '_ASKDE', '_ASKSI', '_ASKST' ),
				'propertyValue' => array( 'list', 0, 1, '[[:Help:Bar]]' )
			)
		);

		// #2 [[..]] is not acknowledged therefore displays an error message
		// {{#show: [[File:Fooo]]
		// |?Modification date
		// |default=no results
		// |format=table
		// }}
		$provider[] = array(
			array(
				'[[File:Fooo]]',
				'?Modification date',
				'default=no results',
				'format=table'
			),
			array(
				'output' => 'class="smwtticon warning"', // lazy content check for the error
				'propertyCount' => 4,
				'propertyKey' => array( '_ASKFO', '_ASKDE', '_ASKSI', '_ASKST' ),
				'propertyValue' => array( 'table', 0, 1, '[[:]]' )
			)
		);

		return $provider;
	}
}

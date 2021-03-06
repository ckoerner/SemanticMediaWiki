<?php

namespace SMW\Tests\Integration\Query;

use SMW\Tests\Utils\UtilityFactory;

use SMW\FileReader;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\DataTypeRegistry;
use SMW\DataValueFactory;
use SMW\Query\PrintRequest as PrintRequest;
use SMWDataItem as DataItem;
use SMWPropertyValue as PropertyValue;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class QueryTestCaseInterpreter {

	/**
	 * @var array
	 */
	private $contents;

	/**
	 * @since 2.2
	 *
	 * @param array $contents
	 */
	public function __construct( array $contents ) {
		$this->contents = $contents;
	}

	/**
	 * @since 2.2
	 *
	 * @return boolean
	 */
	public function hasCondition() {
		return isset( $this->contents['condition'] );
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getCondition() {
		return $this->hasCondition() ? $this->contents['condition'] : '';
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function isAbout() {
		return isset( $this->contents['about'] ) ? $this->contents['about'] : 'no description';
	}

	/**
	 * @since 2.2
	 *
	 * @return integer
	 */
	public function getQueryMode() {
		return isset( $this->contents['parameters']['querymode'] ) ? constant( $this->contents['parameters']['querymode'] ) : \SMWQuery::MODE_INSTANCES;
	}

	/**
	 * @since 2.2
	 *
	 * @return integer
	 */
	public function getLimit() {
		return isset( $this->contents['parameters']['limit'] ) ? (int)$this->contents['parameters']['limit'] : 100;
	}

	/**
	 * @since 2.2
	 *
	 * @return integer
	 */
	public function getOffset() {
		return isset( $this->contents['parameters']['offset'] ) ? (int)$this->contents['parameters']['offset'] : 0;
	}

	/**
	 * @since 2.2
	 *
	 * @return array
	 */
	public function getExtraPrintouts() {

		$extraPrintouts = array();

		if ( !isset( $this->contents['printouts'] ) || $this->contents['printouts'] === array() ) {
			return $extraPrintouts;
		}

		foreach ( $this->contents['printouts'] as $printout ) {

			$propertyValue = new PropertyValue( '__pro' );
			$propertyValue->setDataItem( DIProperty::newFromUserLabel( $printout ) );

			$extraPrintouts[] = new PrintRequest( PrintRequest::PRINT_PROP, null, $propertyValue );
		}

		return $extraPrintouts;
	}

	/**
	 * @since 2.2
	 *
	 * @return integer
	 */
	public function getExpectedCount() {
		return isset( $this->contents['queryresult']['count'] ) ? (int)$this->contents['queryresult']['count'] : 0;
	}

	/**
	 * @since 2.2
	 *
	 * @return DIWikiPage[]
	 */
	public function getExpectedSubjects() {

		$subjects = array();

		if ( !isset( $this->contents['queryresult']['results'] )  ) {
			return $subjects;
		}

		foreach ( $this->contents['queryresult']['results'] as $hashName ) {
			$subjects[] = DIWikiPage::doUnserialize( str_replace( ' ', '_', $hashName ) );
		}

		return $subjects;
	}

	/**
	 * @since 2.2
	 *
	 * @return DataItem[]
	 */
	public function getExpectedDataItems() {

		$dataItems = array();

		if ( !isset( $this->contents['queryresult']['dataitems'] )  ) {
			return $dataItems;
		}

		foreach ( $this->contents['queryresult']['dataitems'] as $dataitem ) {
			$dataItems[] = DataItem::newFromSerialization(
				DataTypeRegistry::getInstance()->getDataItemId( $dataitem['type'] ),
				$dataitem['value']
			);
		}

		return $dataItems;
	}

	/**
	 * @since 2.2
	 *
	 * @return DataValues[]
	 */
	public function getExpectedDataValues() {

		$dataValues = array();

		if ( !isset( $this->contents['queryresult']['datavalues'] )  ) {
			return $dataValues;
		}

		foreach ( $this->contents['queryresult']['datavalues'] as $datavalue ) {
			$dataValues[] = DataValueFactory::getInstance()->newPropertyValue(
				$datavalue['property'],
				$datavalue['value']
			);
		}

		return $dataValues;
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function fetchTextOutputForFormatPage() {

		if ( !isset( $this->contents['outputpage'] ) ) {
			return '';
		}

		$title = \Title::newFromText( $this->contents['outputpage'] );
		$parserOutput = UtilityFactory::getInstance()->newPageReader()->getEditInfo( $title )->output;

		return $parserOutput->getText();
	}

	/**
	 * @since 2.2
	 *
	 * @return array
	 */
	public function getExpectedFormatOuputFor( $id ) {

		$output = array();

		if ( !isset( $this->contents['output'] ) || !isset( $this->contents['output'][$id] )  ) {
			return $output;
		}


		return $this->contents['output'][$id];
	}

	/**
	 * @since 2.2
	 *
	 * @return []
	 */
	public function getExpectedConceptCache() {
		return $this->contents['conceptcache'];
	}

}

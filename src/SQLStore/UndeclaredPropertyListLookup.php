<?php

namespace SMW\SQLStore;

use SMW\InvalidPropertyException;
use SMW\DIProperty;
use SMW\Store;
use SMWDIError as DIError;
use SMWRequestOptions as RequestOptions;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 * @author Nischay Nahata
 */
class UndeclaredPropertyListLookup implements SimpleListLookup {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var string
	 */
	private $defaultPropertyType;

	/**
	 * @var RequestOptions
	 */
	private $requestOptions;

	/**
	 * @since 2.2
	 *
	 * @param Store $store
	 * @param string $defaultPropertyType
	 * @param RequestOptions $requestOptions|null
	 */
	public function __construct( Store $store, $defaultPropertyType, RequestOptions $requestOptions = null ) {
		$this->store = $store;
		$this->defaultPropertyType = $defaultPropertyType;
		$this->requestOptions = $requestOptions;
	}

	/**
	 * @since 2.2
	 *
	 * @return DIProperty[]
	 * @throws RuntimeException
	 */
	public function fetchResultList() {

		if ( $this->requestOptions === null ) {
			throw new RuntimeException( "Missing requestOptions" );
		}

		// Wanted Properties must have the default type
		$propertyTable = $this->getPropertyTableForType( $this->defaultPropertyType );

		if ( $propertyTable->isFixedPropertyTable() ) {
			return array();
		}

		return $this->buildPropertyList( $this->selectPropertiesFromTable( $propertyTable ) );
	}

	/**
	 * @since 2.2
	 *
	 * @return boolean
	 */
	public function isCached() {
		return false;
	}

	/**
	 * @since 2.2
	 *
	 * @return integer
	 */
	public function getTimestamp() {
		return wfTimestamp( TS_UNIX );
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getLookupIdentifier() {
		return __METHOD__ . json_encode( (array)$this->requestOptions );
	}

	private function selectPropertiesFromTable( $propertyTable ) {

		$options = $this->store->getSQLOptions( $this->requestOptions, 'title' );
		$options['ORDER BY'] = 'count DESC';

		$db = $this->store->getConnection( 'mw.db' );

		// TODO: this is not how JOINS should be specified in the select function
		$res = $db->select(
			$propertyTable->getName() . ' INNER JOIN ' .
			$db->tablename( $this->store->getObjectIds()->getIdTable() ) . ' ON p_id=smw_id LEFT JOIN ' .
			'page' . ' ON (page_namespace=' .
			$db->addQuotes( SMW_NS_PROPERTY ) . ' AND page_title=smw_title)',
			'smw_title, COUNT(*) as count',
			'smw_id > 50 AND page_id IS NULL GROUP BY smw_title',
			__METHOD__,
			$options
		);

		return $res;
	}

	private function buildPropertyList( $res ) {

		$result = array();

		foreach ( $res as $row ) {
			$result[] = array( $this->addPropertyFor( $row->smw_title ), $row->count );
		}

		return $result;
	}

	private function addPropertyFor( $title ) {

		try {
			$property = new DIProperty( $title );
		} catch ( InvalidPropertyException $e ) {
			$property = new DIError( new \Message( 'smw_noproperty', array( $title ) ) );
		}

		return $property;
	}

	private function getPropertyTableForType( $type ) {

		$propertyTables = $this->store->getPropertyTables();
		$tableIdForType = $this->store->findTypeTableId( $type );

		if ( isset( $propertyTables[$tableIdForType] ) ) {
			return $propertyTables[$tableIdForType];
		}

		throw new RuntimeException( "Tried to access a table that doesn't exist for {$type}." );
	}

}

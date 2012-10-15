<?php
/**
 * The class in this file provides a container for chunks of subject-centred
 * data.
 *
 * @file
 * @ingroup SMW
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */

/**
 * Class for representing chunks of semantic data for one given
 * article (subject), similar what is typically displayed in the Factbox.
 * This is a light-weight data container.
 *
 * By its very design, the container is unable to hold inverse properties.
 * For one thing, it would not be possible to identify them with mere keys.
 * Since SMW cannot annotate pages with inverses, this is not a limitation.
 *
 * @ingroup SMW
 */
class SMWSemanticData {

	/**
	 * Cache for the localized version of the namespace prefix "Property:".
	 *
	 * @var string
	 */
	static protected $mPropertyPrefix = '';

	/**
	 * States whether this is a stub object. Stubbing might happen on
	 * serialisation to save DB space.
	 *
	 * @todo Check why this is public and document this here. Or fix it.
	 *
	 * @var boolean
	 */
	public $stubObject;

	/**
	 * Array mapping property keys (string) to arrays of SMWDataItem
	 * objects.
	 *
	 * @var array
	 */
	protected $mPropVals = array();

	/**
	 * Array mapping property keys (string) to SMWDIProperty objects.
	 *
	 * @var array
	 */
	protected $mProperties = array();

	/**
	 * States whether the container holds any normal properties.
	 *
	 * @var boolean
	 */
	protected $mHasVisibleProps = false;

	/**
	 * States whether the container holds any displayable predefined
	 * $mProperties (as opposed to predefined properties without a display
	 * label). For some settings we need this to decide if a Factbox is
	 * displayed.
	 *
	 * @var boolean
	 */
	protected $mHasVisibleSpecs = false;

	/**
	 * States whether repeated values should be avoided. Not needing
	 * duplicate elimination (e.g. when loading from store) can save some
	 * time, especially in subclasses like SMWSqlStubSemanticData, where
	 * the first access to a data item is more costy.
	 *
	 * @note This setting is merely for optimization. The SMW data model
	 * never cares about the multiplicity of identical data assignments.
	 *
	 * @var boolean
	 */
	protected $mNoDuplicates;

	/**
	 * SMWDIWikiPage object that is the subject of this container.
	 * Subjects can never be null (and this is ensured in all methods setting
	 * them in this class).
	 *
	 * @var SMWDIWikiPage
	 */
	protected $mSubject;

	/**
	 * subSemanticData objects associated with this SemanticData
	 * These key-value pairs of subObjectName (string) =>SMWSemanticData.
	 *
	 * @since 1.8
	 * @var Array
	 */
	protected $subSemanticData = array();

	/**
	 * Constructor.
	 *
	 * @param SMWDIWikiPage $subject to which this data refers
	 * @param boolean $noDuplicates stating if duplicate data should be avoided
	 */
	public function __construct( SMWDIWikiPage $subject, $noDuplicates = true ) {
		$this->clear();
		$this->mSubject = $subject;
		$this->mNoDuplicates = $noDuplicates;
	}

	/**
	 * This object is added to the parser output of MediaWiki, but it is
	 * not useful to have all its data as part of the parser cache since
	 * the data is already stored in more accessible format in SMW. Hence
	 * this implementation of __sleep() makes sure only the subject is
	 * serialised, yielding a minimal stub data container after
	 * unserialisation. This is a little safer than serialising nothing:
	 * if, for any reason, SMW should ever access an unserialised parser
	 * output, then the Semdata container will at least look as if properly
	 * initialised (though empty).
	 *
	 * @return array
	 */
	public function __sleep() {
		return array( 'mSubject' );
	}

	/**
	 * Return subject to which the stored semantic annotations refer to.
	 *
	 * @return SMWDIWikiPage subject
	 */
	public function getSubject() {
		return $this->mSubject;
	}

	/**
	 * Get the array of all properties that have stored values.
	 *
	 * @return array of SMWDIProperty objects
	 */
	public function getProperties() {
		ksort( $this->mProperties, SORT_STRING );
		return $this->mProperties;
	}

	/**
	 * Get the array of all stored values for some property.
	 *
	 * @param $property SMWDIProperty
	 * @return array of SMWDataItem
	 */
	public function getPropertyValues( SMWDIProperty $property ) {
		if ( $property->isInverse() ) { // we never have any data for inverses
			return array();
		}

		if ( array_key_exists( $property->getKey(), $this->mPropVals ) ) {
			return $this->mPropVals[$property->getKey()];
		} else {
			return array();
		}
	}

	/**
	 * Generate a hash value to simplify the comparison of this data
	 * container with other containers. The hash uses PHP's md5
	 * implementation, which is among the fastest hash algorithms that
	 * PHP offers.
	 *
	 * @return string
	 */
	public function getHash() {
		$ctx = hash_init( 'md5' );

		// here and below, use "_#_" to separate values; really not much care needed here
		hash_update( $ctx, '_#_' . $this->mSubject->getSerialization() );

		foreach ( $this->getProperties() as $property ) {
			hash_update( $ctx, '_#_' . $property->getKey() . '##' );

			foreach ( $this->getPropertyValues( $property ) as $di ) {
				hash_update( $ctx, '_#_' . $di->getSerialization() );
			}
		}

		return hash_final( $ctx );
	}

	/**
	 * Return the array of subSemanticData objects for this SemanticData
	 *
	 * @since 1.8
	 * @return array of subobject => SMWContainerSemanticData objects
	 */
	public function getSubSemanticData() {
		return $this->subSemanticData;
	}

	/**
	 * Return true if there are any visible properties.
	 *
	 * @note While called "visible" this check actually refers to the
	 * function SMWDIProperty::isShown(). The name is kept for
	 * compatibility.
	 *
	 * @return boolean
	 */
	public function hasVisibleProperties() {
		return $this->mHasVisibleProps;
	}

	/**
	 * Return true if there are any special properties that can
	 * be displayed.
	 *
	 * @note While called "visible" this check actually refers to the
	 * function SMWDIProperty::isShown(). The name is kept for
	 * compatibility.
	 *
	 * @return boolean
	 */
	public function hasVisibleSpecialProperties() {
		return $this->mHasVisibleSpecs;
	}

	/**
	 * Store a value for a property identified by its SMWDataItem object.
	 *
	 * @note There is no check whether the type of the given data item
	 * agrees with the type of the property. Since property types can
	 * change, all parts of SMW are prepared to handle mismatched data item
	 * types anyway.
	 *
	 * @param $property SMWDIProperty
	 * @param $dataItem SMWDataItem
	 */
	public function addPropertyObjectValue( SMWDIProperty $property, SMWDataItem $dataItem ) {
		if( $dataItem instanceof SMWDIContainer ) {
			$this->addSubSemanticData( $dataItem->getSemanticData() );
			$dataItem = $dataItem->getSemanticData()->getSubject();
		}

		if ( $property->isInverse() ) { // inverse properties cannot be used for annotation
			return;
		}

		if ( !array_key_exists( $property->getKey(), $this->mPropVals ) ) {
			$this->mPropVals[$property->getKey()] = array();
			$this->mProperties[$property->getKey()] = $property;
		}

		if ( $this->mNoDuplicates ) {
			$this->mPropVals[$property->getKey()][$dataItem->getHash()] = $dataItem;
		} else {
			$this->mPropVals[$property->getKey()][] = $dataItem;
		}

		if ( !$property->isUserDefined() ) {
			if ( $property->isShown() ) {
				$this->mHasVisibleSpecs = true;
				$this->mHasVisibleProps = true;
			}
		} else {
			$this->mHasVisibleProps = true;
		}
	}

	/**
	 * Store a value for a given property identified by its text label
	 * (without namespace prefix).
	 *
	 * @param $propertyName string
	 * @param $dataItem SMWDataItem
	 */
	public function addPropertyValue( $propertyName, SMWDataItem $dataItem ) {
		$propertyKey = smwfNormalTitleDBKey( $propertyName );

		if ( array_key_exists( $propertyKey, $this->mProperties ) ) {
			$property = $this->mProperties[$propertyKey];
		} else {
			if ( self::$mPropertyPrefix === '' ) {
				global $wgContLang;
				self::$mPropertyPrefix = $wgContLang->getNsText( SMW_NS_PROPERTY ) . ':';
			} // explicitly use prefix to cope with things like [[Property:User:Stupid::somevalue]]

			$propertyDV = SMWPropertyValue::makeUserProperty( self::$mPropertyPrefix . $propertyName );

			if ( !$propertyDV->isValid() ) { // error, maybe illegal title text
				return;
			}

			$property = $propertyDV->getDataItem();
		}

		$this->addPropertyObjectValue( $property, $dataItem );
	}

	/**
	 * Remove a value for a property identified by its SMWDataItem object.
	 * This method removes a property-value specified by the property and
	 * dataitem. If there are no more property-values for this property it
	 * also removes the property from the mProperties.
	 *
	 * @note There is no check whether the type of the given data item
	 * agrees with the type of the property. Since property types can
	 * change, all parts of SMW are prepared to handle mismatched data item
	 * types anyway.
	 *
	 * @param $property SMWDIProperty
	 * @param $dataItem SMWDataItem
	 *
	 * @since 1.8
	 */
	public function removePropertyObjectValue( SMWDIProperty $property, SMWDataItem $dataItem ) {
		//delete associated subSemanticData
		if( $dataItem instanceof SMWDIContainer ) {
			$this->removeSubSemanticData( $dataItem->getSemanticData() );
			$dataItem = $dataItem->getSemanticData()->getSubject();
		}

		if ( $property->isInverse() ) { // inverse properties cannot be used for annotation
			return;
		}

		if ( !array_key_exists( $property->getKey(), $this->mPropVals ) || !array_key_exists( $property->getKey(), $this->mProperties ) ) {
			return;
		}

		if ( $this->mNoDuplicates ) {
			//this didn't get checked for my tests, but should work
			unset( $this->mPropVals[$property->getKey()][$dataItem->getHash()] );
		} else {
			foreach( $this->mPropVals[$property->getKey()] as $index => $di ) {
				if( $di->equals( $dataItem ) )
					unset( $this->mPropVals[$property->getKey()][$index] );
			}
			$this->mPropVals[$property->getKey()] = array_values( $this->mPropVals[$property->getKey()] );
		}

		if ( $this->mPropVals[$property->getKey()] === array() ) {
			unset( $this->mProperties[$property->getKey()] );
			unset( $this->mPropVals[$property->getKey()] );
		}
	}

	/**
	 * Delete all data other than the subject.
	 */
	public function clear() {
		$this->mPropVals = array();
		$this->mProperties = array();
		$this->mHasVisibleProps = false;
		$this->mHasVisibleSpecs = false;
		$this->stubObject = false;
		$this->subSemanticData = array();
	}

	/**
	 * Return true if this SemanticData is empty.
	 * Assumes that the mProperties array is always
	 * made empty when there is no data in mPropVals
	 *
	 * since 1.8
	 * @return boolean
	 */
	public function isEmpty() {
		return $this->mProperties == array() && $this->subSemanticData == array();
	}

	/**
	 * Add all data from the given SMWSemanticData.
	 *
	 * @since 1.7
	 *
	 * @param $semanticData SMWSemanticData object to copy from
	 */
	public function importDataFrom( SMWSemanticData $semanticData ) {
		// drop if subjects don't match. Different subjects don't have their subSemanticData compatible to each other
		if( !$this->mSubject->equals( $semanticData->getSubject() ) )
			return;
		// Shortcut when copying into empty objects that don't ask for more duplicate elimination:
		if ( count( $this->mProperties ) == 0 &&
		     ( $semanticData->mNoDuplicates >= $this->mNoDuplicates ) ) {
			$this->mProperties = $semanticData->getProperties();
			$this->mPropVals = array();
			foreach ( $this->mProperties as $property ) {
				$this->mPropVals[$property->getKey()] = $semanticData->getPropertyValues( $property );
			}
			$this->mHasVisibleProps = $semanticData->hasVisibleProperties();
			$this->mHasVisibleSpecs = $semanticData->hasVisibleSpecialProperties();
		} else {
			foreach ( $semanticData->getProperties() as $property ) {
				$values = $semanticData->getPropertyValues( $property );
				foreach ( $values as $dataItem ) {
					$this->addPropertyObjectValue( $property, $dataItem);
				}
			}
		}
		foreach( $semanticData->subSemanticData as $semData ) {
			$this->addSubSemanticData( $semData );
		}
	}

	/**
	 * Removes all common data present in the given SMWSemanticData.
	 *
	 * @since 1.8
	 *
	 * @param $semanticData SMWSemanticData
	 */
	public function removeDataFrom( SMWSemanticData $semanticData ) {
		// drop if subjects don't match. Different subjects don't have their subSemanticData compatible to each other
		if( !$this->mSubject->equals( $semanticData->getSubject() ) )
		foreach ( $semanticData->getProperties() as $property ) {
			$values = $semanticData->getPropertyValues( $property );
			foreach ( $values as $dataItem ) {
				$this->removePropertyObjectValue( $property, $dataItem );
			}
		}
		foreach( $semanticData->subSemanticData as $semData ) {
			$this->removeSubSemanticData( $semData );
		}
	}

	/**
	* Method to add Container items into subSemanticData
	* This method will merge data if they are of the same subobject.
	* Its assumed that the container items all belong to the same subject.
	*
	* @since 1.8
	* @param SMWSemanticData
	*/
	public function addSubSemanticData( SMWSemanticData $semanticData ) {
		if( $semanticData->getSubject()->getDBkey() !== $this->getSubject()->getDBkey() ) {
			throw new MWException( "SubSemanticData can only be assigned for sub-objects" );
		}

		$subobjectName = $semanticData->getSubject()->getSubobjectName();
		if( array_key_exists( $subobjectName, $this->subSemanticData ) ) {
			$this->subSemanticData[$subobjectName]->importDataFrom( $semanticData );
		} else {
			$this->subSemanticData[$subobjectName] = $semanticData;
		}
	}

	/**
	* Method to remove Container items from subSemanticData
	* This method will remove data from the appropriate subSemanticData
	* and delete it if its empty afterwards.
	*
	* @since 1.8
	* @param SMWSemanticData
	*/
	public function removeSubSemanticData( SMWSemanticData $semanticData ) {
		if( $semanticData->getSubject()->getDBkey() !== $this->getSubject()->getDBkey() ) {
			return;
		}

		$subobjectName = $container->getSubject()->getSubobjectName();
		if( array_key_exists( $subobjectName, $this->subSemanticData ) ) {
			$this->subSemanticData[$subobjectName]->removeDataFrom( $semanticData );

			if( $this->subSemanticData[$subobjectName]->isEmpty() ) {
				unset( $this->subSemanticData[$subobjectName] );
			}
		}
	}

}
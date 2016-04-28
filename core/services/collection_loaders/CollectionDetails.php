<?php
namespace EventEspresso\core\services\collection_loaders;

use EventEspresso\Core\Exceptions\InvalidClassException;
use EventEspresso\Core\Exceptions\InvalidFilePathException;
use EventEspresso\Core\Exceptions\InvalidIdentifierException;
use EventEspresso\Core\Exceptions\InvalidInterfaceException;

if ( ! defined( 'EVENT_ESPRESSO_VERSION' ) ) {
	exit( 'No direct script access allowed' );
}



/**
 * Class CollectionDetails
 *
 * Abstract parent class for defining classes for loading into a collection.
 * The supplied interface will be used for type hinting the objects being loaded.
 * Classes can either be located by supplying an array of FQCNs (Fully Qualified Class Names),
 * or an array of full server filepaths to a set of files,
 * where the classnames match the filenames minus all extensions
 *  for example:
 *  $FQCNs = array(
 *      '/Fully/Qualified/ClassNameA'
 *      '/Fully/Qualified/Other/ClassNameB'
 *  );
 *  $paths = array(
 *      '/full/server/path/to/ClassNameA.ext.php' // for class ClassNameA
 *      '/full/server/path/to/other/ClassNameB.php' // for class ClassNameB
 *  );
 *
 * @package       Event Espresso
 * @subpackage    core
 * @author        Brent Christensen
 * @since         4.9.0
 */
abstract class CollectionDetails implements CollectionDetailsInterface {

	const ID_OBJECT_HASH = 1;

	const ID_CLASS_NAME = 2;

	/**
	 * The interface used for controlling what gets added to the collection
	 *
	 * @var string $collection_interface
	 */
	protected $collection_interface = '';

	/**
	 * a unique name used to identify the collection in filter names
	 * supplied value is run through sanitize_title_with_dashes(),
	 * but then also converts dashes to underscores
	 *
	 * @var string $collection_name
	 */
	protected $collection_name = '';

	/**
	 * what the collection uses for the object identifier.
	 * corresponds to one of the class constants above.
	 * CollectionDetails::ID_OBJECT_HASH will use spl_object_hash( object ) for the identifier
	 * CollectionDetails::ID_CLASS_NAME will use get_class( object ) for the identifier
	 * defaults to using spl_object_hash() so that multiple objects of the same class can be added
	 *
	 * @var string $file_mask
	 */
	protected $identifier_type = CollectionDetails::ID_OBJECT_HASH;

	/**
	 * the pattern applied to paths when searching for class files to add to the collection
	 *
	 * @var string $file_mask
	 */
	protected $file_mask = '';

	/**
	 * an array of Fully Qualified Class Names
	 *
	 * @var array $collection_FQCNs
	 */
	protected $collection_FQCNs = array();

	/**
	 * an array of full server paths to folders containing files to be loaded into collection
	 *
	 * @var array $collection_paths
	 */
	protected $collection_paths = array();



	/**
	 * CollectionDetails constructor.
	 *
	 * @param        $collection_name
	 * @param string $collection_interface
	 * @param array  $collection_FQCNs
	 * @param array  $collection_paths
	 * @param string $file_mask
	 * @param int    $identifier_type
	 * @throws \EventEspresso\Core\Exceptions\InvalidClassException
	 * @throws \EventEspresso\Core\Exceptions\InvalidFilePathException
	 * @throws \EventEspresso\Core\Exceptions\InvalidIdentifierException
	 * @throws \EventEspresso\Core\Exceptions\InvalidInterfaceException
	 */
	public function __construct(
		$collection_name,
		$collection_interface,
		$collection_FQCNs = array(),
		$collection_paths = array(),
		$file_mask = '',
		$identifier_type = 1
	) {
		$this->setCollectionInterface( $collection_interface );
		$this->setCollectionFQCNs( $collection_FQCNs );
		$this->setCollectionPaths( $collection_paths );
		$this->setFileMasks( $file_mask );
		$this->setIdentifierType( $identifier_type );

	}



	/**
	 * @return mixed
	 */
	public function getCollectionInterface() {
		return $this->collection_interface;
	}



	/**
	 * @access protected
	 * @param string $collection_interface
	 * @throws \EventEspresso\Core\Exceptions\InvalidInterfaceException
	 */
	protected function setCollectionInterface( $collection_interface ) {
		if ( ! ( interface_exists( $collection_interface ) || class_exists( $collection_interface ) ) ) {
			throw new InvalidInterfaceException( $collection_interface );
		}
		$this->collection_interface = $collection_interface;
	}



	/**
	 * the collection name will be used for creating dynamic filters
	 *
	 * @return string
	 */
	public function collectionName() {
		return $this->collection_name;
	}



	/**
	 * sanitizes collection name and converts spaces and dashes to underscores
	 *
	 * @access protected
	 * @param string $collection_name
	 */
	protected function setCollectionName( $collection_name ) {
		$this->collection_name = str_replace(
			'-',
			'_',
			sanitize_title_with_dashes( $collection_name, '', 'save' )
		);
	}



	/**
	 * @access public
	 * @return string
	 */
	public function identifierType() {
		return $this->identifier_type;
	}



	/**
	 * @access protected
	 * @param string $identifier_type
	 * @throws \EventEspresso\Core\Exceptions\InvalidIdentifierException
	 */
	protected function setIdentifierType( $identifier_type ) {
		if (
			! (
				$identifier_type === CollectionDetails::ID_CLASS_NAME
				|| $identifier_type === CollectionDetails::ID_OBJECT_HASH
			)
		) {
			throw new InvalidIdentifierException(
				'1 ( CollectionDetails::ID_CLASS_NAME ) or 2 ( CollectionDetails::ID_OBJECT_HASH )',
				$identifier_type
			);
		}
		$this->identifier_type = $identifier_type;
	}



	/**
	 * @return array
	 */
	public function getFileMask() {
		return $this->file_mask;
	}



	/**
	 * @param string $file_mask
	 */
	public function setFileMasks( $file_mask ) {
		$this->file_mask = ! empty( $file_mask ) ? $file_mask : '*.php';
	}



	/**
	 * @return string
	 */
	public function getCollectionFQCNs() {
		return $this->collection_FQCNs;
	}



	/**
	 * @param string|array $collection_FQCNs
	 * @throws \EventEspresso\Core\Exceptions\InvalidClassException
	 */
	public function setCollectionFQCNs( $collection_FQCNs ) {
		foreach ( (array) $collection_FQCNs as $collection_FQCN ) {
			if ( ! empty( $collection_FQCN ) ) {
				if ( ! class_exists( $collection_FQCN ) ) {
					throw new InvalidClassException( $collection_FQCN );
				}
				$this->collection_FQCNs[] = $collection_FQCN;
			}
		}
	}



	/**
	 * @return string
	 */
	public function getCollectionPaths() {
		return $this->collection_paths;
	}



	/**
	 * @param string|array $collection_paths
	 * @throws \EventEspresso\Core\Exceptions\InvalidFilePathException
	 */
	public function setCollectionPaths( $collection_paths ) {
		foreach ( (array) $collection_paths as $collection_path ) {
			if ( ! empty( $collection_path ) ) {
				if ( ! is_readable( $collection_path ) ) {
					throw new InvalidFilePathException( $collection_path );
				}
				$this->collection_paths[] = $collection_path;
			}
		}
	}



}
// End of file CollectionDetails.php
// Location: /CollectionDetails.php
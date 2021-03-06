<?php

/**
 * srCertificateTemplateType
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @version
 */
abstract class srCertificateTemplateType {

	const TEMPLATE_TYPE_JASPER = 1;
	const TEMPLATE_TYPE_HTML = 2;
	/**
	 * @var int
	 */
	protected $id = 0;
	/**
	 * @var string
	 */
	protected $title = '';
	/**
	 * @var string
	 */
	protected $description = '';
	/**
	 * @var string
	 */
	protected $template_filename = '';
	/**
	 * @var array
	 */
	protected $valid_suffixes = array();
	/**
	 * @var ilLog
	 */
	protected $log;
	/**
	 * @var ilCertificatePlugin
	 */
	protected $pl;


	public function __construct() {
		global $DIC;
		$this->log = $DIC["ilLog"];
		$this->pl = ilCertificatePlugin::getInstance();
		// Concrete classes must set their properties here...
	}


	// Public


	/**
	 * Generate the report for given certificate
	 *
	 * @param srCertificate $certificate
	 *
	 * @return bool
	 */
	abstract public function generate(srCertificate $certificate);


	/**
	 * Return false if the template type is not available for rendering certificates
	 *
	 * @return bool
	 */
	public function isAvailable() {
		return true;
	}

	// Getters & Setters


	/**
	 * @param int $id
	 */
	public function setId($id) {
		$this->id = $id;
	}


	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}


	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}


	/**
	 * @param string $template_filename
	 */
	public function setTemplateFilename($template_filename) {
		$this->template_filename = $template_filename;
	}


	/**
	 * @return string
	 */
	public function getTemplateFilename() {
		return $this->template_filename;
	}


	/**
	 * @param string $description
	 */
	public function setDescription($description) {
		$this->description = $description;
	}


	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}


	/**
	 * @return array
	 */
	public function getValidSuffixes() {
		return $this->valid_suffixes;
	}


	/**
	 * @param array $valid_suffixes
	 */
	public function setValidSuffixes($valid_suffixes) {
		$this->valid_suffixes = $valid_suffixes;
	}
}

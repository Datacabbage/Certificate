<?php
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Certificate Plugin
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 * @version $Id$
 *
 */
class ilCertificatePlugin extends ilUserInterfaceHookPlugin {

	const PLUGIN_ID = 'cert';
	const PLUGIN_NAME = 'Certificate';
	/**
	 * Name of class that can implement hooks
	 */
	const CLASS_NAME_HOOKS = 'srCertificateCustomHooks';
	/**
	 * Default path for hook class (can be changed in plugin config)
	 */
	const DEFAULT_PATH_HOOK_CLASS = './Customizing/global/Certificate/';
	/**
	 * Default formats (can be changed in plugin config)
	 */
	const DEFAULT_DATE_FORMAT = 'Y-m-d';
	const DEFAULT_DATETIME_FORMAT = 'Y-m-d, H:i';
	const DEFAULT_DISK_SPACE_WARNING = 10;
	/**
	 * Default permission settings
	 */
	const DEFAULT_ROLES_ADMINISTRATE_CERTIFICATES = '["2"]';
	const DEFAULT_ROLES_ADMINISTRATE_CERTIFICATE_TYPES = '["2"]';
	/**
	 * @var srCertificateHooks
	 */
	protected $hooks;
	/**
	 * @var ilCertificatePlugin
	 */
	protected static $instance;
	/**
	 * @var ilPluginAdmin
	 */
	protected $ilPluginAdmin;
	/**
	 * @var ilTree
	 */
	protected $tree;
	/**
	 * @var ilDB
	 */
	protected $db;


	/**
	 * @return ilCertificatePlugin
	 */
	public static function getInstance() {
		if (is_null(static::$instance)) {
			static::$instance = new static();
		}

		return static::$instance;
	}


	protected function init() {
		parent::init();
		if (isset($_GET['ulx'])) {
			$this->updateLanguages();
		}
	}


	/**
	 * @return string
	 */
	public function getPluginName() {
		return self::PLUGIN_NAME;
	}


	public function __construct() {
		parent::__construct();
		global $DIC;

		$this->ilPluginAdmin = $DIC["ilPluginAdmin"];
		$this->tree = $DIC->repositoryTree();
		$this->db = $DIC->database();
	}


	/**
	 * Get a config value
	 *
	 * @param string $name
	 *
	 * @return string|null
	 */
	public function config($name) {
		return ilCertificateConfig::getX($name);
	}


	/**
	 * Get Hooks object
	 *
	 * @return srCertificateHooks
	 */
	public function getHooks() {
		if (is_null($this->hooks)) {
			$class_name = self::CLASS_NAME_HOOKS;
			$path = ilCertificateConfig::getX('path_hook_class');
			if (substr($path, - 1) !== '/') {
				$path .= '/';
			}
			$file = $path . "class.{$class_name}.php";
			if (is_file($file)) {
				require_once $file;
				$object = new $class_name($this);
			} else {
				$object = new srCertificateHooks($this);
			}
			$this->hooks = $object;
		}

		return $this->hooks;
	}


	/**
	 * Check if course is a "template course"
	 * This method returns true if the given ref-ID is a children of a category defined in the plugin options
	 *
	 * @param int $ref_id Ref-ID of the object to check
	 *
	 * @return bool
	 */
	public function isCourseTemplate($ref_id) {
		if (ilCertificateConfig::getX('course_templates') && ilCertificateConfig::getX('course_templates_ref_ids')) {
			// Course templates enabled -> check if given ref_id is defined as template
			$ref_ids = explode(',', ilCertificateConfig::getX('course_templates_ref_ids'));
			$parent_ref_id = $this->tree->repositoryTree()->getParentId($ref_id);

			return in_array($parent_ref_id, $ref_ids);
		}

		return false;
	}


	/**
	 * Check if preconditions are given to use this plugin
	 *
	 * @return bool
	 */
	public function checkPreConditions() {
		$exists = $this->ilPluginAdmin->exists(IL_COMP_SERVICE, 'EventHandling', 'evhk', 'CertificateEvents');
		$active = $this->ilPluginAdmin->isActive(IL_COMP_SERVICE, 'EventHandling', 'evhk', 'CertificateEvents');

		return ($exists && $active);
	}


	/**
	 * Don't activate plugin if preconditions are not given
	 *
	 * @return bool
	 */
	protected function beforeActivation() {
		if (!$this->checkPreConditions()) {
			ilUtil::sendFailure("You need to install the 'CertificateEvents' plugin");

			return false;
		}

		return true;
	}


	/**
	 * @return string
	 */
	public static function getPluginIconImage() {
		return ilUtil::getImagePath('icon_cert.svg');
	}


	/**
	 * @return bool
	 */
	protected function beforeUninstall() {
		$this->db->dropTable(ilCertificateConfig::TABLE_NAME, false);
		$this->db->dropTable(srCertificateType::TABLE_NAME, false);
		$this->db->dropTable(srCertificateDefinition::TABLE_NAME, false);
		$this->db->dropTable(srCertificatePlaceholder::TABLE_NAME, false);
		$this->db->dropTable(srCertificatePlaceholderValue::TABLE_NAME, false);
		$this->db->dropTable(srCertificate::TABLE_NAME, false);
		$this->db->dropTable(srCertificateTypeSetting::TABLE_NAME, false);
		$this->db->dropTable(srCertificateDefinitionSetting::TABLE_NAME, false);
		$this->db->dropTable(srCertificateSignatureDef::TABLE_NAME, false);
		$this->db->dropTable(srCertificateCustomDefinitionSetting::TABLE_NAME, false);
		$this->db->dropTable(srCertificateCustomTypeSetting::TABLE_NAME, false);

		ilUtil::delDir(CLIENT_DATA_DIR . '/cert_signatures');
		ilUtil::delDir(CLIENT_DATA_DIR . '/cert_templates');
		ilUtil::delDir(CLIENT_DATA_DIR . '/cert_data');

		return true;
	}
}

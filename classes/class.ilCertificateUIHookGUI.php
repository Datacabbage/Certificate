<?php
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * User interface hook class for CourseCertificate-Plugin
 *
 * @author  Alex Killing <alex.killing@gmx.de>
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 *
 * @version $Id$
 * @ingroup ServicesUIComponent
 */
class ilCertificateUIHookGUI extends ilUIHookPluginGUI {

	const TAB_CERTIFICATE = 'certificate';
	/**
	 * @var ilCtrl
	 */
	public $ctrl;
	/**
	 * @var ilCertificatePlugin
	 */
	protected $pl;
	/**
	 * @var ilObjUser
	 */
	protected $user;
	/**
	 * @var ilAccessHandler
	 */
	protected $access;


	public function __construct() {
		global $DIC;
		$this->ctrl = $DIC->ctrl();
		$this->pl = ilCertificatePlugin::getInstance();
		$this->user = $DIC->user();
		$this->access = $DIC->access();
	}


	/**
	 * @param       $a_comp
	 * @param       $a_part
	 * @param array $a_par
	 */
	public function modifyGUI($a_comp, $a_part, $a_par = array()) {
		/**
		 * @var $ilTabsGUI ilTabsGUI
		 */
		// ATM only display certificate tab in courses
		if ($a_part == 'tabs' && $this->ctrl->getContextObjType() == 'crs' && isset($_GET['ref_id']) && $this->ctrl->getCmdClass() == 'ilrepositorygui') {
			// User needs write access to course to see the tab 'certificate'
			if ($this->access->checkAccess('write', '', (int)$_GET['ref_id'])) {
				$ilTabsGUI = $a_par['tabs'];
				$this->ctrl->setParameterByClass(srCertificateDefinitionGUI::class, 'ref_id', $_GET['ref_id']);
				$ilTabsGUI->addTab(self::TAB_CERTIFICATE, $this->pl->txt('certificate'), $this->ctrl->getLinkTargetByClass(array(
					ilUIPluginRouterGUI::class,
					srCertificateDefinitionGUI::class
				)));
			}
		}
	}
}

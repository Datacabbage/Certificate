<#1>
	<?php
	require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/class.ilCertificatePlugin.php');
    ilCertificateConfig::installDB();
	?>
<#2>
    <?php
    /*
     * Create tables
     */
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Type/class.srCertificateType.php');
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Definition/class.srCertificateDefinition.php');
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Placeholder/class.srCertificatePlaceholder.php');
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Placeholder/class.srCertificatePlaceholderValue.php');
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Certificate/class.srCertificate.php');
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Definition/class.srCertificateDefinitionSetting.php');
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Type/class.srCertificateTypeSetting.php');

    srCertificateType::installDB();
    srCertificateDefinition::installDB();
    srCertificatePlaceholder::installDB();
    srCertificatePlaceholderValue::installDB();
    srCertificate::installDB();
    srCertificateTypeSetting::installDB();
    srCertificateDefinitionSetting::installDB();
?>
<#3>
    <?php
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Certificate/class.srCertificate.php');
    srCertificate::updateDB();
    ?>
<#4>
    <?php

    /*
    * Add new setting "notification_user" to certificate types and every existing definitions
    */

    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Definition/class.srCertificateDefinition.php');
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Type/class.srCertificateType.php');

    $types = srCertificateType::get();
    /** @var srCertificateType $type */
    foreach ($types as $type) {
        if (is_null($type->getSettingByIdentifier(srCertificateTypeSetting::IDENTIFIER_NOTIFICATION_USER))) {
            $setting = new srCertificateTypeSetting();
            $setting->setIdentifier(srCertificateTypeSetting::IDENTIFIER_NOTIFICATION_USER);
            $setting->setValue(0);
            $setting->setEditableIn($type->getAvailableObjects());
            $setting->setTypeId($type->getId());
            $setting->create();
        }
    }

    $definitions = srCertificateDefinition::get();
    /** @var srCertificateDefinition $def */
    foreach ($definitions as $def) {
        if (is_null($def->getSettingByIdentifier(srCertificateTypeSetting::IDENTIFIER_NOTIFICATION_USER))) {
            $setting = new srCertificateDefinitionSetting();
            $setting->setIdentifier(srCertificateTypeSetting::IDENTIFIER_NOTIFICATION_USER);
            $setting->setValue(0);
            $setting->setDefinitionId($def->getId());
            $setting->create();
        }
    }

    ?>
<#5>
    <?php
    /*
     * Add default values for new config settings
     */
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/class.ilCertificatePlugin.php');

    ilCertificateConfig::set(ilCertificateConfig::DATE_FORMAT, ilCertificatePlugin::DEFAULT_DATE_FORMAT);
    ilCertificateConfig::set(ilCertificateConfig::DATETIME_FORMAT, ilCertificatePlugin::DEFAULT_DATETIME_FORMAT);
    ilCertificateConfig::set(ilCertificateConfig::PATH_HOOK_CLASS, ilCertificatePlugin::DEFAULT_PATH_HOOK_CLASS);
    ilCertificateConfig::set(ilCertificateConfig::ROLES_ADMINISTRATE_CERTIFICATES, ilCertificatePlugin::DEFAULT_ROLES_ADMINISTRATE_CERTIFICATES);
    ilCertificateConfig::set(ilCertificateConfig::ROLES_ADMINISTRATE_CERTIFICATE_TYPES, ilCertificatePlugin::DEFAULT_ROLES_ADMINISTRATE_CERTIFICATE_TYPES);

    ?>
<#6>
    <?php
    // Update database schema, added created_at timestamp and active flag to certificates
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Certificate/class.srCertificate.php');
    srCertificate::updateDB();
    ?>
<#7>
    <?php
    // Flag latest version of each certificate as active
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Certificate/class.srCertificate.php');
    /** @var ilDB $ilDB */
    $set = $ilDB->query('SELECT user_id, definition_id, MAX(file_version) AS max_file_version FROM cert_obj GROUP BY definition_id, user_id');
    while ($row = $ilDB->fetchObject($set)) {
        /** @var srCertificate $cert */
        $cert = srCertificate::where(array(
            'definition_id' => $row->definition_id,
            'user_id' => $row->user_id,
            'file_version' => $row->max_file_version,
        ))->first();
        if ($cert) {
            $cert->setActive(true);
            $cert->save();
        }
    }
    ?>
<#8>
    <?php
    if ( ! $ilDB->tableColumnExists('cert_type_setting', 'value')) {
        $ilDB->renameTableColumn('cert_type_setting', 'default_value', 'value');
    }
    ?>
<#9>
    <?php
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/CustomSetting/class.srCertificateCustomTypeSetting.php');
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/CustomSetting/class.srCertificateCustomDefinitionSetting.php');

    srCertificateCustomTypeSetting::installDB();
    srCertificateCustomDefinitionSetting::installDB();
    ?>
<#10>
    <?php
    if ( ! $ilDB->tableColumnExists('uihkcertificate_c', 'value')) {
        $ilDB->renameTableColumn('uihkcertificate_c', 'config_value', 'value');
    }
    if ( ! $ilDB->tableColumnExists('uihkcertificate_c', 'name')) {
        $ilDB->renameTableColumn('uihkcertificate_c', 'config_key', 'name');
    }
    ?>
<#11>
	<?php
	// We will add one default certificate definition for easier installation.
	require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Definition/class.srCertificateDefinition.php');
	$type = new srCertificateType();
	$type->setTitle("Default Certificate");
	$type->setLanguages(array('en'));
	$type->setRoles(array(2)); //2 Is the default administration role.
	$type->setAvailableObjects(array('crs'));
    $type->setTemplateTypeId(1); // JasperReport
    $type->storeTemplateFileFromServer(ILIAS_ABSOLUTE_PATH . '/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/resources/template.jrxml');
	$type->create();

//	$placeholder = new srCertificatePlaceholder();
//	$placeholder->setCertificateType($type);
//	$placeholder->setIdentifier('crs_title');
//	$placeholder->setIsMandatory(true);
//	$placeholder->setEditableIn(array('crs'));
//	$placeholder->setLabel('Course Title', 'en');
//	$placeholder->create();
	?>
<#12>
    <?php
    // Add some new config settings
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/class.ilCertificateConfig.php');
    $body = "Hi,\n\n" .
            "A new certificate was generated for you:\n\n" .
            "User: [[USER_FULLNAME]]\n" .
            "Course: [[COURSE_TITLE]]\n" .
            "Valid until: [[CERT_VALID_TO]]\n\n" .
            "The certificate is attached in this email";
    ilCertificateConfig::set('notification_user_body', $body);
    ilCertificateConfig::set('notification_user_subject', 'New certificate generated for course [[COURSE_TITLE]]');
    ilCertificateConfig::set('notification_others_subject', 'New certificate generated for user [[USER_FULLNAME]]');
    $body = "Hi,\n\n" .
        "A new certificate was generated for user [[USER_FULLNAME]]:\n\n" .
        "Course: [[COURSE_TITLE]]\n" .
        "Valid until: [[CERT_VALID_TO]]\n\n" .
        "The certificate is attached in this email";
    ilCertificateConfig::set('notification_others_body', $body);

    ilCertificateConfig::set('max_diff_lp_seconds', 28800);
    ?>
<#13>
    <?php
    ilCertificateConfig::set(ilCertificateConfig::DISK_SPACE_WARNING, ilCertificatePlugin::DEFAULT_DISK_SPACE_WARNING);
    ?>
<#14>
    <?php
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Signature/class.srCertificateSignature.php');
    srCertificateSignature::installDB();
    ?>
<#15>
    <?php
        // Removed creation  of srCertificateSignatureDefinition
    ?>
<#16>
    <?php
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Definition/class.srCertificateDefinition.php');
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Definition/class.srCertificateDefinitionSetting.php');
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Type/class.srCertificateTypeSetting.php');
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Type/class.srCertificateType.php');
    foreach (srCertificateDefinition::get() as $cert_def) {
        $setting = new srCertificateDefinitionSetting();
        $setting->setDefinitionId($cert_def->getId());
        $setting->setIdentifier(srCertificateTypeSetting::IDENTIFIER_SCORM_TIMING);
        $setting->setValue(0);
        $setting->save();
    }

    foreach (srCertificateType::get() as $type) {
        $setting = new srCertificateTypeSetting();
        $setting->setTypeId($type->getId());
        $setting->setIdentifier(srCertificateTypeSetting::IDENTIFIER_SCORM_TIMING);
        $setting->setEditableIn(array('crs'));
        $setting->setValue(0);
        $setting->save();
    }
    ?>
<#17>
     <?php
        require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Definition/class.srCertificateDefinition.php');
        srCertificateDefinition::updateDB();

        // Migrate from signature table
        global $ilDB;
        if ($ilDB->tableExists('cert_signature_def')) {
            $set = $ilDB->query('SELECT * FROM cert_signature_def');
            while ($row = $ilDB->fetchObject($set)) {
                /** @var srCertificateDefinition $definition */
                $definition = srCertificateDefinition::find($row->definition_id);
                $definition->setSignatureId($row->signature_id);
                $definition->save();
            }
        }
     ?>
<#18>
<?php
// Change data-type for longer emails
global $ilDB;

if ($ilDB->tableExists('uihkcertificate_c')) {
    $ilDB->modifyTableColumn('uihkcertificate_c', 'value',
        array("type" => "clob", "default"=>null, "notnull" => false));
}
?>
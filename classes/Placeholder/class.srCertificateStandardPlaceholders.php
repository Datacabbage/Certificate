<?php
require_once('./Modules/Course/classes/class.ilObjCourse.php');
require_once('./Modules/Course/classes/class.ilCourseParticipants.php');
require_once('./Services/Tracking/classes/class.ilLearningProgress.php');
require_once("./Services/Tracking/classes/class.ilTrQuery.php");
require_once("./Services/Tracking/classes/class.ilLPStatusFactory.php");
require_once(dirname(dirname(__FILE__)) . '/Certificate/class.srCertificate.php');
require_once(dirname(dirname(__FILE__)) . '/class.ilCertificatePlugin.php');

/**
 * srCertificateStandardPlaceholder
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class srCertificateStandardPlaceholders
{

    /**
     * Prefix
     */
    const PREFIX_CUSTOM_SETTING = 'setting_';

    /**
     * Available Standard placeholders with identifier => description
     *
     * @var array
     */
    protected static $placeholders = array(
        'USER_LOGIN' => 'Login',
        'USER_FULLNAME' => 'Full name of the user (title, first name and last name)',
        'USER_FIRSTNAME' => 'First name of the user',
        'USER_LASTNAME' => 'Last name of the user',
        'USER_TITLE' => 'Title of the user',
        'USER_BIRTHDAY' => 'Birthday of the user',
        'USER_INSTITUTION' => 'Institution of the user',
        'USER_DEPARTMENT' => 'Department of the user',
        'USER_STREET' => "Street of the user address",
        'USER_CITY' => "City of the user address",
        'USER_ZIPCODE' => "ZIP code of the user address",
        'USER_COUNTRY' => "Country of the user address",
        'TIMESTAMP' => 'Current date in milliseconds since 01.01.1970',
        'DATE' => 'Actual date',
        'DATETIME' => 'Actual date and time',
        'DATE_COMPLETED' => 'Date of completion',
        'DATETIME_COMPLETED' => 'Date and time of completion',
        'CERT_VALID_FROM' => 'From validity date of certificate',
        'CERT_VALID_TO' => 'To validity date of certificate',
        'CERT_ID' => 'Unique numerical ID of certificate',
        'COURSE_TITLE' => 'Title of course',
        'LP_FIRST_ACCESS' => 'Learning progress: First access',
        'LP_LAST_ACCESS' => 'Learning progress: Last access',
        'LP_SPENT_TIME' => 'Learning progress: Time spent in course',
        'LP_SPENT_SECONDS' => 'Learning progress: Time spent in course (seconds)',
        'LP_READ_COUNT' => 'Learning progress: Read count',
        'LP_STATUS' => 'Learning progress: Status code',
        'LP_AVG_PERCENTAGE' => 'Learning progress: Avg. percentage of course',
        'CERT_TEMPLATE_PATH' => 'Path where certificate template file and assets are stored',
    );


    /**
     * @var srCertificate
     */
    protected $certificate;

    /**
     * @var ilCertificatePlugin
     */
    protected $pl;

    /**
     * Cache for the parsed placeholders
     *
     * @var array
     */
    protected $parsed_placeholders;

    /**
     * @var bool
     */
    protected $anonymized = false;


    /**
     * @param srCertificate $cert
     * @param bool $anonymized True to anonymize placeholder values
     */
    public function __construct(srCertificate $cert, $anonymized = false)
    {
        $this->certificate = $cert;
        $this->anonymized = $anonymized;
        $this->pl = ilCertificatePlugin::getInstance();
    }


    /**
     * Get all the standard identifiers
     *
     * @return array
     */
    public static function getPlaceholderIdentifiers()
    {
        return array_keys(self::$placeholders);
    }


    /**
     * Check if a given identifier is already reserved
     *
     * @param $identifier
     * @return bool
     */
    public static function isReservedIdentifier($identifier)
    {
        $identifiers = self::getPlaceholderIdentifiers();

        return in_array($identifier, $identifiers);
    }


    /**
     * @return array
     */
    public static function getStandardPlaceholders()
    {
        // TODO i18n
        return self::$placeholders;
    }


    /**
     * Return array containing the parsed (evaluated) standard placeholders
     *
     * @return array
     */
    public function getParsedPlaceholders()
    {
        if (!is_null($this->parsed_placeholders)) {
            return $this->parsed_placeholders;
        }
        // Initialize with empty values
        $this->parsed_placeholders = array();
        foreach (self::$placeholders as $k => $v) {
            $this->parsed_placeholders[$k] = '';
        }
        $user = $this->certificate->getUser();
        $course = new ilObjCourse($this->certificate->getDefinition()->getRefId());
        $this->parsed_placeholders = array_merge(
            $this->parsed_placeholders,
            $this->parseUserPlaceholders($user),
            $this->parseGeneralPlaceholders($course),
            $this->parseLearningProgressPlaceholders($course, $user),
            $this->parseCustomSettingsPlaceholders()
        );

        return $this->parsed_placeholders;
    }


    /**
     * Add custom settings
     *
     * @return array
     */
    protected function parseCustomSettingsPlaceholders()
    {
        $settings = array();
        foreach ($this->certificate->getDefinition()->getCustomSettings() as $setting) {
            $settings[self::PREFIX_CUSTOM_SETTING . $setting->getIdentifier()] = $setting->getValue();
        }

        return $settings;
    }


    /**
     * Parse general placeholders, mostly certificate data
     *
     * @param ilObjCourse $course
     * @return array
     */
    protected function parseGeneralPlaceholders(ilObjCourse $course)
    {
        $utc = ilCertificateConfig::get('time_format_utc');

        $cert_valid_from = strtotime($this->certificate->getValidFrom());
        $cert_valid_to = strtotime($this->certificate->getValidTo());
        if($utc) {
            // fix for timezone issue: when converting a mysql date into a timestamp and then into another timezone, its possible the date changes (because the start date is the first second of the day).
            // We now add 12*60*60 seconds to be in the middle of the day
            $cert_valid_to += srCertificate::TIME_ZONE_CORRECTION;
            $cert_valid_from += srCertificate::TIME_ZONE_CORRECTION;
        }

        $placeholder = array(
            'DATE' => $this->formatDate('DATE'),
            'DATETIME' => $this->formatDateTime('DATETIME'),
            'TIMESTAMP' => ($utc) ? strtotime(gmdate('Y-m-d H:i:s')) : time(),

            'CERT_VALID_FROM' => ($this->certificate->getValidFrom() == '')? $this->pl->txt('unlimited') : $this->formatDate('CERT_VALID_FROM', $cert_valid_from),
            'CERT_VALID_TO' =>  ($this->certificate->getValidTo() == '')? $this->pl->txt('unlimited') : $this->formatDate('CERT_VALID_TO', $cert_valid_to),
            'CERT_ID' => $this->certificate->getId(),
            'CERT_TEMPLATE_PATH' => $this->certificate->getDefinition()->getType()->getCertificateTemplatesPath(),
            'COURSE_TITLE' => $course->getTitle(),
        );

        return $placeholder;
    }


    /**
     * Helper function to get a formatted date.
     * This method checks the if the date should be calculated in GMT or UTC.
     * In addition, all values are passed to the hooks class allowing for custom modification.
     *
     * @param identifier
     * @param $timestamp
     * @return string
     */
    protected function formatDate($identifier, $timestamp = 0)
    {
        $timestamp = ($timestamp) ? $timestamp : time();
        $utc = ilCertificateConfig::get('time_format_utc');
        $format = ilCertificateConfig::get('str_format_date');
        // Check if a hook wants to modify the date format
        $format_custom = $this->pl->getHooks()->formatDate($this->certificate, $identifier);
        if ($format_custom) {
            $format = $format_custom;
        }

        $value = ($utc) ? gmdate($format, $timestamp) : date($format, $timestamp);

        return $value;
    }


    /**
     * See formatDate() method
     *
     * @param $identifier
     * @param int $timestamp
     * @return string
     */
    protected function formatDateTime($identifier, $timestamp = 0)
    {
        $timestamp = ($timestamp) ? $timestamp : time();
        $utc = ilCertificateConfig::get('time_format_utc');
        $format = ilCertificateConfig::get('str_format_datetime');
        // Check if a hook wants to modify the date format
        $format_custom = $this->pl->getHooks()->formatDate($this->certificate, $identifier);
        if ($format_custom) {
            $format = $format_custom;
        }

        $value = ($utc) ? gmdate($format, $timestamp) : date($format, $timestamp);

        return $value;
    }


    /**
     * Build a time string based on the spent seconds in the course
     *
     * @param $lp_data
     * @return string
     */
    protected function buildLpSpentTime($lp_data)
    {
        $seconds = (int) $lp_data['childs_spent_seconds'];
        $hours = (string) (floor($seconds / 3600));
        $mins = (string) (floor(($seconds - ($hours * 3600)) / 60));
        $secs = (string) (floor($seconds % 60));
        if (strlen($hours) == 1) $hours = '0' . $hours;
        if (strlen($mins) == 1) $mins = '0' . $mins;
        if (strlen($secs) == 1) $secs = '0' . $secs;
        $lp_spent_time = "$hours:$mins:$secs";

        return $lp_spent_time;
    }


    /**
     * Build average percentage of objects in course
     *
     * @param array $lp_data
     * @return float|null
     */
    protected function buildAvgPercentageOfCourseObjects(array $lp_data)
    {
        $count_objects = (int) $lp_data['cnt'];
        $avg = 0;
        $count_avg = 0;
        $return = null;
        if ($count_objects > 1) {
            // Course itself is in pos 0 so we count from pos 1
            for ($i = 0; $i < $count_objects; $i++) {
                // If there was no access to the object, don't count percentage
                if (is_null($lp_data['set'][$i]['first_access'])) {
                    continue;
                }
                // Don't count the course
                if ($lp_data['set'][$i]['type'] == 'crs') {
                    continue;
                }
                $avg += (int) $lp_data['set'][$i]['percentage'];
                $count_avg++;
            }
            $return = ($count_avg) ? $avg / ($count_avg) : null;
        }

        return $return;
    }


    /**
     * Return all Placeholders of user data
     *
     * @param ilObjUser $user
     * @return array
     */
    protected function parseUserPlaceholders(ilObjUser $user)
    {
        return array(
            'USER_LOGIN' => ($this->anonymized) ? 'johndoe' : $user->getLogin(),
            'USER_TITLE' => ($this->anonymized) ? 'Mister' : $user->getTitle(),
            'USER_FULLNAME' => ($this->anonymized) ? 'John Doe' : $user->getFullname(),
            'USER_FIRSTNAME' => ($this->anonymized) ? 'John' : $user->getFirstname(),
            'USER_LASTNAME' => ($this->anonymized) ? 'Doe' : $user->getLastname(),
            'USER_BIRTHDAY' => $user->getBirthday(),
            'USER_INSTITUTION' => $user->getInstitution(),
            'USER_DEPARTMENT' => $user->getDepartment(),
            'USER_STREET' => ($this->anonymized) ? 'Manhattan Street' : $user->getStreet(),
            'USER_CITY' => ($this->anonymized) ? 'New York' : $user->getCity(),
            'USER_ZIPCODE' => ($this->anonymized) ? 10026 : $user->getZipcode(),
            'USER_COUNTRY' => ($this->anonymized) ? 'USA' : $user->getCountry(),
        );
    }


    /**
     * Return all Placeholders of Learning Progress data
     *
     * @param ilObjCourse $course
     * @param ilObjUser $user
     * @return array
     */
    protected function parseLearningProgressPlaceholders(ilObjCourse $course, ilObjUser $user)
    {
        $passed_datetime = ilCourseParticipants::getDateTimeOfPassed($course->getId(), $user->getId());
        $lp_fields = array('first_access', 'last_access', 'percentage', 'status', 'read_count', 'childs_spent_seconds');
        $lp_data = ilTrQuery::getObjectsDataForUser($user->getId(), $course->getId(), $course->getRefId(), '', '', 0, 9999, null, $lp_fields);
        $lp_avg = $this->buildAvgPercentageOfCourseObjects($lp_data);
        $lp_crs = array();
        $max_last_access = 0;
        foreach ($lp_data['set'] as $v) {
            if ($v['type'] == 'crs') {
                $lp_crs = $v;
                $lp_crs['first_access'] = strtotime($v['first_access']); // First access is not stored as UNIX timestamp...
            }
            if ($v['last_access'] > $max_last_access) {
                $max_last_access = $v['last_access'];
            }
        }
        $lp_crs['last_access'] = $max_last_access;
        // calculates spent time different for scorm modules if enabled in config
        /** @var $cert_def srCertificateDefinition */
        $cert_definition = $this->certificate->getDefinition();
        if ($cert_definition->getScormTiming()) {
            $spent_seconds = 0;
            foreach (ilLPCollections::_getItems($course->getId()) as $item) {
                $spent_seconds += $this->getSpentSeconds(ilObject::_lookupObjectId($item), $user->getId());
            }
            $lp_crs['childs_spent_seconds'] = $spent_seconds;
        }
        $lp_spent_time = $this->buildLpSpentTime($lp_crs);

        return array(
            'DATE_COMPLETED' => $this->formatDate('DATE_COMPLETED', strtotime($passed_datetime)),
            'DATETIME_COMPLETED' => $this->formatDateTime('DATETIME_COMPLETED', strtotime($passed_datetime)),
            'LP_FIRST_ACCESS' => $this->formatDateTime('LP_FIRST_ACCESS', (int) $lp_crs['first_access']),
            'LP_LAST_ACCESS' => $this->formatDateTime('LP_LAST_ACCESS', (int) $lp_crs['last_access']),
            'LP_SPENT_TIME' => $lp_spent_time,
            'LP_SPENT_SECONDS' => $lp_crs['childs_spent_seconds'],
            'LP_READ_COUNT' => $lp_crs['read_count'],
            'LP_STATUS' => $lp_crs['status'],
            'LP_AVG_PERCENTAGE' => $lp_avg,
        );
    }


    /**
     * calculates spent seconds for an object, fetches data from cmi_node if object is a scorm2004 module
     *
     * @param $obj_id
     * @param $user_id
     * @return int
     */
    protected function getSpentSeconds($obj_id, $user_id)
    {
        global $ilDB;

        $spent_seconds = 0;

        if (ilObject::_lookupType($obj_id) == 'sahs') {
            $sql = $ilDB->query('SELECT cmi_node.total_time AS seconds
                                    FROM cmi_node
                                    INNER JOIN cp_node ON (cmi_node.cp_node_id = cp_node.cp_node_id)
                                    INNER JOIN object_reference ON (cp_node.slm_id = object_reference.obj_id)
                                    WHERE cmi_node.user_id = ' . $ilDB->quote($user_id, 'integer') .
                ' AND cp_node.slm_id = ' . $ilDB->quote($obj_id, 'integer'));
            while ($result = $ilDB->fetchAssoc($sql)) {
                $spent_seconds += $this->formatScormToSeconds($result['seconds']);
            }
        } else {
            $sql = $ilDB->query('SELECT read_event.spent_seconds AS seconds
                                    FROM read_event
                                    WHERE read_event.usr_id = ' . $ilDB->quote($user_id, 'integer') .
                ' AND read_event.obj_id = ' . $ilDB->quote($obj_id, 'integer'));
            while ($result = $ilDB->fetchAssoc($sql)) {
                $spent_seconds += $result['seconds'];
            }
        }

        return $spent_seconds;
    }


    /** TODO: move to some util class?
     * formats a time in scorm 2004 format (e.g. PT0H0M47S) into seconds
     *
     * @param $duration
     * @return int
     */
    public function formatScormToSeconds($duration)
    {

        $count = preg_match('/P(([0-9]+)Y)?(([0-9]+)M)?(([0-9]+)D)?T?(([0-9]+)H)?(([0-9]+)M)?(([0-9]+)(\.[0-9]+)?S)?/', $duration, $matches);

        if ($count) {
            $_years = (int) $matches[2];
            $_months = (int) $matches[4];
            $_days = (int) $matches[6];
            $_hours = (int) $matches[8];
            $_minutes = (int) $matches[10];
            $_seconds = (int) $matches[12];
        } else {
            if (strstr($duration, ':')) {
                list($_hours, $_minutes, $_seconds) = explode(':', $duration);
            } else {
                $_hours = 0;
                $_minutes = 0;
                $_seconds = 0;
            }
        }

        // I just ignore years, months and days as it is unlikely that a
        // course would take any longer than 1 hour
        return $_seconds + (($_minutes + (($_hours + (($_days + (($_months + $_years * 12) * 30)) * 24)) * 60)) * 60);
    }

}
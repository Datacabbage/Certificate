<?php

/**
 * Class srCertificatePlaceholdersParser
 *
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 */
class srCertificatePlaceholdersParser {

    /**
     * @var srCertificatePlaceholdersParser
     */
    static $instance;

    private function __construct() {}


    public static function getInstance()
    {
        if ( ! static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }


    /**
     * Parse the text by replacing placeholder values with the values in given array, e.g.
     *
     * Hello [[USER_FULLNAME]], you passed the course [[COURSE_TITLE]]
     *      *
     * @param array $placeholders
     * @param string $text
     * @return string Parsed text
     */
    public function parse($text, array $placeholders)
    {
        // match all non whitespace characters in [[ ]]
        preg_match_all('/\[\[(\S*)\]\]/', $text, $tpl_placeholders);
        $replacements = array();
        foreach ($tpl_placeholders[0] as $key) {
            $replacements[] = $placeholders[$key];
        }
        return str_replace($tpl_placeholders[0], $replacements, $text);
    }

}
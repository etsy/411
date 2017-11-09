<?php

namespace FOO;

/**
 * Class Enricher
 * Generates Alert objects which are then passed through the rest of the pipeline.
 * @package FOO
 */
class Enricher {
    public static $TYPES = ['IP_Enricher', 'MAC_Enricher', 'Stacktrace_Enricher', 'Link_Enricher', 'Null_Enricher'];
    public static $TYPE = '';

    /**
     * Creates a new Enricher of the appropriate type.
     * @param string $type The type of the Enricher.
     * @return Enricher The new Enricher.
     */
    public static function getEnricher($type) {
        $types = self::getTypes();
        return Util::exists($types, $type) ? $types[$type]:$types['null'];
    }

    /**
     * Return a mapping of all type slugs to their actual class name.
     * @return array A mapping of type slugs to class names.
     */
    public static function getTypes() {
        static $type_map = null;
        if(is_null($type_map)) {
            $type_map = [];
            foreach(static::$TYPES as $class) {
                $class = 'FOO\\' . $class;
                $type_map[$class::$TYPE] = $class;
            }
        }

        return $type_map;
    }

    /**
     * Executes the Enricher and returns structured data.
     * @param mixed $data The input data.
     * @return mixed Enriched data.
     */
    public static function process($data) { return null; }

    /**
     * Executes the Enricher and returns HTML output.
     * @param mixed $data The input data.
     * @return mixed Enriched data.
     */
    public static function processHTML($data) { return null; }
}

/**
 * Exception thrown when there is an error executing the Enricher.
 */
class EnricherException extends \Exception {}

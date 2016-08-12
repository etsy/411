<?php

namespace FOO;

/**
 * Class Exporter
 * Interface for exporting Alert data.
 * @package FOO
 */
abstract class Exporter {
    public static $TYPE = '';

    /**
     * Generates the report.
     * @param Report $report The Report object.
     * @param int $date The current date.
     * @return mixed Report data.
     */
    abstract public function generate($report, $date);

    /**
     * Retrieves the MIME type of the Report.
     * @return string Type.
     */
    abstract public function mimeType();
}

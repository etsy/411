<?php

namespace FOO;

/**
 * CSV Exporter class
 * Export Search results into a CSV.
 * @package FOO
 */
class CSV_Exporter extends Exporter {
    public static $TYPE = 'csv';

    public function generate($report, $date) {
        $fh = fopen('php://temp', 'w+');

        $errors = [];

        $this->TitlePage($fh, $report['name'], $report['description'], $report['range']);

        $searches = $report->getSearches();

        foreach ($searches as $search) {
            $errors = array_merge($errors, $this->SearchPage($fh, $report, $search, $date));
        }

        $sz = ftell($fh);
        rewind($fh);
        $data = fread($fh, $sz);
        fclose($fh);

        return [$data, $errors];
    }

    /**
     * Generate the title data for the CSV.
     * @param resource $fh The file handle.
     * @param string $title The title of this Report.
     * @param string $description The description of this Report.
     * @param int $range The range of this Report.
     */
    public function TitlePage($fh, $title, $description, $range) {
        fputcsv($fh, ['Title', $title]);
        fputcsv($fh, ['Description', $description]);
        fputcsv($fh, ['Date', strftime('%B %e, %G', $_SERVER['REQUEST_TIME'])]);
        fputcsv($fh, ['Range', $range]);
        fputcsv($fh, []);
    }

    /**
     * Generate the Search result pages of the CSV.
     * @param resource $fh The file handle.
     * @param Report $report The Report object.
     * @param Search $search The Search object.
     * @param int $date The current date.
     * @return string[] A list of errors.
     */
    public function SearchPage($fh, $report, $search, $date) {
        $searchjob = new Search_Job();
        $searchjob['target_date'] = $date;
        $search['range'] = $report['range'] * 60 * 24;
        list($alerts, $errors) = $searchjob->_run(false, $search);

        fputcsv($fh, [$search['name']]);
        fputcsv($fh, [$search['description']]);

        $keys = ['Date' => null];
        // Data
        foreach($alerts as $alert) {
            foreach($alert['content'] as $k=>$col) {
                $keys[$k] = null;
            }
        }
        fputcsv($fh, array_keys($keys));
        foreach($alerts as $alert) {
            $row = [gmdate(DATE_RSS, $alert['alert_date'])];
            foreach($keys as $k=>$v) {
                $row[] = Util::get($alert['content'], $k, '');
            }
            fputcsv($fh, $row);
        }

        fputcsv($fh, []);
        fputcsv($fh, []);

        return $errors;
    }

    public function mimeType() {
        return 'text/csv';
    }
}

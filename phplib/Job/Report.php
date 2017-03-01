<?php

namespace FOO;

/**
 * Class Report_Job
 * Represents a scheduled execution of a Report.
 * @package FOO
 */
class Report_Job extends Job {
    public static $TYPE = 'report';

    /**
     * Process a single Report and generate emails as necessary.
     * @param bool $commit Whether to commit changes.
     * @return array PDF data, array of errors and whether failures are ignorable.
     */
    public function run($commit=true) {
        $report = ReportFinder::getById($this->obj['target_id']);
        if(is_null($report)) {
            throw new JobCancelException(sprintf("Report doesn't exist: %d", $this->obj['target_id']));
        }

        $exp = new PDF_Exporter();
        list($pdf_data, $errors) = $exp->generate($report, $this->obj['target_date']);

        $this->setCompletion(75);

        // Email logic.
        if($commit) {
            $to = Assignee::getEmails($report['assignee_type'], $report['assignee']);

            // Error emails.
            if(count($errors)) {
                Notification::sendReportErrorEmail($to, $report, $errors, $this->getDebugData());
                Logger::err('Report error', ['id' => $report['id'], 'errors' => $errors], self::LOG_NAMESPACE);
            } else {
                Notification::sendReportEmail($to, $report, $pdf_data, $this->getDebugData());
            }
        }

        return [$pdf_data, $errors, false];
    }
}

<?php

namespace FOO;

/**
 * PDF Exporter class
 * Export Search results into a PDF.
 */
class PDF_Exporter extends Exporter {
    public static $TYPE = 'pdf';
    /** Width of the page. */
    const WIDTH = 275;

    public function generate($report, $date) {
        $pdf = new \FPDF();
        $pdf->setTitle($report['name']);
        $pdf->setAuthor('411');
        $pdf->SetFont('helvetica');

        $errors = [];

        $this->TitlePage($pdf, $report['name'], $report['description'], $report['range']);

        $searches = $report->getSearches();

        foreach ($searches as $search) {
            $errors = array_merge($errors, $this->SearchPage($pdf, $report, $search, $date));
        }

        $pdf->Close();

        return [$pdf->Output('', 'S'), $errors];
    }

    /**
     * Generate the title page of the PDF.
     * @param \FPDF $pdf The pdf object.
     * @param string $title The title of the Report.
     * @param string $description The description of the Report.
     * @param int $range The range of this Report.
     */
    public function TitlePage($pdf, $title, $description, $range) {
        $pdf->SetFontSize(50);
        $pdf->AddPage('L');
        $pdf->SetY(110);
        $pdf->Cell(0, 0, $title, 0, 1, 'C');
        $pdf->SetY(130);
        $pdf->SetFontSize(20);
        $pdf->Cell(0, 0, $description, 0, 1, 'C');
        $pdf->SetY(160);
        $pdf->SetFontSize(15);
        $pdf->Cell(0, 0, sprintf("Generated %s", strftime('%B %e, %G', $_SERVER['REQUEST_TIME'])), 0, 1, 'C');
        $pdf->SetY(170);
        $pdf->Cell(0, 0, sprintf("%d days", $range), 0, 1, 'C');
    }

    /**
     * Generate the Search result pages of the PDF.
     * @param \FPDF $pdf The pdf object.
     * @param Report $report The Report object.
     * @param Search $search The Search object.
     * @param int $date The current date.
     * @return string[] A list of errors.
     */
    public function SearchPage($pdf, $report, $search, $date) {
        $searchjob = new Search_Job();
        $searchjob['target_date'] = $date;
        $search['range'] = $report['range'] * 60 * 24;
        list($alerts, $errors, $ignorable) = $searchjob->_run(false, $search);

        $pdf->AddPage('L');
        $pdf->SetFontSize(20);
        $pdf->Write(10, $search['name']);
        $pdf->Ln();
        $pdf->SetFontSize(10);
        $pdf->Write(6, $search['description']);
        $pdf->Ln();
        $pdf->Ln();

        // Data
        foreach ($alerts as $alert) {
            // Compute width of header
            $colname_size = $pdf->GetStringWidth('XXXX');
            foreach (array_keys($alert['content']) as $col) {
                $colname_size = max($pdf->GetStringWidth($col) + 2, $colname_size);
            }
            $coldata_size = self::WIDTH - $colname_size;

            // Render!
            $this->headerCell($pdf, $colname_size, 'Date');
            $this->bodyCell($pdf, $coldata_size, gmdate(DATE_RSS, $alert['alert_date']));
            foreach ($alert['content'] as $k=>$col) {
                $this->headerCell($pdf, $colname_size, $k);
                $this->bodyCell($pdf, $coldata_size, $col);
            }
            $pdf->Ln();
            $pdf->Ln();
            $pdf->Ln();
        }
        return $errors;
    }

    /**
     * Generate a header cell for a table with appropriate formatting.
     * @param \FPDF $pdf The pdf object.
     * @param int $sz The size.
     * @param string $str The name of the cell.
     */
    private function headerCell($pdf, $sz, $str) {
        $pdf->SetFontSize(9);
        $pdf->SetFillColor(0xee);
        $pdf->SetFont('', 'B');

        $pdf->Cell($sz, 7, $str, 1, 0, '', true);
    }

    /**
     * Generate a body cell for a table with appropriate formatting.
     * @param \FPDF $pdf The pdf object.
     * @param int $sz The size.
     * @param string $str The value of the cell.
     */
    private function bodyCell($pdf, $sz, $str) {
        $pdf->SetFillColor(0xff);
        $pdf->SetFont('');

        $pdf->MultiCell($sz, 6, $str, 1, 'R', true);
    }

    public function mimeType() {
        return 'application/pdf';
    }
}

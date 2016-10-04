<?php

namespace FOO;

/**
 * Class Notification
 * Generates and sends various email notifications.
 * @package FOO
 */
class Notification {

    /**
     * Send an email about new Alerts.
     * @param string[]|string $to The destination email.
     * @param Search $search The Search object.
     * @param Alert[] $alerts The list of Alerts.
     * @param boolean $content_only Whether to hide metadata.
     * @param array $debug_data Watermarking data for debugging purposes.
     * @throws \Exception
     */
    public static function sendAlertEmail($to, $search, $alerts, $content_only, $debug_data=[]) {
        $alertkeys = [];
        foreach($alerts as $alert) {
            foreach($alert['content'] as $k=>$v) {
                $alertkeys[$k] = null;
            }
        }
        $alertkeys = array_keys($alertkeys);

        self::mail(
            $to, self::getFrom(),
            $search['name'],
            self::render('alerts', [
                'search' => $search,
                'alerts' => $alerts,
                'alertkeys' => $alertkeys,
                'content_only' => $content_only,
            ], $debug_data)
        );
    }

    /**
     * Send an email about new actions on Alerts.
     * @param string[]|string $to The destination email.
     * @param AlertLog $action The action taken.
     * @param array $searches A mapping of search ids to Searches.
     * @param Alert[] $alerts The list of Alerts.
     * @param boolean $content_only Whether to hide metadata.
     * @param array $debug_data Watermarking data for debugging purposes.
     * @throws \Exception
     */
    public static function sendAlertActionEmail($to, $action, $searches, $alerts, $content_only=false, $debug_data=[]) {
        self::mail(
            $to, self::getFrom(),
            $action->getDescription(),
            self::render('action', [
                'action' => $action,
                'alert_groups' => self::groupAlerts($searches, $alerts),
                'content_only' => $content_only,
            ], $debug_data)
        );
    }

    /**
     * Send an email rollup about new Alerts and actions taken.
     * @param string[]|string $to The destination email.
     * @param Alert[] $new_alerts The list of new Alerts.
     * @param AlertLog[] $actions The list of actions.
     * @param Search[] $searches The list of Searches.
     * @param Alert[] $action_alerts The list of Alerts that have been actioned on.
     * @param array $active_alerts The counts of active Alerts.
     * @param boolean $content_only Whether to hide metadata.
     * @param array $debug_data Watermarking data for debugging purposes.
     * @throws \Exception
     */
    public static function sendRollupEmail($to, $new_alerts, $actions, $searches, $action_alerts, $active_alerts, $content_only=false, $debug_data=[]) {
        $search_map = [];
        foreach($searches as $search) {
            $search_map[$search['id']] = $search;
        }

        self::mail(
            $to, self::getFrom(),
            sprintf(
                'Rollup [%d Alert%s] [%d Action%s]',
                count($new_alerts),
                count($new_alerts) != 1 ? 's':'',
                count($actions),
                count($actions) != 1 ? 's':''
            ),
            self::render('rollup', [
                'new_count' => $active_alerts[0],
                'inprog_count' => $active_alerts[1],
                'new_alert_groups' => self::groupAlerts($search_map, $new_alerts),
                'actions' => $actions,
                'action_alert_groups' => self::groupAlerts($search_map, $action_alerts),
                'content_only' => $content_only,
            ], $debug_data)
        );
    }

    /**
     * Send an email about a failing Search type.
     * @param string[]|string $to The destination email.
     * @param string $type The Search type.
     * @param array $debug_data Watermarking data for debugging purposes.
     * @throws \Exception
     */
    public static function sendSearchTypeErrorEmail($to, $type, $debug_data=[]) {
        self::mail(
            $to, self::getFrom(true),
            sprintf('[Failure] "%s" Search Type', $type),
            self::render('searchtypeerror', [
                'type' => $type
            ], $debug_data)
        );
    }

    /**
     * Send an email about a recovered Search type.
     * @param string[]|string $to The destination email.
     * @param string $type The Search type.
     * @param array $debug_data Watermarking data for debugging purposes.
     * @throws \Exception
     */
    public static function sendSearchTypeRecoveryEmail($to, $type, $debug_data=[]) {
        self::mail(
            $to, self::getFrom(true),
            sprintf('[Recovery] "%s" Search Type', $type),
            self::render('searchtyperecovery', [
                'type' => $type
            ], $debug_data)
        );
    }

    /**
     * Send an email about a failed Search.
     * @param string[]|string $to The destination email.
     * @param Search $search The Search object.
     * @param string[] $errors The list of errors.
     * @param array $debug_data Watermarking data for debugging purposes.
     * @throws \Exception
     */
    public static function sendSearchErrorEmail($to, $search, $errors, $debug_data=[]) {
        self::mail(
            $to, self::getFrom(true),
            sprintf('[Failure] "%s" Search', $search['name']),
            self::render('searcherror', [
                'search' => $search,
                'errors' => $errors
            ], $debug_data)
        );
    }

    /**
     * Send an email about a recovered Search.
     * @param string[]|string $to The destination email.
     * @param Search $search The Search object.
     * @param array $debug_data Watermarking data for debugging purposes.
     * @throws \Exception
     */
    public static function sendSearchRecoveryEmail($to, $search, $debug_data=[]) {
        self::mail(
            $to, self::getFrom(true),
            sprintf('[Recovery] "%s" Search', $search['name']),
            self::render('searchrecovery', [
                'search' => $search
            ], $debug_data)
        );
    }

    /**
     * Send an email with a generated Report.
     * @param string[]|string $to The destination email.
     * @param Report $report The Report object.
     * @param string $pdf_data The content of the Report.
     * @param array $debug_data Watermarking data for debugging purposes.
     * @throws \Exception
     */
    public static function sendReportEmail($to, $report, $pdf_data, $debug_data=[]) {
        self::mail(
            $to, self::getFrom(),
            sprintf('"%s" Report', $report['name']),
            self::render('report', [
                'report' => $report
            ], $debug_data),
            $pdf_data
        );
    }

    /**
     * Send an email about a failed Report.
     * @param string[]|string $to The destination email.
     * @param Report $report The Report object.
     * @param string[] $errors The list of errors.
     * @param array $debug_data Watermarking data for debugging purposes.
     * @throws \Exception
     */
    public static function sendReportErrorEmail($to, $report, $errors, $debug_data=[]) {
        self::mail(
            $to, self::getFrom(true),
            sprintf('[Failure] "%s" Report', $report['name']),
            self::render('reporterror', [
                'report' => $report,
                'errors' => $errors
            ], $debug_data)
        );
    }

    /**
     * Send an email with a weekly summary.
     * @param string[]|string $to The destination email.
     * @param DateTime $start_date The starting date for this week.
     * @param int[] $stats New alerts, closed alerts and open alerts.
     * @param array $leaders Users who've closed the most Alerts this week w/ a count.
     * @param array $noisy_searches Searches that generate the most Alerts w/ a count.
     * @param array $quiet_searches Searches that generate the least Alerts w/ a count.
     * @param array $debug_data Watermarking data for debugging purposes.
     * @param
     */
    public static function sendSummaryEmail($to, $start_date, $stats, $leaders, $noisy_searches, $quiet_searches, $debug_data=[]) {
        $end_date = clone $start_date;
        $end_date->modify('+6 days');
        self::mail(
            $to, self::getFrom(),
            sprintf('Weekly summary: %s to %s', $start_date->format('Y-m-d'), $end_date->format('Y-m-d')),
            self::render('summary', [
                'new_count' => $stats[0],
                'close_count' => $stats[1],
                'open_count' => $stats[2],
                'leaders' => $leaders,
                'noisy_searches' => $noisy_searches,
                'quiet_searches' => $quiet_searches,
            ], $debug_data)
        );
    }

    /**
     * Group Alerts underneath a Search.
     * @param array $search_map The Search mapping.
     * @param Alert[] $alerts The list of Alerts.
     * @return array A keyed mapping of Alerts.
     */
    private static function groupAlerts($search_map, $alerts) {
        $groups = [];
        foreach($alerts as $alert) {
            if(!array_key_exists($alert['search_id'], $groups)) {
                $groups[$alert['search_id']] = [[], []];
            }
            $groups[$alert['search_id']][0][] = $alert;
            foreach($alert['content'] as $k=>$v) {
                $groups[$alert['search_id']][1][$k] = null;
            }
        }

        $ret = [];
        foreach($groups as $key=>$data) {
            $ret[] = [Util::get($search_map, $key), $data[0], array_keys($data[1])];
        }
        return $ret;
    }

    /**
     * Get the source email address.
     * @param bool $error Whether this email is for an error.
     * @return string The email address.
     */
    public static function getFrom($error=false) {
        $cfg = new DBConfig();
        return $cfg[$error ? 'from_error_email':'from_email'] ?: sprintf('411@%s', Util::getHost());
    }

    /**
     * Renders a template.
     * @param string $tpl The name of the template.
     * @param array $vars Variables to inject into the template.
     * @param array $debug_data Watermarking data for debugging purposes.
     * @return string A templated string.
     * @throws \Exception
     */
    public static function render($tpl, $vars, $debug_data=[]) {
        // Embedded CSS. This is unfortunately necessary as most (all?) mail clients only allow inline CSS.
        $font = "font-family: 'Myriad Pro','Helvetica Neue',Helvetica,Tahoma,Arial,sans-serif;";
        $base_url = sprintf('https://%s', Util::getHost());
        $large_style = "font-size: 1.6em;";
        $panel_style = "border-radius: 3px; background-color: #f5f5f5; color: #333; border: 1px solid #ddd;";
        $panel_content_style = "padding: 0px 15px; margin: 5px 0px; text-size: 1.5em;";
        $sub_style = "float: right; color: #777;";
        $well_style = "padding: 9px; border-radius: 3px; background: #f5f5f5; border: 1px solid #e3e3e3; box-shadow: inset 0 1px 1px rgba(0,0,0,.05);";
        $table_container_style = ""; // "overflow-x: auto";
        $table_style = "border-collapse: collapse; min-width: 100%;";
        $link_style = "color: #000;";
        $h_cell_style = "background-color: #eee; color: #333; border-top: 1px solid #ddd; padding: 5px;";
        $cell_style = "background-color: #f9f9f9; color: #333; border-top: 1px solid #ddd; padding: 5px; text-align: right;";
        $button_style = "padding: 1px 5px; border-radius: 3px; border: solid 1px #269abc; background: #5bc0de; color: #fff; text-decoration: none;";
        $action_button_style = "padding: 10px 16px; border-radius: 6px; border: solid 1px #4cae4c; background: #5cb85c; color: #fff; text-decoration: none; font-size: 18px; cursor: hand;";
        $info_alert_style = "color: #31708f; background-color: #d9edf7; border-color: #bce8f1; border-radius: 4px; padding: 15px;";
        $error_alert_style = "color: #a94442; background-color: #f2dede; border-color: #ebccd1; border-radius: 4px; padding: 15px;";

        // Extract variables into the current namespace ONLY if it doesn't already exist.
        extract($vars, EXTR_SKIP);

        // Render the template. If there's an error, dump the output buffer.
        ob_start();
        try {
            require(sprintf('%s/templates/%s.php', BASE_DIR, $tpl));
        } catch(\Exception $e) {
            ob_end_clean();
            throw $e;
        }
        print sprintf("\n<!-- DEBUG\n%s\n-->", str_replace('&', "\n", http_build_query($debug_data)));
        return ob_get_clean();
    }

    /**
     * Render and send an email.
     * @param string[]|string $to Email addresses to send to.
     * @param string $from Email addresses to send from.
     * @param string $title The subject line.
     * @param string $message The message.
     * @param string $file The contents of a file to send.
     */
    public static function mail($to, $from, $title, $message, $file=null) {
        list($to, $from, $title, $message, $file) = Hook::call('mail', [$to, $from, $title, $message, $file]);

        $bnd = uniqid();
        $headers = "From: $from\r\n";
        $headers.= "MIME-Version: 1.0\r\n";
        $to = (array) $to;
        if (count($to) > 1) {
            $headers .= sprintf("CC: %s\r\n", implode(', ', array_slice($to, 1)));
        }
        $headers.= "Content-Type: multipart/mixed; boundary=$bnd\r\n\r\n";

        $sep = "--$bnd\r\n";
        $end = "--$bnd--";

        $body = $sep;
        $body.= "Content-Type: text/html; charset=utf-8\r\n";
        $body.= "Content-Transfer-Encoding: 8bit\r\n";
        $body.= "\r\n$message\r\n\r\n";

        if(!is_null($file)) {
            $body.= $sep;
            $body.= "Content-Type: application/octet-stream; name=\"report.pdf\"\r\n";
            $body.= "Content-Transfer-Encoding: base64\r\n";
            $body.= "Content-Disposition: attachment\r\n";
            $body.= "\r\n" . chunk_split(base64_encode($file)) . "\r\n\r\n";
        }
        $body.= $end;

        mail(
            $to[0],
            sprintf('[%s] %s', Util::getSiteName(), $title),
            $body,
            $headers
        );
    }
}

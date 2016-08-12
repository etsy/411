<?php

namespace FOO;

/**
 * Jira Target Class
 * Creates a ticket for an Alert.
 */
class Jira_Target extends Target {
    public static $TYPE = 'jira';

    public static $DESC = 'Create a ticket for this alert and assign to <assignee>.';

    protected static function generateDataSchema() {
        return [
            'project' => [static::T_STR, null, ''],
            'type' => [static::T_NUM, null, 0],
            'assignee' => [static::T_STR, null, ''],
        ];
    }

    /**
     * Create ticket.
     *
     * @param Alert An Alert object
     * @param int $date The current date.
     */
    public function process(Alert $alert, $date) {
        $site = SiteFinder::getCurrent();
        $desc = [];
        $desc[] = sprintf('Date: %s', gmdate(DATE_RSS, $alert['alert_date']));

        // Don't show the link if this Alert isn't persisted.
        if(!$alert->isNew()) {
            $desc[] = sprintf('[Link to Alert|https://%s/alert/%d]', $site['host'], $alert['alert_id']);
        }
        $desc[] = '';
        $desc[] = '||Key||Value||';
        foreach($alert['content'] as $key=>$value) {
            $desc[] = sprintf('|%s|%s|', $key, $value);
        }
        $search = SearchFinder::getById($alert['search_id']);

        $ret = self::createIssue(
            [
                'project' => ['key' => $this->obj['data']['project']],
                'issuetype' => ['id' => $this->obj['data']['type']],
                'summary' => sprintf('[%s] %s', $site['name'], $search['name']),
                'description' => implode("\n", $desc),
                'assignee' => ['name' => $this->obj['data']['assignee']]
            ]
        );
        if(!$ret) {
            throw new TargetException(sprintf('Failed to send Alert:%d to Jira', $alert['alert_id']));
        }
    }

    /**
     * Create a JIRA ticket.
     * @param mixed[] $issue_data Issue data.
     * @return string|null The issue key or null.
     */
    public static function createIssue($issue_data) {
        $jiracfg = Config::get('jira');
        if(is_null($jiracfg['host'])) {
            return null;
        }

        $curl = new \Curl\Curl;
        $curl->setHeader('X-Atlassian-Token', 'nocheck');
        $curl->setHeader('Content-type', 'application/json');
        if(!is_null($jiracfg['user']) && !is_null($jiracfg['pass'])) {
            $curl->setBasicAuthentication($jiracfg['user'], $jiracfg['pass']);
        }
        $ret = $curl->post(
            sprintf('%s/rest/api/2/issue', $jiracfg['host']),
            json_encode(['fields' => $issue_data])
        );

        if($curl->httpStatusCode < 200 || $curl->httpStatusCode >= 300) {
            return null;
        }

        return $ret->key;
    }
}

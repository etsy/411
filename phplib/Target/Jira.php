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
            'watchers' => [static::T_ARR, static::T_STR, []],
        ];
    }

    public function isAccessible() {
        $jiracfg = Config::get('jira');
        return !is_null($jiracfg['host']);
    }

    /**
     * Create ticket.
     *
     * @param Alert An Alert object
     * @param int $date The current date.
     */
    public function process(Alert $alert, $date) {
        $site = SiteFinder::getCurrent();
        $search = SearchFinder::getById($alert['search_id']);
        $issue_key = null;

        $title = sprintf('[%s] %s', $site['name'], $search['name']);
        $desc = [];
        $desc[] = sprintf('Date: %s', gmdate(DATE_RSS, $alert['alert_date']));

        // Don't show the link if this Alert isn't persisted.
        if(!$alert->isNew()) {
            $desc[] = sprintf('[Link to Alert|%s]', $site->urlFor(
                sprintf('/alert/%d', $alert['alert_id'])
            ));
            $source = $search->getLink($alert);
            if(!is_null($source)) {
                $desc[] = sprintf('[Source|%s]', $source);
            }
        }
        $desc[] = '';
        $desc[] = '*Alert Data*';
        $desc[] = '||Key||Value||';
        foreach($alert['content'] as $key=>$value) {
            $desc[] = sprintf('|%s|%s|', $key, $value);
        }

        $issue_data = [
            'project' => ['key' => $this->obj['data']['project']],
            'issuetype' => ['id' => $this->obj['data']['type']],
            'summary' => $title,
            'description' => implode("\n", $desc),
            'assignee' => ['name' => $this->obj['data']['assignee']]
        ];
        list($issue_data) = Hook::call('target.jira.send', [$issue_data]);

        $issue_key = self::createIssue($issue_data);
        if(!is_null($issue_key)) {
            self::addWatchers($issue_key, $this->obj['data']['watchers']);
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
            throw new TargetException('Jira not configured');
        }

        $curl = new Curl;
        $curl->setHeader('X-Atlassian-Token', 'nocheck');
        $curl->setHeader('Content-type', 'application/json');
        if(!is_null($jiracfg['user']) && !is_null($jiracfg['pass'])) {
            $curl->setBasicAuthentication($jiracfg['user'], $jiracfg['pass']);
        }
        $raw_data = $curl->post(
            sprintf('%s/rest/api/2/issue', $jiracfg['host']),
            json_encode(['fields' => $issue_data])
        );

        if($curl->httpStatusCode < 200 || $curl->httpStatusCode >= 300) {
            throw new TargetException(sprintf('Remote server returned %d: %s: %s', $curl->httpStatusCode, $curl->httpErrorMessage, $raw_data));
        }

        return $raw_data['key'];
    }

    /**
     * Add watchers to a JIRA ticket.
     * @param string $issue_key Issue key.
     * @param string[] $watchers An array of watchers.
     */
    public static function addWatchers($issue_key, $watchers) {
        $jiracfg = Config::get('jira');
        if(is_null($jiracfg['host'])) {
            throw new TargetException('Jira not configured');
        }

        $curl = new Curl;
        $curl->setHeader('X-Atlassian-Token', 'nocheck');
        $curl->setHeader('Content-type', 'application/json');
        if(!is_null($jiracfg['user']) && !is_null($jiracfg['pass'])) {
            $curl->setBasicAuthentication($jiracfg['user'], $jiracfg['pass']);
        }

        foreach($watchers as $watcher) {
            $raw_data = $curl->post(
                sprintf('%s/rest/api/2/issue/%s/watchers', $jiracfg['host'], $issue_key),
                json_encode($watcher)
            );
        }
    }
}

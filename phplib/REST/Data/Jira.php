<?php

namespace FOO;

/**
 * Class Jira_Data_REST
 * @package FOO
 */
class Jira_Data_REST extends REST {
    public function GET(array $get) {
        $jira_data = [
            'Issues' => [],
            'Users' => [],
        ];

        $issue_raw_data = self::getData('issue/createmeta');
        $user_raw_data = self::getData('user/search?username=%&maxResults=100000');

        // Format the metadata for the frontend.
        if(!is_null($issue_raw_data)) {
            foreach($issue_raw_data['projects'] as $project) {
                $issuetypes = [];
                foreach($project['issuetypes'] as $issuetype) {
                    $issuetypes[$issuetype['id']] = [
                        'name' => $issuetype['name']
                    ];
                }
                $jira_data['Issues'][$project['key']] = [
                    'name' => $project['name'],
                    'issuetypes' => $issuetypes,
                ];
            }
        }

        // Format the metadata for the frontend.
        if(!is_null($user_raw_data)) {
            foreach($user_raw_data as $user) {
                $jira_data['Users'][$user['name']] = $user['displayName'];
            }
        }

        return $jira_data;
    }

    private static function getData($endpoint) {
        $jiracfg = Config::get('jira');
        if(is_null($jiracfg['host'])) {
            return null;
        }

        $curl = new Curl;
        if(!is_null($jiracfg['user']) && !is_null($jiracfg['pass'])) {
            $curl->setBasicAuthentication($jiracfg['user'], $jiracfg['pass']);
        }
        $raw_data = $curl->get(sprintf('%s/rest/api/2/%s', $jiracfg['host'], $endpoint));

        if($curl->httpStatusCode < 200 || $curl->httpStatusCode >= 300) {
            return null;
        }

        return $raw_data;
    }
}

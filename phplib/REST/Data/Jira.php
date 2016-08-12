<?php

namespace FOO;

/**
 * Class Jira_Data_REST
 * @package FOO
 */
class Jira_Data_REST extends REST {
    public function GET(array $get) {
        $jira_data = [];

        $raw_data = self::getJiraIssueMeta();

        // Format the metadata for the frontend.
        if(!is_null($raw_data)) {
            foreach($raw_data->projects as $project) {
                $issuetypes = [];
                foreach($project->issuetypes as $issuetype) {
                    $issuetypes[$issuetype->id] = [
                        'name' => $issuetype->name
                    ];
                }
                $jira_data[$project->key] = [
                    'name' => $project->name,
                    'issuetypes' => $issuetypes,
                ];
            }
        }

        return $jira_data;
    }

    private static function getJiraIssueMeta() {
        $jiracfg = Config::get('jira');
        if(is_null($jiracfg['host'])) {
            return null;
        }

        $curl = new \Curl\Curl;
        if(!is_null($jiracfg['user']) && !is_null($jiracfg['pass'])) {
            $curl->setBasicAuthentication($jiracfg['user'], $jiracfg['pass']);
        }
        $ret = $curl->get(sprintf('https://%s/rest/api/2/issue/createmeta', $jiracfg['host']));

        if($curl->httpStatusCode < 200 || $curl->httpStatusCode >= 300) {
            return null;
        }

        return $ret;
    }
}

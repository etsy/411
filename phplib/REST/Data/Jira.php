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

        $issue_raw_data = Jira_Target::getCreateMeta();
        $user_raw_data = Jira_Target::getUsers();

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
}

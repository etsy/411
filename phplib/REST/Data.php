<?php

namespace FOO;

/**
 * Class Data_Controller
 * @package FOO
 */
class Data_REST extends REST {
    public function checkAuthorization() {}

    /**
     * Outputs all runtime data necessary to bootstrap the frontend.
     */
    public function GET(array $get) {
        $users = $this->generateUsers();

        $search_types = $this->generateTypeNames(Search::getTypes());
        $target_types = $this->generateTypeNames(Target::getTypes());
        $filter_types = $this->generateTypeNames(Filter::getTypes());

        $target_datas = $this->generateTypeData(Target::getTypes());
        $filter_datas = $this->generateTypeData(Filter::getTypes());

        $target_descs = $this->generateTypeDescs(Target::getTypes());
        $filter_descs = $this->generateTypeDescs(Filter::getTypes());

        $ret = [
            'AppName' => Util::getSiteName(),
            'Host' => Util::getHost(),
            'Alert' => [
                'States' => $this->generateEnumData(Alert::$STATES),
                'Resolutions' => $this->generateEnumData(Alert::$RESOLUTIONS),
                'Defaults' => $this->generateDefaultData(Alert::getSchema()),
            ],
            'AlertLog' => [
                'Defaults' => $this->generateDefaultData(AlertLog::getSchema()),
            ],
            'Search' => [
                'Categories' => $this->generateEnumData(Search::$CATEGORIES),
                'Types' => $search_types,
                'Priorities' => $this->generateEnumData(Search::$PRIORITIES),
                'NotifTypes' => $this->generateEnumData(Search::$NOTIF_TYPES),
                'NotifFormats' => $this->generateEnumData(Search::$NOTIF_FORMATS),
                'Defaults' => $this->generateDefaultData(Search::getSchema()),
                'TimeBased' => $this->generateSearchTimeBased(),
                'Sources' => $this->generateSearchSources(),
            ],
            'SearchLog' => [
                'Defaults' => $this->generateDefaultData(SearchLog::getSchema()),
            ],
            'Assignee' => [
                'Types' => $this->generateEnumData(Assignee::$TYPES),
            ],
            'Group' => [
                'Types' => $this->generateEnumData(Group::$TYPES),
                'Defaults' => $this->generateDefaultData(Group::getSchema()),
            ],
            'GroupTarget' => [
                'Defaults' => $this->generateDefaultData(GroupTarget::getSchema()),
            ],
            'List' => [
                'Types' => $this->generateEnumData(SList::$TYPES),
                'Defaults' => $this->generateDefaultData(SList::getSchema()),
            ],
            'Target' => [
                'Types' => $target_types,
                'Data' => $target_datas,
                'Descriptions' => $target_descs,
                'Defaults' => $this->generateDefaultData(Target::getSchema()),
            ],
            'Filter' => [
                'Types' => $filter_types,
                'Data' => $filter_datas,
                'Descriptions' => $filter_descs,
                'Defaults' => $this->generateDefaultData(Filter::getSchema()),
            ],
            'Job' => [
                'States' => $this->generateEnumData(Job::$STATES),
            ],
            'User' => [
                'Me' => Auth::getUserId(),
                'Models' => $users,
                'Defaults' => $this->generateDefaultData(User::getSchema()),
            ],
            'Nonce' => Nonce::get(),
            'Timezone' => Util::getDefaultTimezone(),
        ];
        list($ret) = Hook::call('rest.data', [$ret]);

        return $ret;
    }

    /**
     * Generate a list of users.
     * @return array Users list.
     */
    private function generateUsers() {
        $users = [];
        if(Auth::isAuthenticated()) {
            $c = new Users_REST;
            $users = $c->GET([])['data'];
        }

        return $users;
    }

    /**
     * Generate type names for enums.
     * @param string[] $types The list of enum values.
     * @return object A mapping of values to names.
     */
    private function generateEnumData($types) {
        return (object) (Auth::isAuthenticated() ? $types:[]);
    }

    /**
     * Generate type names for classes.
     * @param string[] $types The list of type slugs.
     * @return array A mapping of type slugs to names.
     */
    private function generateTypeNames($types) {
        $type_names = [];
        if(Auth::isAuthenticated()) {
            foreach($types as $type) {
                $name = explode('_', $type)[0];
                $name = explode('\\', $name)[1];
                $type_names[$type::$TYPE] = $name;
            }
        }

        return $type_names;
    }

    /**
     * Generate runtime data for classes.
     * @param string[] $types The list of types.
     * @return array A mapping of types to runtime data.
     */
    private function generateTypeData($types) {
        $type_names = [];
        if(Auth::isAuthenticated()) {
            foreach($types as $type) {
                $data = [];
                $dataschema = $type::getDataSchema();

                foreach($dataschema as $k=>$v) {
                    $value = $v;
                    if (is_array($v) && Util::exists($v, "__type__")) {
                        $value = [];
                    }
                    $data[$k] = $value;
                }
                $type_names[$type::$TYPE] = $data;
            }
        }

        return $type_names;
    }

    /**
     * Generate descriptions for classes.
     * @param string[] $types The list of types.
     * @return array A mapping of type names to descriptions.
     */
    private function generateTypeDescs($types) {
        $type_names = [];
        if(Auth::isAuthenticated()) {
            foreach($types as $type) {
                $type_names[$type::$TYPE] = $type::$DESC;
            }
        }

        return $type_names;
    }

    /**
     * Generate descriptions for classes.
     * @param mixed[] $schema Schema information.
     * @return array Default values for each field.
     */
    private function generateDefaultData($schema) {
        $defaults = [];
        foreach($schema as $key=>$data) {
            $defaults[$key] = $data[2];
        }

        return $defaults;
    }

    /**
     * Generate sources lists for Searches.
     * @return array[] An array of sources.
     */
    private function generateSearchSources() {
        $sources = [];
        if(Auth::isAuthenticated()) {
            foreach(Search::getTypes() as $type) {
                $sources[$type::$TYPE] = $type::getSources();
            }
        }

        return $sources;
    }

    /**
     * Generate data on whether Searches are time-based.
     * @return array[] An array of 
     */
    private function generateSearchTimeBased() {
        $time_based = [];
        if(Auth::isAuthenticated()) {
            foreach(Search::getTypes() as $type) {
                if($type::$SOURCES) {

                } else {
                    $time_based[$type::$TYPE] = (new $type)->isTimeBased();
                }
            }
        }

        return $time_based;
    }
}

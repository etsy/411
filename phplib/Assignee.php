<?php

namespace FOO;

/**
 * Class Assignee
 * Contains functions to manipulate Assignees. An Assignee is a User or a Group of Users.
 * @package FOO
 */
class Assignee {
    // Types.
    /** User assignee */
    const T_USER = 0;
    /** Group assignee */
    const T_GROUP = 1;
    /** @var string[] Mapping of types to a user-friendly string. */
    public static $TYPES = [
        self::T_USER => 'User',
        self::T_GROUP=> 'Group'
    ];

    /**
     * Extract an array of emails from an Assignee. Returns the default email if there is no Assignee or the Assignee
     * has no emails.
     * @param int $type The type of Assignee.
     * @param int $entry The ID of the entry.
     * @param bool $update Whether to update the Model.
     * @param bool $all Whether to get all emails in the Model.
     * @return string[] An array of emails.
     */
    public static function getEmails($type, $entry, $update=true, $all=false) {
        $emails = [];
        switch($type) {
            case self::T_USER:
                $user = UserFinder::getById($entry);
                if(!is_null($user)) {
                    $emails[] = $user['email'];
                }
                break;
            case self::T_GROUP:
                $group = GroupFinder::getById($entry);
                if(!is_null($group)) {
                    $group_emails = $group->getEmails($update, $all);
                    // Need to update the group state.
                    if($update) {
                        $group->store();
                    }
                    $emails = array_merge($emails, $group_emails);
                }
                break;
        }
        $cfg = new DBConfig();
        if(!count($emails)) {
            $emails[] = $cfg['default_email'];
        }

        list($emails) = Hook::call('assignee.emails', [$emails]);

        return $emails;
    }

    /**
     * Get the name of the Assignee (User or Group).
     * @param int $type The type of Assignee.
     * @param int $entry The ID of the entry.
     * @return string The name.
     */
    public static function getName($type, $entry) {
        $ret = 'System';
        switch($type) {
            case self::T_USER:
                $user = UserFinder::getById($entry);
                if(!is_null($user)) {
                    $ret = $user['real_name'];
                }
                break;
            case self::T_GROUP:
                $group = GroupFinder::getById($entry);
                if(!is_null($group)) {
                    $ret = $group['name'];
                }
                break;
        }
        return $ret;
    }
}

<?php

namespace FOO;

/**
 * Class Users_REST
 * REST endpoint for manipulating Users.
 * @package FOO
 */
class Users_REST extends Models_REST {
    const SLOG_TYPE = SLog::T_USER;

    protected static $MODEL = 'User';
    protected static $CREATABLE = [
        'name', 'real_name', 'password', 'email', 'admin', 'api_key'
    ];
    protected static $QUERYABLE = [
        'name'
    ];
    protected static $READABLE = [
        'name', 'real_name', 'email', 'admin', 'settings', 'api_key'
    ];
    protected static $UPDATEABLE = [
        'name', 'real_name', 'password', 'email', 'admin', 'settings', 'api_key'
    ];

    public function allowRead() {
        return Auth::isAuthenticated();
    }
    public function allowCreate() {
        return Auth::isAdmin();
    }
    public function allowUpdate() {
        return Auth::isAuthenticated();
    }
    public function allowDelete() {
        return Auth::isAdmin();
    }

    public function filterFields(Model $model, array $readable=null) {
        $data = parent::filterFields($model, $readable);
        // Filter the list of readable fields
        if(!Auth::isAdmin() && $model['id'] !== Auth::getUserId()) {
            unset($data['api_key']);
        }
        return $data;
    }

    public function POST(array $get, array $data) {
        $data['password'] = password_hash(Util::get($data, 'password', ''), PASSWORD_DEFAULT);

        return self::format($this->create($get, $data));
    }

    public function PUT(array $get, array $data) {
        $target_id = (int) Util::get($get, 'id');
        // Only an admin user or the user themself should be able to modify a user!
        // That check is done here. Note that the strict compare is necessary as getUserId()
        // can return null.
        if(!Auth::isAdmin() && $target_id !== Auth::getUserId()) {
            throw new ForbiddenException;
        }
        $pass = Util::get($data, 'password', '');

        // Don't set the password if a blank one was provided.
        if(strlen($pass) == 0) {
            unset($data['password']);
        } else {
	        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
	    }

        // If the api_key field is blank, don't set it.
        if(strlen($data['api_key']) == 0) {
            unset($data['api_key']);
        }

        // If not an admin, don't allow setting the admin field.
        if(!Auth::isAdmin()) {
            unset($data['admin']);
        }

        return self::format($this->update($get, $data));
    }
}

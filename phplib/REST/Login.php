<?php

namespace FOO;

/**
 * Class Login_REST
 * REST endpoint for handling authentication.
 * @package FOO
 */
class Login_REST extends REST {
    public function checkAuthorization() {}

    public function POST(array $get, array $data) {
        $ret = null;
        $name = Util::get($data, 'name');
        $pass = Util::get($data, 'password');
        if($name && $pass) {
            $user = Auth::login($name, $pass);
            if(!is_null($user)) {
                $ret = $user['id'];
            }
        } else if(Util::exists($_GET, 'logout')) {
            Auth::logout();
        }

        return self::format($ret);
    }
}

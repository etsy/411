<?php

namespace FOO;

/**
 * Class ProxyAuth
 * Handles authentication when run behind a SAML Proxy.
 * @package FOO
 */
class ProxyAuth {

    public static $enabled = false;
    public static $header_name = null;
    public static $auto_sign_up = false;
    public static $subject_is_email = false;
    public static $domain = null;

    public static function init() {
      $cfg = Config::get('proxy_auth');

      if(is_null($cfg['enabled']) || !$cfg['enabled']){
        return;
      }
      self::$enabled = true;

      if(!is_null($cfg['subject_is_email'])) {
        self::$subject_is_email = $cfg['subject_is_email'];
      }

      if(!is_null($cfg['auto_sign_up'])) {
        self::$auto_sign_up = $cfg['auto_sign_up'];
      }

      if(!is_null($cfg['domain'])) {
        self::$domain = $cfg['domain'];
      }

      self::$header_name = sprintf('HTTP_%s', strtoupper(str_replace("-","_", $cfg['header_name'])));
    }

    public static function createUser() {
      $user = new User();
      $user['name'] = self::getUserName();
      $user['real_name'] = 'Proxy Auth';
      $user['password'] = password_hash(Random::base64_bytes(12), PASSWORD_DEFAULT);
      $user['email'] = self::getEmailAddress();
      $user['admin'] = false;
      $user['api_key'] = Random::base64_bytes(User::API_KEY_LEN);
      $user->store();

      return $user;
    }

    /**
     * Returns whether Proxy Auth is available via config and headers passed.
     * @return bool Whether Proxy Auth is available.
     */
    public static function available() {
      if(!self::$enabled){
        return false;
      }
      if(array_key_exists(self::$header_name, $_SERVER)) {
        if(strlen($_SERVER[self::$header_name]) == 0) {
          return false;
        }
        return true;
      }
      return false;
    }

    /**
     * Returns Username from SAML Header.
     * @return string Username.
     */
    public static function getUserName() {
      if(self::$subject_is_email){
        return explode('@', $_SERVER[self::$header_name])[0];
      }
      return $_SERVER[self::$header_name];
    }

    /**
     * Returns Email from SAML Header plus configuration.
     * @return string Email.
     */
    public static function getEmailAddress() {
      if(self::$subject_is_email){
        return $_SERVER[self::$header_name];
      }
      return sprintf("%s@%s", $_SERVER[self::$header_name], self::$domain);
    }
}

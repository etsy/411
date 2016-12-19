<?php

namespace FOO;

/**
 * Class StatusException
 * Base class for HTTP status exceptions.
 * @package FOO
 */
abstract class StatusException extends \Exception {
    /** @var string Status string. */
    public static $MSG = 'Unknown';
    /**
     * Constructor.
     */
    public function __construct($msg=null) {
        $this->message = is_null($msg) ? static::$MSG:$msg;
    }
    abstract public function getStatus();
}

/**
 * Class NotFoundException
 * HTTP 404 Error
 * @package FOO
 */
class NotFoundException extends StatusException {
    public static $MSG = 'Not found';
    public function getStatus() { return 404; }
}

/**
 * Class UnauthorizedException
 * HTTP 401 Error
 * @package FOO
 */
class UnauthorizedException extends StatusException {
    public static $MSG = 'Unauthorized';
    public function getStatus() { return 401; }
}

/**
 * Class ForbiddenException
 * HTTP 403 Error
 * @package FOO
 */
class ForbiddenException extends StatusException {
    public static $MSG = 'Forbidden';
    public function getStatus() { return 403; }
}

/**
 * Class InternalErrorException
 * HTTP 500 Error
 * @package FOO
 */
class InternalErrorException extends StatusException {
    public static $MSG = 'Server error';
    public function getStatus() { return 500; }
}

/**
 * Class NotImplementedException
 * HTTP 501 Error
 * @package FOO
 */
class NotImplementedException extends StatusException {
    public static $MSG = 'Not implemented';
    public function getStatus() { return 501; }
}

/**
 * Class REST
 * REST router.
 * @package FOO
 */
abstract class REST {
    /**
     * Route a request to the appropriate method. Handles different input types and exceptions. Outputs JSON to the
     * client.
     */
    public function route() {
        $ret = self::format();
        try {
            $this->checkAuthorization();

            // Attempt to read input as JSON.
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            if(is_null($data)) {
                $data = $_POST;
            }
            // Check the nonce if this is not a GET request.
            if($_SERVER['REQUEST_METHOD'] != 'GET' && Auth::isWeb()) {
                $nnc1 = Util::get($_SERVER, 'HTTP_X_NONCE');
                $nnc2 = Util::get($data, '_nonce');
                if(strlen($nnc1) == 0 && strlen($nnc2) == 0) {
                    throw new UnauthorizedException('Empty nonce specified');
                }
                if(!Nonce::check($nnc1) && !Nonce::check($nnc2)) {
                    throw new UnauthorizedException('Invalid nonce specified');
                }
            }

            // Now try running the appropriate method.
            switch($_SERVER['REQUEST_METHOD']) {
                case 'GET': $ret = $this->GET($_GET); break;
                case 'POST': $ret = $this->POST($_GET, $data); break;
                case 'PUT': $ret = $this->PUT($_GET, $data); break;
                case 'DELETE': $ret = $this->DELETE($_GET, $data); break;
                default: throw new NotImplementedException('Method not supported');
            }
        } catch(StatusException $e) {
            http_response_code($e->getStatus());
            $ret['success'] = false;
            $ret['message'] = $e->getMessage();
            Logger::except($e);
        } catch(ValidationException $e) {
            http_response_code(500);
            $ret['success'] = false;
            $ret['message'] = $e->getMessage();
            Logger::except($e);
        } catch(\Exception $e) {
            http_response_code(500);
            $ret['success'] = false;
            $ret['message'] = $e->getMessage();
            Logger::except($e);
        }
        header('Content-Type: application/json charset=utf-8');
        print json_encode($ret);
        exit();
    }

    /**
     * Verifies that the current user is allowed to execute this request.
     * @throws StatusException
     */
    public function checkAuthorization() {
        if(!Auth::isAuthenticated()) {
            throw new UnauthorizedException('Authentication required');
        }
    }

    /**
     * Format data for output.
     * @param array $data The output data.
     * @param bool $success Whether the request was successful.
     * @param string[]|string $message The message.
     * @return array Formatted data.
     */
    public function format($data=null, $success=true, $message='') {
        // Return the existing object if it's already formatted.
        if(
            is_array($data) &&
            Util::exists($data, 'data') && Util::exists($data, 'success') &&
            Util::exists($data, 'message') && Util::exists($data, 'authenticated')
        ) {
            return $data;
        }

        return [
            'data' => $data,
            'success' => $success,
            'message' => $message,
            'authenticated' => Auth::isAuthenticated()
        ];
    }

    /**
     * GET request.
     * @param array $get The url parameters.
     * @return array Data.
     * @throws NotImplementedException
     * @suppress PhanTypeMissingReturn
     */
    public function GET(array $get) {
        throw new NotImplementedException('Method not supported');
    }

    /**
     * POST request.
     * @param array $get The url parameters.
     * @param array $data The body parameters.
     * @return array Data.
     * @throws NotImplementedException
     * @suppress PhanTypeMissingReturn
     */
    public function POST(array $get, array $data) {
        throw new NotImplementedException('Method not supported');
    }

    /**
     * PUT request.
     * @param array $get The url parameters.
     * @param array $data The body parameters.
     * @return array Data.
     * @throws NotImplementedException
     * @suppress PhanTypeMissingReturn
     */
    public function PUT(array $get, array $data) {
        throw new NotImplementedException('Method not supported');
    }

    /**
     * DELETE request.
     * @param array $get The url parameters.
     * @param array $data The body parameters.
     * @return array Data.
     * @throws NotImplementedException
     * @suppress PhanTypeMissingReturn
     */
    public function DELETE(array $get, array $data) {
        throw new NotImplementedException('Method not supported');
    }
};

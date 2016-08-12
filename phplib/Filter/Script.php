<?php

namespace FOO;

/**
 * Class Script_Filter
 * Transforms raw json into an Alert by running a script on the output data. Script are stored in phplib/Filter/Script.
 *
 * Each script should reside in a directory, with a file called init. Ex: Script/Null/init
 * The script is passed the content of the Alert as a JSON string via STDIN.
 * It has to output a valid JSON string to STDOUT.
 * If the string 'null' is output, the Alert will be dropped.
 * @package FOO
 */
class Script_Filter extends Filter {
    public static $TYPE = 'script';
    public static $SCRIPTS = [
        'null' => 'Null',
    ];

    public static $DESC = 'Passes the json results from a search to a script specified in <script>.';

    protected static function generateDataSchema() {
        return [
            'script' => [static::T_ENUM, static::$SCRIPTS, '']
        ];
    }
    /**
     * Return the Alert after it's been passed through the script.
     * @param Alert $alert The Alert object.
     * @param int $date The current date.
     * @return Alert[] The Alert object.
     * @throws  FilterException
     */
    public function process(Alert $alert, $date) {
        $script = self::$SCRIPTS[$this->obj['data']['script']];
        $spec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
        ];
        $script_dir_list = [
            sprintf('%s/extlib/Filter/Script/%s', BASE_DIR, $script),
            sprintf('%s/phplib/Filter/Script/%s', BASE_DIR, $script),
        ];

        // Look for the script in the usual places.
        foreach($script_dir_list as $script_dir) {
            $script_file = sprintf('%s/init', $script_dir);
            if(file_exists($script_file)) {
                $env = $_SERVER;
                $env['argv'] = null;
                $process = proc_open($script_file, $spec, $pipes, $script_dir, $env);

                // Write the json blob to STDIN.
                fwrite($pipes[0], json_encode((object)$alert['content']));
                fclose($pipes[0]);

                // Read output from STDOUT.
                $output = stream_get_contents($pipes[1]);
                //Logger::info($output); // for debugging script issues
                fclose($pipes[1]);

                // Ensure success.
                $ret = proc_close($process);
                if($ret != 0) {
                    throw new FilterException(sprintf('Return code: %d', $ret));
                }

                if($output == 'null') {
                    return [];
                } else {
                    $json_output = json_decode(trim($output), true);
                    if(is_null($json_output)) {
                        throw new FilterException('Invalid output');
                    }
                    $alert['content'] = $json_output;
                    return [$alert];
                }
            }
        }

        throw new FilterException('Script not found');
    }
}

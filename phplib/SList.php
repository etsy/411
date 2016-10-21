<?php

namespace FOO;

/**
 * SList Class
 * A List contains a series of values to be inserted into search queries.
 * @package FOO
 */
class SList extends Model {
    public static $TABLE = 'lists';
    public static $PKEY = 'list_id';

    // Types.
    /** Json list type. */
    const T_JSON = 0;
    /** CSV list type. */
    const T_CSV = 1;
    /** LSV list type. */
    const T_LSV = 2;
    /** @var string[] Mapping of types to a user-friendly string. */
    public static $TYPES = [
        self::T_JSON => 'JSON',
        self::T_CSV => 'Comma separated',
        self::T_LSV => 'Line separated'
    ];

    protected static function generateSchema() {
        return [
            'type' => [static::T_ENUM, static::$TYPES, self::T_JSON],
            'url' => [static::T_STR, null, ''],
            'name' => [static::T_STR, null, '']
        ];
    }

    /**
     * Pull data from the specified url.
     * @return array|null The data from the list.
     */
    public function getData() {
        $curl = new Curl;
        $raw_data = $curl->get($this->obj['url']);

        $ret = null;
        if($curl->httpStatusCode == 200 && $raw_data) {
            switch($this->obj['type']) {
                case self::T_JSON:
                    if(is_array($raw_data)) {
                        $ret = $raw_data;
                    }
                    break;
                case self::T_CSV:
                    $ret = explode(',', $raw_data);
                    if(array_slice($ret, -1)[0] == '') {
                        array_pop($ret);
                    }
                    break;
                case self::T_LSV:
                    $ret = explode("\n", $raw_data);
                    if(array_slice($ret, -1)[0] == '') {
                        array_pop($ret);
                    }
                    break;
            }
        }

        return $ret;
    }

    public function validateData(array $data) {
        parent::validateData($data);

        if(strlen(trim($data['name'])) == 0) {
            throw new ValidationException('Invalid name');
        }
        $proto = strtolower(parse_url($data['url'], PHP_URL_SCHEME) ?: '');
        if($proto !== 'http' && $proto !== 'https') {
            throw new ValidationException('Invalid url');
        }
        if(!is_array($this->getData())) {
            throw new ValidationException('Invalid data from url');
        }
    }
}

/**
 * Class SListFinder
 * Finder for Lists.
 * @package FOO
 * @method static SList getById(int $id, bool $archived=false)
 * @method static SList[] getAll()
 * @method static SList[] getByQuery(array $query=[], $count=null, $offset=null, $sort=[], $reverse=null)
 * @method static SList[] hydrateModels($objs)
 */
class SListFinder extends ModelFinder {
    public static $MODEL = 'SList';
}

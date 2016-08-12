<?php

namespace FOO;

/**
 * Class SearchLog
 * Stores a snapshot of a Search. Used to implement the Search changelogs.
 * @package FOO
 */
class SearchLog extends Model {
    public static $TABLE = 'search_logs';
    public static $PKEY = 'log_id';

    protected static function generateSchema() {
        return [
            'user_id' => [static::T_NUM, null, 0],
            'search_id' => [static::T_NUM, null, 0],
            'data' => [static::T_OBJ, null, []],
            'description' => [static::T_STR, null, '']
        ];
    }

    protected function serialize(array $data) {
        $data['data'] = json_encode((object)$data['data']);
        return parent::serialize($data);
    }

    protected function deserialize(array $data) {
        $data['data'] = json_decode($data['data'], true);
        return parent::deserialize($data);
    }
}

/**
 * Class SearchLogFinder
 * Finder for SearchLogs.
 * @package FOO
 * @method static SearchLog getById(int $id, bool $archived=false)
 * @method static SearchLog[] getAll()
 * @method static SearchLog[] getByQuery(array $query=[], $count=null, $offset=null, $sort=[], $reverse=null)
 * @method static SearchLog[] hydrateModels($objs)
 */
class SearchLogFinder extends ModelFinder {
    public static $MODEL = 'SearchLog';
}

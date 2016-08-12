<?php

namespace FOO;

/**
 * Class Element
 * Base class for Target and Filter.
 * @package FOO
 */
abstract class Element extends TypeModel {
    public static $TYPE = '';
    public static $DESC = '';

    /**
     * Generate the schema for this model.
     * Elements must have a lifetime field.
     */
    protected static function generateSchema() {
        return [
            'lifetime' => [self::T_NUM, null, 0],
        ];
    }

    /**
     * Retrieve the data schema for this model.
     * @return array The data schema.
     */
    public static function getDataSchema() {
        static $schema = null;
        if(is_null($schema)) {
            $schema = static::generateDataSchema();
        }

        return $schema;
    }

    /**
     * Generate the data schema for this model.
     * @return array The data schema.
     */
    protected static function generateDataSchema() {
        return [];
    }

    /**
     * Get default values for data fields.
     * @return array The default values.
     */
    public static function getDataDefaults() {
        return array_map(function($x) {
            return $x[2];
        }, static::getDataSchema());
    }

    /**
     * Element constructor.
     * Initializes the default data attributes.
     * @param array $data The model attributes.
     */
    public function __construct(array $data=null) {
        parent::__construct($data);

        if(is_null($data)) {
            $this->obj['data'] = static::getDataDefaults();
        }
    }

    /**
     * Creates a new Element of the appropriate type.
     * @param string $type The type of the Element.
     * @param array $data The attributes for the Element.
     * @return Element The new Element.
     */
    public static function newElement($type, $data=null) {
        return self::newObject($type, $data);
    }

    protected function serialize(array $data) {
        $data['data'] = json_encode((object)$data['data']);
        return parent::serialize($data);
    }

    protected function deserialize(array $data) {
        $data['data'] = json_decode($data['data'], true);
        return parent::deserialize($data);
    }

    public function validateData(array $data) {
        parent::validateData($data);

        if($data['lifetime'] < 0) {
            throw new ValidationException('Invalid range');
        }

        // Validate each of the values inside `data`.
        $dataschema = static::getDataSchema();
        foreach($dataschema as $field=>$settings) {
            $this->validateField($field, $settings, $data['data'][$field]);
        }

        // Verify all the keys in `data` are expected.
        foreach($data['data'] as $k=>$v) {
            if(!Util::exists($dataschema, $k)) {
                throw new ValidationException(sprintf('Invalid data key: %s', $k));
            }
        }
    }

    /**
     * Process an Alert object.
     * @param Alert $alert The Alert object.
     * @param int $date The current date.
     */
    abstract public function process(Alert $alert, $date);

    /**
     * Finish processing these alerts.
     * @param int $date The current date.
     */
    abstract public function finalize($date);
};

/**
 * Class ElementFinder
 * Finder for Elements.
 * @package FOO
 * @method static Element getById(int $id, bool $archived=false)
 * @method static Element[] getAll()
 * @method static Element[] getByQuery(array $query=[], $count=null, $offset=null, $sort=[], $reverse=null)
 * @method static Element[] hydrateModels($objs)
 */
class ElementFinder extends TypeModelFinder {
    public static function getBySearch($id) {
        return self::getByQuery(['search_id' => $id]);
    }

    /**
     * Archive Elements that have expired.
     * @param int $date The current date.
     */
    public static function reap($date) {
        $MODEL = 'FOO\\' . static::$MODEL;
        $sql = sprintf(
            'UPDATE `%s` SET `archived` = 1 WHERE `site_id` = ? AND `archived` = 0 AND `lifetime` > 0 AND ? >= (`create_date` + `lifetime` * 60 - 5)',
        $MODEL::$TABLE);
        DB::query($sql, [SiteFinder::getCurrentId(), $date]);
    }
}

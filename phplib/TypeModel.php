<?php

namespace FOO;

/**
 * Class TypeModel
 * Represents models that can dynamically construct a sub class based on a type string.
 * @package FOO
 */
abstract class TypeModel extends Model {
    // List of types. getTypes() should be used instead of referencing this directly.
    public static $TYPES = [];
    /** @var string Sub-type of this model. */
    public static $TYPE = '';

    /**
     * Return a mapping of all type slugs to their actual class name.
     * @return array The type mapping.
     */
    public static function getTypes() {
        static $type_map = null;
        if(is_null($type_map)) {
            $type_map = [];
            // Populate the list of types.
            foreach(static::$TYPES as $class) {
                $class = 'FOO\\' . $class;
                $type_map[$class::$TYPE] = $class;
            }
            // Remove any types that aren't accessible. We do it this way because
            // getTypes is called by instantiating a class. All the types must be
            // available before that happens.
            foreach($type_map as $type=>$class) {
                if(!(new $class)->isAccessible()) {
                    unset($type_map[$type]);
                    continue;
                }
            }
        }

        return $type_map;
    }

    /**
     * Return the type.
     */
    public static function getType() {
        return static::$TYPE;
    }

    /**
     * Retrieve the schema for this model.
     * @return array The schema.
     */
    public static function getSchema() {
        static $schema = null;
        if(is_null($schema)) {
            $schema = static::generateSchema();
            $schema['type'] = [self::T_ENUM, static::getTypes(), ''];
        }

        return $schema;
    }

    /**
     * Creates a new object of the appropriate type.
     * @param string $type The type of the object.
     * @param array $data The attributes for the object.
     * @return object The new object.
     */
    public static function newObject($type, $data=null) {
        $type_map = static::getTypes();
        if(!array_key_exists($type, $type_map)) {
            throw new \OutOfBoundsException(sprintf('Invalid type: %s', $type));
        }

        return new $type_map[$type]($data);
    }

    /**
     * Constructs the object and keeps track of the type.
     * @param array $data The model attributes.
     */
    public function __construct(array $data=null) {
        parent::__construct($data);

        $this->obj['type'] = static::$TYPE;
    }

    /**
     * Whether the Model is available. Can be used to enforce access control on a Model.
     * @return bool The availability of the Model.
     */
    public function isAccessible() {
        return true;
    }
}

/**
 * Class TypeModelFinder
 * Base finder for TypeModels.
 * @package FOO
 * @method static TypeModel getById(int $id, bool $archived=false)
 * @method static TypeModel[] getAll()
 * @method static TypeModel[] getByQuery(array $query=[], $count=null, $offset=null, $sort=[], $reverse=null)
 * @method static TypeModel[] hydrateModels($objs)
 */
class TypeModelFinder extends ModelFinder {
    /**
     * Hydrates TypeModels into the correct subclass.
     * @param array $obj The attribute data.
     * @return object The hydrated TypeModel.
     */
    protected static function construct(array $obj) {
        $model = 'FOO\\' . static::$MODEL;
        return $model::newObject(Util::get($obj, 'type'), $obj);
    }
}

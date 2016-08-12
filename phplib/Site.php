<?php

namespace FOO;

/**
 * Class Site
 * Represents a 411 site. Most Models are owned by a Site.
 * @package FOO
 */
class Site extends Model {
    public static $TABLE = 'sites';
    public static $PKEY = 'site_id';
    public static $PERSITE = false;

    /** Invalid site. */
    const NONE = 0;

    protected static function generateSchema() {
        return [
            'host' => [static::T_STR, null, ''],
            'name' => [static::T_STR, null, '']
        ];
    }
}

/**
 * Class SiteFinder
 * Finder for Sites.
 * @package FOO
 * @method static Site getById(int $id, bool $archived=false)
 * @method static Site[] getAll()
 * @method static Site[] getByQuery(array $query=[], $count=null, $offset=null, $sort=[], $reverse=null)
 * @method static Site[] hydrateModels($objs)
 */
class SiteFinder extends ModelFinder {
    public static $MODEL = 'Site';
    private static $site = Site::NONE;

    /**
     * Set the currently active Site.
     * @param Site $site The Site.
     */
    public static function setSite(Site $site) {
        self::$site = $site;
    }

    /**
     * Clear the currently active Site.
     */
    public static function clearSite() {
        self::$site = Site::NONE;
    }

    /**
     * Returns the currently active Site.
     * @return Site The currently active Site.
     */
    public static function getCurrent() {
        $host = getenv('411HOST') ?: Util::get($_SERVER, '411HOST', '') ?: Util::get($_SERVER, 'HTTP_HOST', '');
        if(self::$site === Site::NONE && strlen($host) > 0) {
            $sites = static::getByQuery(['host' => $host]);
            if(count($sites)) {
                self::$site = $sites[0];
            }
        }
        return self::$site;
    }

    /**
     * Returns the id of the currently active Site.
     * @return int The id of the currently active Site.
     */
    public static function getCurrentId() {
        $site = self::getCurrent();
        return $site ? $site['id']:Site::NONE;
    }
}

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
            'name' => [static::T_STR, null, ''],
            'secure' => [static::T_BOOL, null, true],
        ];
    }

    /**
     * Generate a url.
     * @param string $path The path.
     * @param string[] $params URL query parameters.
     * @return string The generated url.
     */
    public function urlFor($path='', $params=[]) {
        $url_parts = [
            $this->obj['secure'] ? 'https':'http', '://',
            $this->obj['host']
        ];
        if(strlen($path) > 0 && $path != '/') {
            $url_parts[] = '/';
            $url_parts[] = $path;
        }
        if(count($params) > 0) {
            $url_parts[] = '?';
            $url_parts[] = http_build_query($params);
        }

        return implode('', $url_parts);
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
     * @return Site|int The currently active Site.
     */
    public static function getCurrent() {
        $host =
            getenv('FOURONEONEHOST') ?:
            getenv('411HOST') ?:
            Util::get($_SERVER, 'FOURONEONEHOST', '') ?:
            Util::get($_SERVER, '411HOST', '') ?:
            Util::get($_SERVER, 'HTTP_HOST', '');
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

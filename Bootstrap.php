<?php
/**
 * This class belongs to the Pub namespace
 * \addToGroup Pub
 */

/**
 * test
 */
namespace Pub;
/**
 * Class file for Crabapple Bootstrap
 *
 * @author $Author$
 * @class Bootstrap
 */

// We extend off the existing bootstrapper...
require_once(__DIR__ . "/../packages/Viacom/Crabapple-ENT/classes/Bootstrap.php");

class Bootstrap extends \Viacom\VMN\ENT\Crabapple\Bootstrap
{
    /**
     * Disable Site Config
     *
     * @var string[]
     */
    public static $cmsSiteConfigEnabled = false;

    /**
     * Set config values
     */
    public function loadConfiguration()
    {
        parent::loadConfiguration();
        $this->getCrabappleSystem()->components->configuration->sitePackagePath = __DIR__;
    }

    protected function setAutoload()
    {
        spl_autoload_register('\Pub\Bootstrap::autoload');
        parent::setAutoload();
    }

    public static function autoload($className)
    {
        static::psr0And4Autoload($className, "Pub", __DIR__ . DIRECTORY_SEPARATOR);
    }
}
<?php
/**
 * @package   phing.tasks.ext
 * @copyright Copyright (c) 2011 Mike Rötgers (http://roetgers.org)
 * @license   http://roetgers.org/license/new-bsd New BSD License
 */

require_once 'phing/Task.php';
require_once 'phing/BuildException.php';

/**
 * Phing task for communicating with Memcache
 *
 * @package   phing.tasks.ext
 * @author    Mike Rötgers <mike@roetgers.org>
 */
class MemcacheTask extends Task
{
    /**
     * Memcache instance
     *
     * @var Memcache
     */
    protected $_memcache;
    
    /**
     * @var string
     */
    protected $_action;

    /**
     * hostname or ip address
     *
     * @var string
     */
    protected $_host;

    /**
     * port number
     *
     * @var int
     */
    protected $_port;

    /**
     * Memcache record key
     *
     * @var string
     */
    protected $_key;

    /**
     * Memcache record value
     *
     * @var mixed
     */
    protected $_value;

    /**
     * Use properties in project namespace or data from setters?
     *
     * @var boolean
     */
    protected $_useProperties = false;

    /**
     * Prefix for properties in project namespace
     *
     * @var string
     */
    protected $_propertiesPrefix = 'memcache.';

    /**
     * Whether to halt execution on a memcache error or not
     *
     * @var boolean
     */
    protected $_haltOnFailure = false;

    /**
     * Set action
     *
     * @param string $action
     */
    public function setAction($action)
    {
        $this->_action = $action;
    }

    /**
     * Set host
     *
     * @param string $host
     */
    public function setHost($host)
    {
        $this->_host = $host;
    }

    /**
     * Set port
     *
     * @param int $port
     */
    public function setPort($port)
    {
        $this->_port = $port;
    }

    /**
     * Set memcache record key
     *
     * @param string $key
     */
    public function setKey($key)
    {
        $this->_key = $key;
    }

    /**
     * Set memcache record value
     *
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->_value = $value;
    }

    /**
     * Use project properties?
     *
     * @param boolean $useProperties
     */
    public function setUseProperties($useProperties)
    {
        $this->_useProperties = (bool)$useProperties;
    }

    /**
     * Set properties prefix
     *
     * @param string $propertiesPrefix
     */
    public function setPropertiesPrefix($propertiesPrefix)
    {
        $this->_propertiesPrefix = $propertiesPrefix;
    }

    /**
     * Set haltOnFailure flag
     *
     * @param boolean $flag
     */
    public function setHaltOnFailure($flag)
    {
        $this->_haltOnFailure = (bool)$flag;
    }

    /**
     * Main function
     *
     * Connects to memcache(s) and executes given action
     */
    public function main()
    {
        if ($this->_useProperties === true) {
            $memcacheServers = $this->_findAllKeys($this->project->getProperties(), $this->_propertiesPrefix);
        } else {
            if (empty($this->_host) || empty($this->_port)) {
                throw new BuildException('You must provide memcache host and port. Maybe the config is wrong?');
            }

            $memcacheServers = array(
                0 => array('host' => $this->_host, 'port' => $this->_port)
            );
        }

        foreach ($memcacheServers as $server) {
            $this->_connect($server['host'], $server['port']);

            if ($this->_action != 'flush' && empty($this->_key)) {
                throw new BuildException('You must provide a key in order to "' . $this->_action . '" something');
            }

            $returnValue = null;

            switch ($this->_action) {
                case 'add':
                    $this->log('Adding record "' . $this->_key . '" to memcache "' . $server['host'] . ':' . $server['port'] . '"');
                    $returnValue = $this->_memcache->add($this->_key, $this->_value);
                    break;
                case 'delete':
                    $this->log('Deleting record "' . $this->_key . '" from memcache "' . $server['host'] . ':' . $server['port'] . '"');
                    $returnValue = $this->_memcache->delete($this->_key);
                    break;
                case 'flush':
                    $this->log('Flushing memcache "' . $server['host'] . ':' . $server['port'] . '"');
                    $returnValue = $this->_memcache->flush();
                    break;
                case 'set':
                    $this->log('Setting record "' . $this->_key . '" in memcache "' . $server['host'] . ':' . $server['port'] . '"');
                    $returnValue = $this->_memcache->set($this->_key, $this->_value);
                    break;
                default:
                    throw new BuildException('Provided action "' . $this->_action . '" is unknown');
            }

            if ($returnValue === false && $this->_haltOnFailure === true) {
                throw new BuildException('Could not "' . $this->_action . '" on memcache "' . $server['host'] . ':' . $server['port'] . '"');
            }
        }
    }

    /**
     * Connect to memcache server
     *
     * @param string $host
     * @param int $port
     */
    protected function _connect($host, $port)
    {
        if ($this->_memcache instanceof Memcache) {
            $this->_memcache->close();
        }

        $this->_memcache = new Memcache;
        if (!$this->_memcache->connect($host, $port)) {
            throw new BuildException('Could not connect to memcache server "' . $host . ':' . $port . '"');
        }
    }

    /**
     * Filters out all host/port pairs for memcache configuration
     *
     * @param array $properties
     * @param string $prefix
     * @return array
     */
    protected function _findAllKeys(array $properties, $prefix)
    {
        $memcacheServers = array();

        ksort($properties);
        foreach ($properties as $key => $value) {
            if (strpos($key, $prefix) !== 0 || strpos($key, 'host') === false) {
                continue;
            }

            $portKey = preg_replace('/^(' . $prefix . '+)host$/', '${1}port', $key);

            if (!isset($properties[$portKey])) {
                continue; // incomplete host/port pair
            }

            $memcacheServers[] = array('host' => $value, 'port' => $properties[$portKey]);
        }

        if (empty($memcacheServers)) {
            throw new BuildException('Could not find any memcache servers. Maybe the config is wrong?');
        }

        return $memcacheServers;
    }
}
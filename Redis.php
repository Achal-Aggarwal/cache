<?php
/**
 * Part of the Joomla Framework Cache Package
 *
 * @copyright  Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Cache;

use Psr\Cache\CacheItemInterface;
use \Redis as RedisDriver;

/**
 * Redis cache driver for the Joomla Framework.
 *
 * @since  1.0
 */
class Redis extends Cache
{
    /**
     * Default hostname of redis server
     */
    const REDIS_HOST = '127.0.0.1';

    /**
     * Default port of redis server
     */
    const REDIS_PORT = 6379;

    /**
     * @var    \Redis  The redis driver.
     * @since  1.0
     */
    private $driver;


    /**
     * Constructor.
     *
     * @param   mixed  $options  An options array, or an object that implements \ArrayAccess
     *
     * @since   1.0
     * @throws  \RuntimeException
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        if (!extension_loaded('redis') || !class_exists('\Redis'))
        {
            throw new \RuntimeException('Redis not supported.');
        }
    }

    /**
     * This will wipe out the entire cache's keys
     *
     * @return  boolean  The result of the clear operation.
     *
     * @since   1.0
     */
    public function clear()
    {
        $this->connect();

        return $this->driver->flushall();
    }

    /**
     * Method to get a storage entry value from a key.
     *
     * @param   string  $key  The storage entry identifier.
     *
     * @return  CacheItemInterface
     *
     * @since   1.0
     */
    public function get($key)
    {
        $this->connect();

        $value = $this->driver->get($key);
        $item = new Item($key);

        if (is_null($value) == false)
        {
            $item->setValue($value);
        }

        return $item;
    }

    /**
     * Method to remove a storage entry for a key.
     *
     * @param   string  $key  The storage entry identifier.
     *
     * @return  boolean
     *
     * @since   1.0
     */
    public function remove($key)
    {
        $this->connect();

        $result = (bool) $this->driver->del($key);

        return $result;
    }


    /**
     * Method to set a value for a storage entry.
     *
     * @param   string   $key    The storage entry identifier.
     * @param   mixed    $value  The data to be stored.
     * @param   integer  $ttl    The number of seconds before the stored data expires.
     *
     * @return  boolean
     *
     * @since   1.0
     */
    public function set($key, $value, $ttl = null)
    {
        $this->connect();

        if (!$this->driver->set($key, $value))
            return false;

        if ($ttl)
        {
            if (!$this->driver->expire($key, $ttl))
                return false;
        }

        return true;
    }

    /**
     * Method to determine whether a storage entry has been set for a key.
     *
     * @param   string  $key  The storage entry identifier.
     *
     * @return  boolean
     *
     * @since   1.0
     */
    protected function exists($key)
    {
        $this->connect();

        return $this->driver->exists($key);
    }


    /**
     * Connect to the Redis servers if the connection does not already exist.
     *
     * @return  void
     *
     * @since   1.0
     */
    private function connect()
    {
        // We want to only create the driver once.
        if (isset($this->driver))
        {
            return;
        }

        $host   = isset($this->options['redis.host'])? $this->options['redis.host'] : self::REDIS_HOST;
        $port   = isset($this->options['redis.port'])? $this->options['redis.port'] : self::REDIS_PORT;

        $this->driver = new RedisDriver();

        if (($host == 'localhost' || $this->isIP($host)))
        {
            $this->driver->connect('tcp://'. $host.':'.$port, $port);
        }
        else
        {
            $this->driver->connect($host, null);
        }
    }

    /**
     * Checks is given string is a valid TCP string
     *
     * @var string $ip Address to be validated
     *
     * @return bool True if given address is valid IP
     */
    protected function isIP($ip = '')
    {
        return filter_var($ip, FILTER_VALIDATE_IP);
    }

}

<?php
namespace Bart\Optional;

use Bart\Exceptions\IllegalStateException;

/**
 * Optional package class that handles a nonexistent value. This
 * class operates as a singleton.
 * Class Absent
 * @package Bart\Optional
 */
class Absent extends Optional
{

    /** @var Absent $instance */
    private static $instance;

    private function __construct()
    {

    }

    /**
     * @return Absent
     */
    public static function instance() {

        if (static::$instance === null) {
            static::$instance = new Absent();
        }

        return static::$instance;
    }

    /**
     * Whether or not the instance is present
     * @return bool
     */
    public function isPresent()
    {
        return false;
    }

    /**
     * Whether the instance is absent
     * @return bool
     */
    public function isAbsent()
    {
        return true;
    }

    /**
     * Gets the contained reference
     * @return mixed
     * @throws IllegalStateException
     */
    public function get()
    {
        throw new IllegalStateException("Trying to get a nonexistent value.");
    }

    /**
     * Gets the contained reference, or a provided default value if it is absent
     * @param mixed $default
     * @return mixed
     */
    public function getOrElse($default)
    {
        return $default;
    }

    /**
     * Gets the contained reference, or null if it is absent;
     * @return mixed
     */
    public function getOrNull()
    {
        return null;
    }

    /**
     * @param callable $callable
     * @return mixed
     */
    public function map(Callable $callable)
    {
        return Optional::absent();
    }

    /**
     * Whether the contained equals a provided object
     * @param mixed $object
     * @return bool
     */
    public function equals(Optional $object)
    {
       return $object instanceof Absent;
    }
}
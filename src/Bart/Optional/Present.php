<?php

namespace Bart\Optional;

/**
 * Class that handles containing an existent (non-null) value
 * Class Present
 * @package Bart\Optional
 */
class Present extends Optional
{
    /** @var mixed $ref */
    private $ref;

    /**
     * Creates an instance of Present with the passed object. Throws an exception
     * if the object is null
     * @param mixed $ref
     * @throws \Bart\Exceptions\IllegalStateException
     */
    public function __construct($ref)
    {
        $this->ref = self::notNull($ref);
    }

    /**
     * Whether or not the instance is present
     * @return bool
     */
    public function isPresent()
    {
        return true;
    }

    /**
     * Whether the instance is absent
     * @return bool
     */
    public function isAbsent()
    {
        return false;
    }

    /**
     * Gets the contained reference
     * @return mixed
     */
    public function get()
    {
        return $this->ref;
    }

    /**
     * Gets the contained reference, or a provided default value if it is absent
     * @param mixed $default
     * @return mixed
     */
    public function getOrElse($default)
    {
        return $this->ref;
    }

    /**
     * Gets the contained reference, or null if it is absent;
     * @return mixed
     */
    public function getOrNull()
    {
        return $this->ref;
    }

    /**
     * @param callable $callable
     * @return mixed
     */
    public function map(Callable $callable)
    {
        return new Present($callable($this->ref));
    }

    /**
     * Whether the contained equals a provided object
     * @param mixed $object
     * @return bool
     */
    public function equals(Optional $object)
    {
        if ($object instanceof Present) {
            return $this->ref === $object->get();
        }

        return false;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->ref;
    }
}
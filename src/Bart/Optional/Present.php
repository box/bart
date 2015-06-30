<?php

namespace Bart\Optional;

/**
 * Class that contains an optional value that exists (is not Null). This
 * class extends Optional. This class will not allow the contained reference
 * to be null and will throw exceptions if instantiated with a null value.
 *
 * In order to encapsulate a null value, use Optional::fromNullable.
 *
 * Implementation inspired by:
 * http://nitschinger.at/A-Journey-on-Avoiding-Nulls-in-PHP
 * https://gist.github.com/philix/7312211
 *
 * Class Present
 * @package Bart\Optional
 */
class Present extends Optional
{
    /** @var mixed $ref */
    private $ref;

    /**
     * Creates an instance of Present with the passed object. Throws an exception
     * if the object is null as Present may only contain a non-null value.
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
     * Returns the value of the option. This method will throw
     * exceptions for nonexistent values.
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
     * Gets the contained reference, or null if it is absent. The idea of
     * Optional is to avoid using null, but there may be cases where it is still relevant.
     * @return mixed
     */
    public function getOrNull()
    {
        return $this->ref;
    }

    /**
     * Returns an Optional containing the result of calling $callable on
     * the contained value. If no value exists, as in the case of Absent, then
     * this method will simply return Absent. The method will return Absent
     * if the result of applying $callable to the contained value is null.
     * @param callable $callable
     * @return Present|Absent
     */
    public function map(Callable $callable)
    {
        return Optional::fromNullable($callable($this->ref));
    }

    /**
     * Whether the contained value equals the value contained in another Optional
     * @param Optional $object
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

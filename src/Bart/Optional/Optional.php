<?php
namespace Bart\Optional;

use Bart\Exceptions\IllegalStateException;

/**
 * Abstract class that Optional classes such as Present and Absent should extend.
 * Class Optional
 * @package Bart\Optional
 */
abstract class Optional
{
    private function __construct()
    {

    }

    /**
     * Checks that a reference is not null and returns it or throws an exception if it is
     * @param mixed $reference
     * @param string|null $exceptionMessage
     * @return mixed
     * @throws IllegalStateException
     */
    protected static function notNull($reference, $exceptionMessage = null)
    {
        $message = $exceptionMessage === null ? 'Disallowed null in reference.' : $exceptionMessage;

        if ($reference === null) {
            throw new IllegalStateException($message);
        }

        return $reference;
    }

    /**
     * Returns an instance that contains no references
     * @return Absent
     */
    public static function absent()
    {
        return Absent::instance();
    }

    /**
     * Creates an instance containing the provided reference
     * @param mixed $ref
     * @return Present
     * @throws IllegalStateException
     */
    public static function from($ref)
    {
        // Present checks if the value is null on instantiation
        return new Present($ref);
    }

    /**
     * Returns an Optional instance of the reference or returns Absent if it's null
     * @param mixed $ref
     * @return Absent|Present
     */
    public static function fromNullable($ref)
    {
        return $ref === null ? static::absent() : new Present($ref);
    }

    /**
     * Whether or not the instance is present
     * @return bool
     */
    public abstract function isPresent();

    /**
     * Whether the instance is absent
     * @return bool
     */
    public abstract function isAbsent();

    /**
     * Gets the contained reference
     * @return mixed
     */
    public abstract function get();

    /**
     * Gets the contained reference, or a provided default value if it is absent
     * @param mixed $default
     * @return mixed
     */
    public abstract function getOrElse($default);

    /**
     * Gets the contained reference, or null if it is absent;
     * @return mixed
     */
    public abstract function getOrNull();

    /**
     * @param callable $callable
     * @return mixed
     */
    public abstract function map(Callable $callable);

    /**
     * Whether the contained equals a provided object
     * @param mixed $object
     * @return bool
     */
    public abstract function equals(Optional $object);

    /**
     * @return null
     */
    public function __toString() {
        return null;
    }

}
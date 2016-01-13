<?php
namespace Bart\Git;

/**
 * Class Person
 * Represents a Person in Git, i.e. an Author or a Committer
 * @package Bart\Git
 */
class Person
{
    /** @var string $name */
    private $name;
    /** @var string $email */
    private $email;

    /**
     * Person constructor.
     * @param string $name
     * @param string $email
     */
    public function __construct($name, $email)
    {
        $this->name = $name;
        $this->email = $email;
    }

    /**
     * @return string
     */
    public
    function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public
    function getEmail()
    {
        return $this->email;
    }

}

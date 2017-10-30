<?php

namespace Lyal\Checkr\Entities;

use Lyal\Checkr\Client;
use Lyal\Checkr\Exceptions\UnknownResourceException;
use Lyal\Checkr\Traits\Getable;
use Lyal\Checkr\Traits\HasAttributes;

abstract class AbstractEntity
{
    use HasAttributes, Getable;

    /**
     * Define the field allowed for each resource
     * Overridden in individual children.
     *
     * @var array
     */
    protected $fields = [];

    /**
     * @var \Lyal\Checkr\Client
     */
    private $client;

    /**
     * AbstractEntity constructor.
     *
     * @param string|null $values
     * @param Client|null $client
     */
    public function __construct($values = null, $client = null)
    {
        $this->setValues($values);
        $this->setClient($client);
    }

    /**
     * Attach the previously queried resource as classname_id
     * Allows for magic querying.
     *
     * @param AbstractEntity $object
     *
     * @return void
     */
    public function setPreviousObject(AbstractEntity $object)
    {
        $objectId = strtolower((new \ReflectionClass($object))->getShortName()).'_id';
        if (null !== $object->getAttribute('id') && $this->checkField($objectId)) {
            $this->setAttribute($objectId, $object->getAttribute('id'));
        }
    }

    /**
     * @param \StdClass|string $values
     *
     * return void
     */
    public function setValues($values)
    {
        /*
         * If we get a string, we assume that it's an ID here
         * to allow for loading objects easily
         */
        if (is_string($values)) {
            $this->setAttribute('id', $values);

            return;
        }

        foreach ((array) $values as $key => $value) {
            if (isset($value->object)) {
                $className = checkrEntityClassName($value->object);
                $value = new $className($value, $this->getClient());
                $value->setPreviousObject($this);
            }

            if (is_array($value)) {
                $list = collect($value);
                $value = $list;
            }
            $this->{$key} = $value;
        }
    }

    /**
     * @throws UnknownResourceException
     *
     * @return mixed
     */
    public function __call($name, $args)
    {
        return $this->getClient()->api($name, $args, $this);
    }

    /**
     * Get the client object; Client also handles routing between resources.
     *
     * @return \Lyal\Checkr\Client
     */
    public function getClient() : \Lyal\Checkr\Client
    {
        return $this->client;
    }

    /**
     * Set the client object; Client also handles routing between resources.
     *
     * @param Client $client
     *
     * @return void;
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get the resource name of an object.
     *
     * AdverseAction becomes adverse_actions
     *
     * @param AbstractEntity $object
     *
     * @return string
     */
    public function getResourceName($object = null) : string
    {
        $object = $object ?? $this;

        return snake_case(str_plural((new \ReflectionClass($object))->getShortName()));
    }

    /**
     * @param string|null $path
     * @param array|null  $values
     *
     * @return string
     */
    public function processPath($path = null, array $values = null) : string
    {
        $path = $path ?? $this->getResourceName();

        return str_replace_tokens($path, $values ?? $this->getAttributes(false));
    }

    /**
     * Set the fields for an object.
     *
     * @param array $fields
     */
    public function setFields($fields = [])
    {
        $this->fields = $fields;
    }

    /**
     * Return the fields for a resource.
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }
}

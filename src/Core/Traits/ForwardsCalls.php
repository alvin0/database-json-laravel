<?php

namespace DatabaseJson\Core\Traits;

trait ForwardsCalls
{
    /**
     * Forward a method call to the given object.
     *
     * @param  mixed  $object
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \Exception
     */
    protected function forwardCallTo($object, $method, $parameters)
    {
        try {
            $object->query->{$method}(...$parameters);
            return $object;
        } catch (\Exception $e) {
            $pattern = '~^Call to undefined method (?P<class>[^:]+)::(?P<method>[^\(]+)\(\)$~';

            if (!preg_match($pattern, $e->getMessage(), $matches)) {
                throw $e;
            }

            if ($matches['class'] != get_class($object) ||
                $matches['method'] != $method) {
                throw $e;
            }

            static::throwException($method);
        }
    }

    /**
     * Throw a bad method call exception for the given method.
     *
     * @param  string  $method
     * @return void
     *
     * @throws \Exception
     */
    protected static function throwException($method)
    {
        throw new \Exception('Call to undefined method %s::%s()', static::class, $method);
    }
}

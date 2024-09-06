<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\traits;

trait CanSaveQuietly
{
    /**
     * Update the model without firing any model events.
     *
     * @param array $attributes
     * @param array $options
     *
     * @return mixed
     */
    public function updateQuietly(array $attributes = [], array $options = []): mixed
    {
        return static::withoutEvents(function () use ($attributes, $options) {
            return $this->update($attributes, $options);
        });
    }

    public static function updateWithoutEvents(array $values, $conditions)
    {
        return static::withoutEvents(function () use ($values, $conditions) {
            static::where($conditions)->update($values);

            return static::where($conditions)->first();
        });
    }
}

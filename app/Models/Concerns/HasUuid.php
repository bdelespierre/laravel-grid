<?php

namespace App\Models\Concerns;

use Webpatser\Uuid\Uuid;

trait HasUuid
{
    /**
     * Sets the `incrementing` property to false, the id will be generated
     * on creation.
     *
     * @param  array  $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->incrementing = false;
    }

    /**
     * Generate a new random UUID and use if as primary key
     * when creating a new model.
     */
    protected static function bootHasUuid()
    {
        static::creating(function ($model) {
            $model->{$model->getKeyName()} = Uuid::generate()->string;
        });
    }
}
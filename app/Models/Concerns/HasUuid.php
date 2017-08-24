<?php

namespace App\Models\Concerns;

use Webpatser\Uuid\Uuid;

trait HasUuid
{
    /**
     * Uuid models are no longer capable to auto-increment index
     *
     * @return bool
     */
    public function getIncrementing()
    {
        return false;
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
<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use LogicException;

trait HasVersion
{
    /**
     * The relation's attribute that stores tuple's version.
     *
     * @var string
     */
    protected $versionAttribute = "version";

    /**
     * Perform a model update operation.
     *
     * @see \Illuminate\Database\Eloquent\Model::performUpdate
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return bool
     *
     * @throws \LogicException
     */
    protected function performUpdate(Builder $query)
    {
        if ($this->fireModelEvent('updating') === false) {
            return false;
        }

        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        $dirty = $this->getDirty();

        if (count($dirty) > 0) {
            $query = $this->setKeysForSaveQuery($query);
            $query = $this->setVersionForSaveQuery($query);

            $updated = $query->update($dirty + [
                'version' => $this->getVersionForSaveQuery() +1
            ]);

            if (!$updated) {
                throw new LogicException("Version mismatched when updating.");
            }

            $this->fireModelEvent('updated', false);
        }

        return true;
    }

    /**
     * Set the version for a save update query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function setVersionForSaveQuery(Builder $query)
    {
        $query->where($this->getVersionAttributeName(), '=', $this->getVersionForSaveQuery());

        return $query;
    }

    /**
     * Get the relation attribute's name that holds the version number for
     * current model.
     *
     * @return string
     */
    protected function getVersionAttributeName(): string
    {
        return $this->versionAttribute;
    }

    /**
     * Get the version's value for save query.
     *
     * @return int
     */
    protected function getVersionForSaveQuery(): int
    {
        $attr = $this->getVersionAttributeName();

        if (isset($this->original[$attr])) {
            return (int) $this->original[$attr];
        }

        if ($version = $this->getAttribute($attr)) {
            return (int) $version;
        }

        return 0;
    }
}

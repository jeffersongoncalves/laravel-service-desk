<?php

namespace JeffersonGoncalves\ServiceDesk\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Keeps a denormalized name/email snapshot alongside a morphTo actor
 * reference, and guards resolving that actor against a *_type class that
 * no longer exists in the resolving app (e.g. a satellite app, a queued
 * job running in a different context, or a renamed/removed host model) --
 * Eloquent's MorphTo otherwise fatals with `new $class` on a missing class.
 */
trait HasActorSnapshot
{
    public static function bootHasActorSnapshot(): void
    {
        static::saving(function (self $model): void {
            foreach ($model->actorSnapshots() as [$typeAttr, $idAttr, $nameAttr, $emailAttr]) {
                $model->snapshotActor($typeAttr, $idAttr, $nameAttr, $emailAttr);
            }
        });
    }

    /**
     * Each entry: [type column, id column, snapshot name column, snapshot email column].
     *
     * @return array<int, array{0: string, 1: string, 2: string, 3: string}>
     */
    abstract protected function actorSnapshots(): array;

    protected function snapshotActor(string $typeAttr, string $idAttr, string $nameAttr, string $emailAttr): void
    {
        if (! $this->isDirty($typeAttr) && ! $this->isDirty($idAttr)) {
            return;
        }

        $type = $this->{$typeAttr};
        $id = $this->{$idAttr};

        if (! $type || ! $id) {
            $this->{$nameAttr} = null;
            $this->{$emailAttr} = null;

            return;
        }

        $class = Model::getActualClassNameForMorph($type);

        if (! class_exists($class)) {
            return;
        }

        $actor = $class::find($id);

        $this->{$nameAttr} = $actor->name ?? null;
        $this->{$emailAttr} = $actor->email ?? null;
    }

    /**
     * Resolve a morphTo relation, but only if its stored type still maps to
     * a class that exists in this app -- returns null instead of letting
     * Eloquent fatal on `new $class` for a missing one.
     */
    protected function resolveActor(string $relation, string $typeAttr): ?Model
    {
        $type = $this->{$typeAttr};

        if (! $type) {
            return null;
        }

        $class = Model::getActualClassNameForMorph($type);

        if (! class_exists($class)) {
            return null;
        }

        return $this->getRelationValue($relation);
    }
}

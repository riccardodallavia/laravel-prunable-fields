<?php

namespace Maize\PrunableFields;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;
use Maize\PrunableFields\Events\ModelsFieldsPruned;

trait PrunableFields
{
    public function prunable(): array
    {
        return property_exists($this, 'prunable')
            ? $this->prunable
            : [];
    }

    public function pruneAllFields(int $chunkSize = 1000): int
    {
        $total = 0;

        $this
            ->prunableFields()
            ->when(
                in_array(SoftDeletes::class, class_uses_recursive(get_class($this))),
                fn ($query) => $query->withTrashed()
            )
            ->chunkById($chunkSize, function ($models) use (&$total) {
                $models->each->pruneFields();

                $total += $models->count();

                ModelsFieldsPruned::dispatch(static::class, $total);
            });

        return $total;
    }

    public function prunableFields(): Builder
    {
        throw new LogicException('Please implement the prunable method on your model.');
    }

    public function pruneFields(): bool
    {
        $this->pruningFields();

        return $this->update($this->prunable());
    }

    protected function pruningFields(): void
    {
        //
    }
}

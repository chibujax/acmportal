<?php

namespace App\Traits;

use App\Models\ActivityLog;

trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            if (! static::shouldLog()) return;

            $attrs = static::sanitize($model->getAttributes(), $model);

            ActivityLog::create([
                'user_id'       => auth()->id(),
                'action'        => 'created',
                'resource_type' => class_basename($model),
                'resource_id'   => $model->getKey(),
                'description'   => $model->getActivityDescription('created'),
                'new_values'    => $attrs ?: null,
                'ip_address'    => request()->ip(),
                'created_at'    => now(),
            ]);
        });

        static::updated(function ($model) {
            if (! static::shouldLog()) return;

            $changed = array_diff(array_keys($model->getChanges()), ['updated_at']);
            if (empty($changed)) return;

            $old = array_intersect_key($model->getOriginal(), array_flip($changed));
            $new = array_intersect_key($model->getChanges(), array_flip($changed));

            $old = static::sanitize($old, $model);
            $new = static::sanitize($new, $model);

            ActivityLog::create([
                'user_id'       => auth()->id(),
                'action'        => 'updated',
                'resource_type' => class_basename($model),
                'resource_id'   => $model->getKey(),
                'description'   => $model->getActivityDescription('updated'),
                'old_values'    => $old ?: null,
                'new_values'    => $new ?: null,
                'ip_address'    => request()->ip(),
                'created_at'    => now(),
            ]);
        });

        static::deleted(function ($model) {
            if (! static::shouldLog()) return;

            $attrs = static::sanitize($model->getAttributes(), $model);

            ActivityLog::create([
                'user_id'       => auth()->id(),
                'action'        => 'deleted',
                'resource_type' => class_basename($model),
                'resource_id'   => $model->getKey(),
                'description'   => $model->getActivityDescription('deleted'),
                'old_values'    => $attrs ?: null,
                'ip_address'    => request()->ip(),
                'created_at'    => now(),
            ]);
        });
    }

    public function getActivityDescription(string $action): string
    {
        return ucfirst($action) . ' ' . class_basename($this) . ' #' . $this->getKey();
    }

    private static function shouldLog(): bool
    {
        return ! app()->runningInConsole()
            && auth()->check()
            && auth()->user()->isAdmin();
    }

    private static function sanitize(array $attributes, $model): array
    {
        $hidden = method_exists($model, 'getHidden') ? $model->getHidden() : [];
        return collect($attributes)->except($hidden)->toArray();
    }
}

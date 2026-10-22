<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Trait to automatically record the authenticated user who created or last updated an Eloquent model.
 */
trait HasUserTracking
{
    /**
     * Boot the user tracking trait for the model.
     */
    public static function bootHasUserTracking(): void
    {
        static::creating(function (Model $model): void {
            if (empty($model->created_by_id) && Auth::check()) {
                $model->created_by_id = Auth::id();
            }

            if (empty($model->updated_by_id) && Auth::check()) {
                $model->updated_by_id = Auth::id();
            }
        });

        static::updating(function (Model $model): void {
            if (Auth::check() && ! $model->isDirty('updated_by_id')) {
                $model->updated_by_id = Auth::id();
            }
        });
    }

    /**
     * Get the user who created this record.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the user who last updated this record.
     *
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}

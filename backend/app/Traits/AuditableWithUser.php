<?php

namespace App\Traits;

/**
 * AuditableWithUser Trait
 * 
 * Automatically tracks who created, updated, and deleted records
 * by setting created_by, updated_by, and deleted_by foreign keys
 * 
 * Usage: Add `use AuditableWithUser;` to any model
 */
trait AuditableWithUser
{
    /**
     * Boot the auditable trait
     * Register model events for audit tracking
     */
    protected static function bootAuditableWithUser(): void
    {
        // When creating a record, set created_by to current user
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        // When updating a record, set updated_by to current user
        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        // When deleting (soft delete), set deleted_by to current user
        static::deleting(function ($model) {
            if (auth()->check() && $model->isDeletable()) {
                $model->deleted_by = auth()->id();
                $model->save();
            }
        });
    }

    /**
     * Get the user who created this record
     */
    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record
     */
    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this record
     */
    public function deletedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'deleted_by');
    }
}

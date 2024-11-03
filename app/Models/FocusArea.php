<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FocusArea extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'is_predefined'
    ];

    protected $casts = [
        'is_predefined' => 'boolean'
    ];

    // Relationship with Goals
    public function goals()
    {
        return $this->hasMany(Goal::class);
    }

    // Scope for predefined areas
    public function scopePredefined($query)
    {
        return $query->where('is_predefined', true);
    }

    // Scope for custom areas
    public function scopeCustom($query)
    {
        return $query->where('is_predefined', false);
    }
}

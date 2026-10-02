<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'methodology',
        'status',
        'color',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public const METHODOLOGY_AGILE = 'agile';
    public const METHODOLOGY_WATERFALL = 'waterfall';
    public const METHODOLOGY_MATRIX = 'matrix';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ARCHIVED = 'archived';

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}

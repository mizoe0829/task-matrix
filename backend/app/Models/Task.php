<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'priority_type',
        'due_date',
        'is_completed',
        'google_event_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_completed' => 'boolean',
        'due_date' => 'datetime',
    ];

    /**
     * Priority type constants for convenience
     */
    public const PRIORITY_URGENT_IMPORTANT = 'urgent_important';
    public const PRIORITY_NOT_URGENT_IMPORTANT = 'not_urgent_important';
    public const PRIORITY_URGENT_NOT_IMPORTANT = 'urgent_not_important';
    public const PRIORITY_NOT_URGENT_NOT_IMPORTANT = 'not_urgent_not_important';

    public const PRIORITIES = [
        self::PRIORITY_URGENT_IMPORTANT,
        self::PRIORITY_NOT_URGENT_IMPORTANT,
        self::PRIORITY_URGENT_NOT_IMPORTANT,
        self::PRIORITY_NOT_URGENT_NOT_IMPORTANT,
    ];
}

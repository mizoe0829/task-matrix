<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'task_scope',
        'methodology',
        'team_task_id',
        'assigned_to',
        'title',
        'description',
        'priority_type',
        'status',
        'agile_sprint',
        'agile_story_points',
        'waterfall_phase',
        'progress_rate',
        'start_date',
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
        'start_date' => 'datetime',
        'due_date' => 'datetime',
        'agile_story_points' => 'integer',
        'progress_rate' => 'integer',
    ];

    /**
     * Priority constants
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

    /**
     * Scope constants
     */
    public const SCOPE_TEAM = 'team';
    public const SCOPE_PERSONAL = 'personal';

    /**
     * Methodology constants
     */
    public const METHODOLOGY_AGILE = 'agile';
    public const METHODOLOGY_WATERFALL = 'waterfall';
    public const METHODOLOGY_MATRIX = 'matrix';

    /**
     * Status constants
     */
    public const STATUS_TODO = 'todo';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_REVIEW = 'review';
    public const STATUS_DONE = 'done';

    /**
     * Waterfall phases
     */
    public const PHASE_REQUIREMENT = 'requirement';
    public const PHASE_DESIGN = 'design';
    public const PHASE_DEVELOPMENT = 'development';
    public const PHASE_TESTING = 'testing';
    public const PHASE_RELEASE = 'release';

    /**
     * Relationship: The parent team task (if this is a personal task derived from team task)
     */
    public function teamTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'team_task_id');
    }

    /**
     * Relationship: Derived personal tasks created by members
     */
    public function personalTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'team_task_id');
    }
}

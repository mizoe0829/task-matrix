<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->enum('task_scope', ['team', 'personal'])->default('personal')->after('id');
            $table->enum('methodology', ['agile', 'waterfall', 'matrix'])->default('matrix')->after('task_scope');
            $table->foreignId('team_task_id')->nullable()->after('methodology')->constrained('tasks')->nullOnDelete();
            $table->string('assigned_to')->nullable()->after('team_task_id');
            $table->enum('status', ['todo', 'in_progress', 'review', 'done'])->default('todo')->after('is_completed');
            
            // Agile (Scrum / Kanban) fields
            $table->string('agile_sprint')->nullable()->after('status');
            $table->unsignedTinyInteger('agile_story_points')->nullable()->after('agile_sprint');

            // Waterfall (WBS / Phase) fields
            $table->enum('waterfall_phase', ['requirement', 'design', 'development', 'testing', 'release'])->nullable()->after('agile_story_points');
            $table->unsignedTinyInteger('progress_rate')->default(0)->after('waterfall_phase');
            $table->dateTime('start_date')->nullable()->after('progress_rate');

            // Indexes
            $table->index(['task_scope', 'methodology']);
            $table->index('assigned_to');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['team_task_id']);
            $table->dropColumn([
                'task_scope',
                'methodology',
                'team_task_id',
                'assigned_to',
                'status',
                'agile_sprint',
                'agile_story_points',
                'waterfall_phase',
                'progress_rate',
                'start_date',
            ]);
        });
    }
};

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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority_type', [
                'urgent_important',
                'not_urgent_important',
                'urgent_not_important',
                'not_urgent_not_important',
            ])->default('urgent_important');
            $table->dateTime('due_date')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->string('google_event_id')->nullable();
            $table->timestamps();

            // Index for faster query by quadrant and completion
            $table->index(['priority_type', 'is_completed']);
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};

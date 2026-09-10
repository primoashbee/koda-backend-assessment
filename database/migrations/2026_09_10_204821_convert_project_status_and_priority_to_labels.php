<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Status labels exposed by the API, keyed by the machine value they replace.
     *
     * @var array<string, string>
     */
    private const STATUS_LABELS = [
        'planning' => 'Planning',
        'in_progress' => 'In Progress',
        'on_hold' => 'On Hold',
        'completed' => 'Completed',
    ];

    /**
     * Priority labels exposed by the API, keyed by the machine value they replace.
     *
     * @var array<string, string>
     */
    private const PRIORITY_LABELS = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->replaceValues('status', self::STATUS_LABELS);
        $this->replaceValues('priority', self::PRIORITY_LABELS);

        Schema::table('projects', function (Blueprint $table) {
            $table->string('status')->default('Planning')->change();
            $table->string('priority')->default('Medium')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->replaceValues('status', array_flip(self::STATUS_LABELS));
        $this->replaceValues('priority', array_flip(self::PRIORITY_LABELS));

        Schema::table('projects', function (Blueprint $table) {
            $table->string('status')->default('planning')->change();
            $table->string('priority')->default('medium')->change();
        });
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function replaceValues(string $column, array $replacements): void
    {
        foreach ($replacements as $from => $to) {
            DB::table('projects')->where($column, $from)->update([$column => $to]);
        }
    }
};

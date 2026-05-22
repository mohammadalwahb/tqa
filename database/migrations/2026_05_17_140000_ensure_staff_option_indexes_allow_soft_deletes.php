<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropUniqueIfExists('staff_lookup_options', ['field', 'name']);
        $this->dropUniqueIfExists('staff_statuses', ['name']);
    }

    public function down(): void
    {
        // Intentionally empty — restoring strict uniques can break soft-delete restore flows.
    }

    /**
     * @param  list<string>  $columns
     */
    private function dropUniqueIfExists(string $table, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $indexName = $this->resolveUniqueIndexName($table, $columns);

        if ($indexName === null) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropIndex($indexName);
        });
    }

    /**
     * @param  list<string>  $columns
     */
    private function resolveUniqueIndexName(string $table, array $columns): ?string
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (! ($index['unique'] ?? false)) {
                continue;
            }

            $indexColumns = $index['columns'] ?? [];
            if ($indexColumns === $columns) {
                return $index['name'];
            }
        }

        return null;
    }
};

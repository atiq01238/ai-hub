
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('benchmarks', function (Blueprint $table) {
            if (! Schema::hasColumn('benchmarks', 'benchmark_class')) {
                $table->string('benchmark_class', 40)->default('unclassified')->index()->after('category');
            }
        });

        Schema::table('tools', function (Blueprint $table) {
            $table->decimal('benchmark_score', 4, 1)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            $table->decimal('benchmark_score', 4, 1)->default(0)->nullable(false)->change();
        });

        Schema::table('benchmarks', function (Blueprint $table) {
            if (Schema::hasColumn('benchmarks', 'benchmark_class')) {
                $table->dropIndex(['benchmark_class']);
                $table->dropColumn('benchmark_class');
            }
        });
    }
};

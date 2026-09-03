<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('sync_produits', 'service')) {
            Schema::table('sync_produits', function (Blueprint $table) {
                $table->boolean('service')->default(false);
            });
        }
    }

    public function down(): void
    {
        //
    }
};

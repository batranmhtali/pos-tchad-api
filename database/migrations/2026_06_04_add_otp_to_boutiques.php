<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boutiques', function (Blueprint $table) {
            // OTP sécurisé
            $table->string('otp_code', 64)->nullable()->after('email');
            $table->timestamp('otp_expire')->nullable()->after('otp_code');
            // Telegram : chat_id lié au numéro de téléphone
            $table->string('telegram_chat_id')->nullable()->after('otp_expire');

            // Multi-boutiques
            if (!Schema::hasColumn('boutiques', 'proprietaire_telephone')) {
                $table->string('proprietaire_telephone')->nullable()->after('telegram_chat_id');
            }
            if (!Schema::hasColumn('boutiques', 'est_principale')) {
                $table->boolean('est_principale')->default(true)->after('proprietaire_telephone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('boutiques', function (Blueprint $table) {
            $table->dropColumn(['otp_code', 'otp_expire']);
        });
    }
};

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
        Schema::table('mail_accounts', function (Blueprint $table) {
            $table->string('account_id')->nullable()->after('email')->comment('ID dari mail.tm API');
            $table->bigInteger('quota')->nullable()->after('bearer_token')->comment('Total quota dalam bytes');
            $table->bigInteger('used')->nullable()->after('quota')->comment('Space terpakai dalam bytes');
            $table->boolean('is_disabled')->default(false)->after('is_active')->comment('Disabled di mail.tm');
            $table->boolean('is_deleted')->default(false)->after('is_disabled')->comment('Deleted di mail.tm');
            $table->timestamp('account_created_at')->nullable()->after('notes')->comment('Waktu pembuatan di mail.tm');
            $table->timestamp('account_updated_at')->nullable()->after('account_created_at')->comment('Waktu update di mail.tm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mail_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'account_id',
                'quota',
                'used',
                'is_disabled',
                'is_deleted',
                'account_created_at',
                'account_updated_at'
            ]);
        });
    }
};

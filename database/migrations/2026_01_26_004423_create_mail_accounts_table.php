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
        Schema::create('mail_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Nama identifikasi akun');
            $table->string('email')->unique()->comment('Email address dari mail.tm');
            $table->string('domain')->comment('Domain email (contoh: @bugfoo.com)');
            $table->text('bearer_token')->comment('Bearer token untuk API mail.tm');
            $table->boolean('is_active')->default(true)->comment('Status aktif/nonaktif');
            $table->integer('message_count')->default(0)->comment('Total pesan yang di-fetch');
            $table->timestamp('last_fetch_at')->nullable()->comment('Waktu terakhir fetch');
            $table->text('notes')->nullable()->comment('Catatan tambahan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mail_accounts');
    }
};

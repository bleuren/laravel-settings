<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 執行遷移
     */
    public function up(): void
    {
        // 取得設定表名稱和資料庫連接
        $tableName = Config::get('settings.table', 'settings');
        $connection = Config::get('settings.database_connection');

        Schema::connection($connection)->create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('key', 191)->unique()->index();
            $table->string('description')->nullable();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * 還原遷移
     */
    public function down(): void
    {
        // 取得設定表名稱和資料庫連接
        $tableName = Config::get('settings.table', 'settings');
        $connection = Config::get('settings.database_connection');

        Schema::connection($connection)->dropIfExists($tableName);
    }
};

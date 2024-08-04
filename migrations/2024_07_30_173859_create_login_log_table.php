<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateLoginLogTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('login_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('用户id');
            $table->ipAddress('ip')->nullable()->comment('ip地址');
            $table->string('address')->nullable()->comment('登录地址');
            $table->string('browser')->nullable()->comment('浏览器类型');
            $table->string('operating_system')->nullable()->comment('操作系统');
            $table->smallInteger('status')->comment('登录状态, 1: 成功， 2:失败');
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_log');
    }
}

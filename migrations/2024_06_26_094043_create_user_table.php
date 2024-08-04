<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateUserTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user', function (Blueprint $table) {
            $table->id();
            $table->string('username',255)->unique()->comment('用户名');
            $table->string('email')->nullable()->comment('邮箱');
            $table->string('password',255)->comment('密码');
            $table->boolean('enable')->default(true)->comment('是否启用');
            $table->char('salt', 8);
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user');
    }
}

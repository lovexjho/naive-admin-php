<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateProfileTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('profile', function (Blueprint $table) {
            $table->id();
            $table->integer('gender')->nullable()->default(null)->comment('性别');
            $table->string('avatar')->nullable()->comment('头像');
            $table->string('email')->nullable()->comment('邮箱');
            $table->unsignedBigInteger('user_id')->comment('用户id');
            $table->string('address')->nullable()->comment('地址');
            $table->string('nickName')->nullable()->default(null)->comment('昵称');
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile');
    }
}

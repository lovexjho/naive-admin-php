<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateUpfileTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('upfile', function (Blueprint $table) {
            $table->id();
            $table->string('model_type')->comment('模型类型');
            $table->integer('model_id')->index()->comment('对应模型的id');
            $table->string('client_name')->comment('文件名');
            $table->string('path')->comment('文件路径');
            $table->string('mime')->comment('文件 MIME 类型');
            $table->integer('size')->comment('文件大小');
            $table->boolean('visible')->comment('是否可见');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upfile');
    }
}

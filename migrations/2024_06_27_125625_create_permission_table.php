<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreatePermissionTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('permission', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('菜单名称');
            $table->string('code')->comment('目录名');
            $table->string('type')->comment('类型');
            $table->integer('parentId')->nullable()->comment('父级id');
            $table->string('path')->nullable()->comment('路由地址');
            $table->string('redirect')->nullable()->comment('重定向地址');
            $table->string('icon')->nullable()->comment('图标');
            $table->string('component')->nullable()->comment('组件路径');
            $table->string('layout')->nullable()->comment('使用的布局');
            $table->boolean('keepAlive')->default(0)->nullable()->comment('是否缓存组件');
            $table->string('method')->nullable()->comment('请求方式');
            $table->boolean('enable')->default(true)->comment('是否启用');
            $table->string('description')->nullable()->comment('描述');
            $table->boolean('show')->default(1)->comment('是否显示');
            $table->integer('order')->default(99)->comment('排序');
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission');
    }
}
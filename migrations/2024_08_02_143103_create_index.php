<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateIndex extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('profile', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('user')
                ->onDelete('cascade');
        });

        Schema::table('role_permission', function (Blueprint $table) {
            $table->foreign('role_id')
                ->references('id')
                ->on('role')
                ->onDelete('cascade');
            $table->foreign('permission_id')
                ->references('id')
                ->on('permission')
                ->onDelete('cascade');
        });


        Schema::table('user_role', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('user')
                ->onDelete('cascade');

            $table->foreign('role_id')
                ->references('id')
                ->on('role')
                ->onDelete('cascade');
        });

        Schema::table('login_log', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('user')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
}

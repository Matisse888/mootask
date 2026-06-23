<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->comment('项目ID');
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->enum('role', ['owner', 'admin', 'member', 'guest'])->default('member')->comment('角色');
            $table->integer('sort')->default(0)->comment('排序');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['project_id', 'user_id']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('project_users');
    }
}

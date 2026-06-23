<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('项目名称');
            $table->text('desc')->nullable()->comment('项目描述');
            $table->unsignedBigInteger('user_id')->comment('创建者ID');
            $table->unsignedBigInteger('owner_user_id')->comment('所有者ID');
            $table->timestamp('archived_at')->nullable()->comment('归档时间');
            $table->integer('task_count')->default(0)->comment('任务数量');
            $table->integer('member_count')->default(0)->comment('成员数量');
            $table->string('color', 20)->default('#409EFF')->comment('颜色');
            $table->string('icon', 50)->default('folder')->comment('图标');
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('owner_user_id');
            $table->index('archived_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('projects');
    }
}

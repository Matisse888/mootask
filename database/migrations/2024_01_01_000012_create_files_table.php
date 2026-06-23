<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('文件名');
            $table->string('original_name', 255)->comment('原始文件名');
            $table->string('path', 500)->comment('路径');
            $table->string('url', 500)->comment('URL');
            $table->string('mime_type', 100)->comment('MIME类型');
            $table->integer('size')->comment('大小');
            $table->unsignedBigInteger('user_id')->comment('上传者ID');
            $table->unsignedBigInteger('project_id')->default(0)->comment('项目ID');
            $table->unsignedBigInteger('task_id')->default(0)->comment('任务ID');
            $table->unsignedBigInteger('dialog_id')->default(0)->comment('对话ID');
            $table->unsignedBigInteger('message_id')->default(0)->comment('消息ID');
            $table->string('disk', 20)->default('public')->comment('存储磁盘');
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('project_id');
            $table->index('task_id');
            $table->index('dialog_id');
            $table->index('mime_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('files');
    }
}

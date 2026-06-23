<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebSocketDialogMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('web_socket_dialog_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dialog_id')->comment('对话ID');
            $table->unsignedBigInteger('user_id')->comment('发送者ID');
            $table->enum('type', ['text', 'image', 'file', 'audio', 'video', 'system'])->default('text')->comment('类型');
            $table->text('content')->nullable()->comment('内容');
            $table->string('file_url', 500)->nullable()->comment('文件URL');
            $table->string('file_name', 255)->nullable()->comment('文件名');
            $table->integer('file_size')->default(0)->comment('文件大小');
            $table->unsignedBigInteger('reply_id')->default(0)->comment('回复ID');
            $table->boolean('is_recalled')->default(false)->comment('是否撤回');
            $table->timestamp('created_at')->comment('创建时间');

            $table->index('dialog_id');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('web_socket_dialog_messages');
    }
}

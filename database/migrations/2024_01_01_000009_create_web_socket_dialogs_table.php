<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebSocketDialogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('web_socket_dialogs', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['private', 'group'])->comment('类型');
            $table->string('name', 50)->nullable()->comment('名称');
            $table->string('avatar', 500)->nullable()->comment('头像');
            $table->unsignedBigInteger('user_id')->comment('创建者ID');
            $table->unsignedBigInteger('last_msg_id')->nullable()->comment('最后消息ID');
            $table->integer('unread_count')->default(0)->comment('未读数');
            $table->timestamp('last_msg_at')->nullable()->comment('最后消息时间');
            $table->timestamps();

            $table->index('user_id');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('web_socket_dialogs');
    }
}

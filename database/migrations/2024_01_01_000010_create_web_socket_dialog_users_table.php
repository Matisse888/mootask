<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebSocketDialogUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('web_socket_dialog_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dialog_id')->comment('对话ID');
            $table->unsignedBigInteger('user_id')->comment('用户ID');
            $table->enum('role', ['owner', 'admin', 'member'])->default('member')->comment('角色');
            $table->timestamp('last_read_at')->nullable()->comment('最后阅读时间');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['dialog_id', 'user_id']);
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
        Schema::dropIfExists('web_socket_dialog_users');
    }
}

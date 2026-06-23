<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email', 100)->unique()->comment('邮箱');
            $table->string('password')->comment('密码');
            $table->string('name', 50)->comment('昵称');
            $table->string('avatar', 500)->nullable()->comment('头像');
            $table->string('phone', 20)->nullable()->comment('手机号');
            $table->unsignedBigInteger('department_id')->default(0)->comment('部门ID');
            $table->enum('status', ['active', 'inactive', 'banned'])->default('active')->comment('状态');
            $table->timestamp('last_login_at')->nullable()->comment('最后登录时间');
            $table->timestamps();
            $table->softDeletes();

            $table->index('email');
            $table->index('department_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}

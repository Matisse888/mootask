<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->comment('项目ID');
            $table->unsignedBigInteger('column_id')->comment('列ID');
            $table->unsignedBigInteger('parent_id')->default(0)->comment('父任务ID');
            $table->string('name', 200)->comment('任务名称');
            $table->text('desc')->nullable()->comment('任务描述');
            $table->unsignedBigInteger('user_id')->comment('创建者ID');
            $table->unsignedBigInteger('assignee_user_id')->nullable()->comment('指派人ID');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium')->comment('优先级');
            $table->enum('status', ['todo', 'in_progress', 'done', 'cancelled'])->default('todo')->comment('状态');
            $table->enum('type', ['task', 'bug', 'improvement', 'epic'])->default('task')->comment('类型');
            $table->date('start_date')->nullable()->comment('开始日期');
            $table->date('due_date')->nullable()->comment('截止日期');
            $table->decimal('estimated_hours', 8, 2)->default(0)->comment('预估工时');
            $table->decimal('actual_hours', 8, 2)->default(0)->comment('实际工时');
            $table->integer('progress')->default(0)->comment('进度');
            $table->integer('sort')->default(0)->comment('排序');
            $table->integer('sub_task_count')->default(0)->comment('子任务数');
            $table->integer('completed_sub_task_count')->default(0)->comment('已完成子任务数');
            $table->integer('file_count')->default(0)->comment('文件数');
            $table->integer('comment_count')->default(0)->comment('评论数');
            $table->json('labels')->nullable()->comment('标签');
            $table->timestamp('completed_at')->nullable()->comment('完成时间');
            $table->timestamps();
            $table->softDeletes();

            $table->index('project_id');
            $table->index('column_id');
            $table->index('parent_id');
            $table->index('user_id');
            $table->index('assignee_user_id');
            $table->index('status');
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('project_tasks');
    }
}

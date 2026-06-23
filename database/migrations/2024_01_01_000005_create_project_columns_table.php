<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectColumnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_columns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->comment('项目ID');
            $table->string('name', 50)->comment('列名称');
            $table->integer('sort')->default(0)->comment('排序');
            $table->string('color', 20)->default('#909399')->comment('颜色');
            $table->integer('task_count')->default(0)->comment('任务数量');
            $table->timestamps();

            $table->index('project_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('project_columns');
    }
}

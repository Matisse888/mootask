<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserDepartment;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $departments = [
            ['name' => '技术部', 'pid' => 0, 'sort' => 1],
            ['name' => '产品部', 'pid' => 0, 'sort' => 2],
            ['name' => '设计部', 'pid' => 0, 'sort' => 3],
            ['name' => '运营部', 'pid' => 0, 'sort' => 4],
            ['name' => '前端组', 'pid' => 1, 'sort' => 1],
            ['name' => '后端组', 'pid' => 1, 'sort' => 2],
            ['name' => '移动端组', 'pid' => 1, 'sort' => 3],
            ['name' => '测试组', 'pid' => 1, 'sort' => 4],
        ];

        foreach ($departments as $dept) {
            UserDepartment::create($dept);
        }
    }
}

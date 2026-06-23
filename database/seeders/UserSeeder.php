<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 创建管理员账号
        User::create([
            'name' => '管理员',
            'email' => 'admin@mootask.com',
            'password' => Hash::make('admin123'),
            'avatar' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=admin',
            'status' => 'active',
        ]);

        // 创建测试账号
        User::create([
            'name' => '测试用户',
            'email' => 'test@mootask.com',
            'password' => Hash::make('test123'),
            'avatar' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=test',
            'status' => 'active',
        ]);

        // 创建示例用户
        for ($i = 1; $i <= 5; $i++) {
            User::create([
                'name' => "用户$i",
                'email' => "user$i@mootask.com",
                'password' => Hash::make('password'),
                'avatar' => "https://api.dicebear.com/7.x/avataaars/svg?seed=user$i",
                'status' => 'active',
            ]);
        }
    }
}

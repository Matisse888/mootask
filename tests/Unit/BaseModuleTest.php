<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Module\Base;

class BaseModuleTest extends TestCase
{
    /**
     * 测试成功响应
     */
    public function test_ret_success()
    {
        $result = Base::retSuccess('操作成功', ['id' => 1]);

        $this->assertEquals(1, $result['ret']);
        $this->assertEquals('操作成功', $result['msg']);
        $this->assertEquals(['id' => 1], $result['data']);
    }

    /**
     * 测试错误响应
     */
    public function test_ret_error()
    {
        $result = Base::retError('操作失败');

        $this->assertEquals(0, $result['ret']);
        $this->assertEquals('操作失败', $result['msg']);
    }

    /**
     * 测试格式化字节
     */
    public function test_format_bytes()
    {
        $this->assertEquals('1 B', Base::formatBytes(1));
        $this->assertEquals('1 KB', Base::formatBytes(1024));
        $this->assertEquals('1 MB', Base::formatBytes(1048576));
        $this->assertEquals('1 GB', Base::formatBytes(1073741824));
    }

    /**
     * 测试生成唯一 ID
     */
    public function test_generate_unique_id()
    {
        $id1 = Base::generateUniqueId();
        $id2 = Base::generateUniqueId();

        $this->assertNotEquals($id1, $id2);
    }

    /**
     * 测试生成 token
     */
    public function test_generate_token()
    {
        $token = Base::generateToken();

        $this->assertIsString($token);
        $this->assertGreaterThan(32, strlen($token));
    }

    /**
     * 测试掩码字符串
     */
    public function test_mask_string()
    {
        $masked = Base::maskString('13800138000');

        // 前3后4，中间是*
        $this->assertStringContainsString('*', $masked);
        $this->assertStringStartsWith('138', $masked);
        $this->assertStringEndsWith('0000', $masked);
    }

    /**
     * 测试数组转树
     */
    public function test_array_to_tree()
    {
        $list = [
            ['id' => 1, 'pid' => 0, 'name' => 'Root'],
            ['id' => 2, 'pid' => 1, 'name' => 'Child 1'],
            ['id' => 3, 'pid' => 1, 'name' => 'Child 2'],
            ['id' => 4, 'pid' => 2, 'name' => 'Grandchild'],
        ];

        $tree = Base::arrayToTree($list);

        $this->assertCount(1, $tree);
        $this->assertEquals('Root', $tree[0]['name']);
        $this->assertCount(2, $tree[0]['children']);
    }
}

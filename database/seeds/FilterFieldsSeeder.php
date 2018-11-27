<?php

use App\Models\FilterField;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FilterFieldsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::beginTransaction();
        try {
            FilterField::create([
                'table_name' => 'trails',
                'department_id' => '1',
                'code' => 'brand',
                'value' => '品牌名称',
                'type' => '1',
                'operator' => "[['code': 'LIKE', 'value': '等于']]",
                'content' => "",
            ]);
            FilterField::create([
                'table_name' => 'trails',
                'department_id' => '1',
                'code' => 'client.company',
                'value' => '公司名称',
                'type' => '1',
                'operator' => "[['code': 'LIKE', 'value': '等于']]",
                'content' => "",
            ]);
            FilterField::create([
                'table_name' => 'trails',
                'department_id' => '1',
                'code' => 'client.grade',
                'value' => '级别',
                'type' => '2',
                'operator' => "[['code': 'LIKE', 'value': '等于']]",
                'content' => "",
            ]);
            FilterField::create([
                'table_name' => 'trails',
                'department_id' => '1',
                'code' => 'title',
                'value' => '线索名称',
                'type' => '1',
                'operator' => "[['code': 'LIKE', 'value': '等于']]",
                'content' => "",
            ]);
            FilterField::create([
                'table_name' => 'trails',
                'department_id' => '1',
                'code' => 'resource_type',
                'value' => '线索来源',
                'type' => '1',
                'operator' => "[['code': 'LIKE', 'value': '等于']]",
                'content' => "",
            ]);
            FilterField::create([
                'table_name' => 'trails',
                'department_id' => '1',
                'code' => 'type',
                'value' => '线索类型',
                'type' => '1',
                'operator' => "[['code': 'LIKE', 'value': '等于']]",
                'content' => "",
            ]);
            FilterField::create([
                'table_name' => 'trails',
                'department_id' => '1',
                'code' => 'departments.name',
                'value' => '部门',
                'type' => '1',
                'operator' => "[['code': 'LIKE', 'value': '等于']]",
                'content' => "['商务A组','商务B组','商务C组','影视组','综艺组']",
            ]);
            FilterField::create([
                'table_name' => 'trails',
                'department_id' => '1',
                'code' => 'principal_id',
                'value' => '负责人',
                'type' => '1',
                'operator' => "[['code': 'LIKE', 'value': '等于']]",
                'content' => "", // 存接口
            ]);
            FilterField::create([
                'table_name' => 'trails',
                'department_id' => '1',
                'code' => 'exceptions',
                'value' => '目标艺人',
                'type' => '1',
                'operator' => "[['code': 'LIKE', 'value': '等于']]",
                'content' => "", // 存接口
            ]);
            FilterField::create([
                'table_name' => 'trails',
                'department_id' => '1',
                'code' => 'recommendations',
                'value' => '推荐艺人',
                'type' => '1',
                'operator' => "[['code': 'LIKE', 'value': '等于']]",
                'content' => "", // 存接口
            ]);
            FilterField::create([
                'table_name' => 'trails',
                'department_id' => '1',
                'code' => 'contacts.name',
                'value' => '联系人',
                'type' => '1',
                'operator' => "[['code': 'LIKE', 'value': '等于']]",
                'content' => "", // 存接口
            ]);
            FilterField::create([
                'table_name' => 'trails',
                'department_id' => '1',
                'code' => 'contacts.phone',
                'value' => '联系人电话',
                'type' => '1',
                'operator' => "[['code': 'LIKE', 'value': '等于']]",
                'content' => "", // 存接口
            ]);
            FilterField::create([
                'table_name' => 'trails',
                'department_id' => '1',
                'code' => 'progress_status',
                'value' => '销售进展',
                'type' => '1',
                'operator' => "[['code': '=', 'value': '等于']]",
                'content' => "['未确认合作','拒绝','确认合作']", // 存接口
            ]);
            FilterField::create([
                'table_name' => 'trails',
                'department_id' => '1',
                'code' => 'fee',
                'value' => '预计费用',
                'type' => '1',
                'operator' => "[['code': '=', 'value': '等于'],['code': '>=', 'value': '大于等于'],['code': '<', 'value': '小于'],['code': '<=', 'value': '小于等于']]",
                'content' => "", // 存接口
            ]);
            FilterField::create([
                'table_name' => 'trails',
                'department_id' => '1',
                'code' => 'creator_id',
                'value' => '录入人',
                'type' => '1',
                'operator' => "[['code': 'LIKE', 'value': '等于']]",
                'content' => "", // 存接口
            ]);
            FilterField::create([
                'table_name' => 'trails',
                'department_id' => '1',
                'code' => 'created_at',
                'value' => '录入时间',
                'type' => '1',
                'operator' => "[['code': 'LIKE', 'value': '等于']]",
                'content' => "", // 存接口
            ]);
            FilterField::create([
                'table_name' => 'trails',
                'department_id' => '1',
                'code' => 'operate_logs.user_id',
                'value' => '最近更新人',
                'type' => '1',
                'operator' => "[['code': 'LIKE', 'value': '等于']]",
                'content' => "", // 存接口
            ]);
            FilterField::create([
                'table_name' => 'trails',
                'department_id' => '1',
                'code' => 'operate_logs.created_at',
                'value' => '最近更新时间',
                'type' => '1',
                'operator' => "[['code': 'LIKE', 'value': '等于']]",
                'content' => "", // 存接口
            ]);
            FilterField::create([
                'table_name' => 'trails',
                'department_id' => '1',
                'code' => 'department',
                'value' => 'M组',
                'type' => '1',
                'operator' => "[['code': 'LIKE', 'value': '等于']]",
                'content' => "", // 存接口
            ]);
            FilterField::create([
                'table_name' => 'trails',
                'department_id' => '1',
                'code' => '',
                'value' => '经纪人',
                'type' => '1',
                'operator' => "[['code': 'LIKE', 'value': '等于']]",
                'content' => "", // 存接口
            ]);

            $json = '[{"code":"=","value":"等于"},{"code":">","value":"大于"},{"code":">=","value":"大于等于"},{"code":"<","value":"小于"},{"code":"<=","value":"小于等于"}]';
        } catch (Exception $exception) {
            \Illuminate\Support\Facades\Log::error($exception);
            DB::rollBack();
        }
        DB::commit();
    }
}

<?php

use App\Models\TemplateField;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TemplateFieldSeeder extends Seeder
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
            // 影视项目
            TemplateField::create([
                'key' => '艺人组别',
                'field_type' => '6',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '艺人类型',
                'field_type' => '2',
                'content' => '成熟艺人|新艺人',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '剧本评分',
                'field_type' => '1',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '开机时间',
                'field_type' => '6',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '拍摄周期',
                'field_type' => '6',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '拍摄地点',
                'field_type' => '6',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '影视类型',
                'field_type' => '6',
                'content' => '电视剧|网剧|电影',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '影视级别',
                'field_type' => '6',
                'content' => 'S|A|B|C',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '题材',
                'field_type' => '1',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '导演/监制',
                'field_type' => '1',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '播出平台',
                'field_type' => '1',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '编剧',
                'field_type' => '1',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '主演已签',
                'field_type' => '6',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '主演拟邀',
                'field_type' => '6',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '其他主创',
                'field_type' => '1',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '出品方',
                'field_type' => '1',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '提供物料',
                'field_type' => '1',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '是否新线索',
                'field_type' => '2',
                'content' => '是|否',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => 'A评分及推荐',
                'field_type' => '1',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '与M讨论结果',
                'field_type' => '1',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '与GM讨论结果',
                'field_type' => '1',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '签单时间',
                'field_type' => '4',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '选角团队',
                'field_type' => '1',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '跟进情况',
                'field_type' => '1',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '试戏时间',
                'field_type' => '4',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '试戏地点',
                'field_type' => '1',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '试戏反馈',
                'field_type' => '1',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '跟进结果',
                'field_type' => '1',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);

            TemplateField::create([
                'key' => '节目级别',
                'field_type' => '2',
                'content' => 'S|A|B|C',
                'module_type' => '2',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '艺人组别',
                'field_type' => '6',
                'content' => '',
                'module_type' => '2',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '播出平台',
                'field_type' => '1',
                'content' => '',
                'module_type' => '2',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '综艺节目类型',
                'field_type' => '2',
                'content' => '真人秀|晚会',
                'module_type' => '2',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '嘉宾类型',
                'field_type' => '2',
                'content' => '常驻嘉宾|飞行嘉宾|选手|固定嘉宾',
                'module_type' => '2',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '其他参与嘉宾',
                'field_type' => '1',
                'content' => '',
                'module_type' => '2',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '录制时间',
                'field_type' => '8',
                'content' => '',
                'module_type' => '2',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '上线时间',
                'field_type' => '8',
                'content' => '',
                'module_type' => '2',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '签单时间',
                'field_type' => '4',
                'content' => '',
                'module_type' => '2',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '与M的讨论结果',
                'field_type' => '2',
                'content' => '',
                'module_type' => '2',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '结果',
                'field_type' => '2',
                'content' => 'S|A|B|C',
                'module_type' => '2',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '最新进展',
                'field_type' => '2',
                'content' => 'S|A|B|C',
                'module_type' => '2',
                'status' => '1',
                'is_secret' => '0',
            ]);
        } catch (Exception $exception) {
            DB::rollBack();
            return ;
        }
        DB::commit();
    }
}

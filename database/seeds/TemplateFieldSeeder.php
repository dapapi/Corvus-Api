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
                'key' => '艺人类型',
                'field_type' => '2',
                'content' => '成熟艺人|新艺人',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '剧本评分',
                'field_type' => '11',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '开机时间',
                'field_type' => '4',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '开机地点',
                'field_type' => '1',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '拍摄周期',
                'field_type' => '1',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '拍摄地点',
                'field_type' => '1',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '影视类型',
                'field_type' => '2',
                'content' => '电视剧|网剧|电影',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '影视级别',
                'field_type' => '2',
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
                'field_type' => '1',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '主演拟邀',
                'field_type' => '1',
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
                'field_type' => '5',
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
                'field_type' => '5',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '与M讨论结果',
                'field_type' => '5',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '与GM讨论结果',
                'field_type' => '5',
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
                'field_type' => '5',
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
                'field_type' => '5',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '试戏反馈',
                'field_type' => '5',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '跟进结果',
                'field_type' => '5',
                'content' => '',
                'module_type' => '1',
                'status' => '1',
                'is_secret' => '0',
            ]);

            // 综艺项目
            TemplateField::create([
                'key' => '节目级别',
                'field_type' => '2',
                'content' => 'S|A|B|C',
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
                'field_type' => '5',
                'content' => '',
                'module_type' => '2',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '结果',
                'field_type' => '5',
                'content' => '',
                'module_type' => '2',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '最新进展',
                'field_type' => '5',
                'content' => '',
                'module_type' => '2',
                'status' => '1',
                'is_secret' => '0',
            ]);

            // 商务代言
            TemplateField::create([
                'key' => '合作类型',
                'field_type' => '2',
                'content' => '未确定|代言|合作|活动|微博|抖音|短期代言|时装周',
                'module_type' => '3',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '优先级',
                'field_type' => '2',
                'content' => 'S|A|B|C',
                'module_type' => '3',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '状态',
                'field_type' => '2',
                'content' => '开始接洽|主动拒绝|客户拒绝|进入谈判|意向签约|签约中|签约完成|待执行|在执行|已执行|客户回款|客户反馈分析及项目复盘',
                'module_type' => '3',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '应对措施',
                'field_type' => '5',
                'content' => '',
                'module_type' => '3',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '回款记录',
                'field_type' => '5',
                'content' => '',
                'module_type' => '3',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '执行成本/万',
                'field_type' => '5',
                'content' => '',
                'module_type' => '3',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '周期',
                'field_type' => '1',
                'content' => '',
                'module_type' => '3',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => 'title',
                'field_type' => '1',
                'content' => '',
                'module_type' => '3',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '工作量',
                'field_type' => '1',
                'content' => '',
                'module_type' => '3',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '执行状态',
                'field_type' => '5',
                'content' => '',
                'module_type' => '3',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '签单时间',
                'field_type' => '4',
                'content' => '',
                'module_type' => '3',
                'status' => '1',
                'is_secret' => '0',
            ]);

            //papi 项目
            TemplateField::create([
                'key' => '签单时间',
                'field_type' => '4',
                'content' => '',
                'module_type' => '4',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '上线时间',
                'field_type' => '4',
                'content' => '',
                'module_type' => '4',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '投放方式',
                'field_type' => '6',
                'content' => '现下活动|直播|视频定制|图文定制|图文挂稿|视频挂稿',
                'module_type' => '4',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '投放平台',
                'field_type' => '6',
                'content' => '抖音|小红书|B站|全平台',
                'module_type' => '4',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '合约费用(含税)',
                'field_type' => '1',
                'content' => '',
                'module_type' => '4',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '制作费',
                'field_type' => '1',
                'content' => '',
                'module_type' => '4',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '是否与他组合作',
                'field_type' => '2',
                'content' => '是|否',
                'module_type' => '4',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '合作小组',
                'field_type' => '10',
                'content' => '',
                'module_type' => '4',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '合作小组分成',
                'field_type' => '5',
                'content' => '',
                'module_type' => '4',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '奖金额度变动比',
                'field_type' => '5',
                'content' => '',
                'module_type' => '4',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '变动原因',
                'field_type' => '5',
                'content' => '',
                'module_type' => '4',
                'status' => '1',
                'is_secret' => '0',
            ]);
            TemplateField::create([
                'key' => '价格特评',
                'field_type' => '5',
                'content' => '',
                'module_type' => '4',
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

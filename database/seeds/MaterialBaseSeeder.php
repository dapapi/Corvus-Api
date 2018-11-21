<?php

use App\Models\Material;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaterialBaseSeeder extends Seeder
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
            Material::create([
                'name' => '1801会议室',
                'creator_id' => 2
            ]);
            Material::create([
                'name' => '1802会议室',
                'creator_id' => 2
            ]);
            Material::create([
                'name' => '1803会议室',
                'creator_id' => 2
            ]);
            Material::create([
                'name' => '梵石-1号摄影棚',
                'creator_id' => 2
            ]);
            Material::create([
                'name' => '梵石-2号摄影棚',
                'creator_id' => 2
            ]);
            Material::create([
                'name' => '梵石-3号摄影棚',
                'creator_id' => 2
            ]);
            Material::create([
                'name' => '梵石-4号摄影棚',
                'creator_id' => 2
            ]);
            Material::create([
                'name' => '梵石-5号摄影棚',
                'creator_id' => 2
            ]);
            Material::create([
                'name' => '梵石一层-一夜暴富（5人）',
                'creator_id' => 2
            ]);
            Material::create([
                'name' => '梵石一层-十万加（5人）',
                'creator_id' => 2
            ]);
            Material::create([
                'name' => '梵石一层-大爱无边（16人）(投影)',
                'creator_id' => 2
            ]);
            Material::create([
                'name' => '梵石一层-春满人间（8人）(可旋转电视)',
                'creator_id' => 2
            ]);
            Material::create([
                'name' => '梵石一层-樱木花道（5人）',
                'creator_id' => 2
            ]);
            Material::create([
                'name' => '梵石一层-流川枫（5人）',
                'creator_id' => 2
            ]);
            Material::create([
                'name' => '梵石一层-流量爆表（5人）',
                'creator_id' => 2
            ]);
            Material::create([
                'name' => '梵石一层-花好月圆（16人）',
                'creator_id' => 2
            ]);
            Material::create([
                'name' => '梵石一层-赤木刚宪（10人）',
                'creator_id' => 2
            ]);
            Material::create([
                'name' => '梵石一层-阶梯开放区（30人）',
                'creator_id' => 2
            ]);
            Material::create([
                'name' => '梵石三层-简单说两句（20人）（可旋转电视）',
                'creator_id' => 2
            ]);
            Material::create([
                'name' => '梵石三层-那么大会议室（20人）',
                'creator_id' => 2
            ]);
            Material::create([
                'name' => '梵石二层-下班别走（6人）（电视）',
                'creator_id' => 2
            ]);
            Material::create([
                'name' => '梵石二层-保持围笑（6人）',
                'creator_id' => 2
            ]);
            Material::create([
                'name' => '梵石二层-坐有坐相（10人）',
                'creator_id' => 2
            ]);
            Material::create([
                'name' => '梵石二层-多功能接待室（可旋转白板电视）',
                'creator_id' => 2
            ]);
            Material::create([
                'name' => '梵石二层-暗中观察（6人）',
                'creator_id' => 2
            ]);
            Material::create([
                'name' => '梵石二层-请您吃瓜（10人）',
                'creator_id' => 2
            ]);
            Material::create([
                'name' => '诺金26层-自由会议室',
                'creator_id' => 2
            ]);
        } catch (Exception $exception) {
            \Illuminate\Support\Facades\Log::error($exception);
            DB::rollBack();
        }
        DB::commit();
    }
}

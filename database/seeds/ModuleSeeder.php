<?php

use App\Models\Module;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ModuleSeeder extends Seeder
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
            Module::create([
                'name' => '艺人',
                'icon' => '',
                'code' => 'artists',
            ]);
            Module::create([
                'name' => '项目',
                'icon' => '',
                'code' => 'projects',
            ]);
            Module::create([
                'name' => '任务',
                'icon' => '',
                'code' => 'tasks',
            ]);
            Module::create([
                'name' => '销售线索',
                'icon' => '',
                'code' => 'trails',
            ]);
            Module::create([
                'name' => '客户',
                'icon' => '',
                'code' => 'clients',
            ]);
            Module::create([
                'name' => '联系人',
                'icon' => '',
                'code' => 'contacts',
            ]);
            Module::create([
                'name' => '附件',
                'icon' => '',
                'code' => 'affixes',
            ]);
            Module::create([
                'name' => '子任务',
                'icon' => '',
                'code' => 'subtask',
            ]);
        } catch (Exception $exception) {
            Log::error($exception);
            DB::rollBack();
        }
        DB::commit();
    }
}

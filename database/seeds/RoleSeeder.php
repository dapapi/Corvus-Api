<?php

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoleSeeder extends Seeder
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
            Role::create([
                'name' => 'member',
                'display_name' => '成员',
                'desc' => '基础角色',
            ]);
            Role::create([
                'name' => 'admin',
                'display_name' => '管理员',
                'desc' => '管理员',
            ]);
            Role::create([
                'name' => 'producer',
                'display_name' => '制作人',
                'desc' => '制作人',
            ]);
            Role::create([
                'name' => 'agent',
                'display_name' => '经纪人',
                'desc' => '经纪人',
            ]);
            Role::create([
                'name' => 'hr',
                'display_name' => '人事',
                'desc' => '人事',
            ]);
        } catch (Exception $exception) {
            Log::error($exception);
            DB::rollBack();
        }
        DB::commit();

    }
}

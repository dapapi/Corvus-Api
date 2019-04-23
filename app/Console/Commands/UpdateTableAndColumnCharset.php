<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateTableAndColumnCharset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:tableCharset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '修改数据所有变的编码及其字段的编码';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tables = Schema::getConnection()->getDoctrineSchemaManager()->listTableNames();
        foreach ($tables as $table){
            dump("开始修改表:".$table);
            DB::select("alter table `{$table}` convert to character set utf8mb4 collate utf8mb4_unicode_ci");
            dump("表:$table 修改完成");
//            $columns = Schema::getColumnListing($this->option($table));
//            foreach ($columns as $column){
//                DB::select("alter table $")
//            }
        }

    }
}

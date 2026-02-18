<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->timestamp('last_comment_at')->nullable()->after('ip_address')->index();
        });

        // Заполнить last_comment_at для существующих корневых комментариев
        $roots = DB::table('comments')->whereNull('parent_id')->get(['id', 'created_at', '_lft', '_rgt']);

        foreach ($roots as $root) {
            $maxChildDate = DB::table('comments')
                ->where('_lft', '>', $root->_lft)
                ->where('_rgt', '<', $root->_rgt)
                ->whereNull('deleted_at')
                ->max('created_at');

            DB::table('comments')
                ->where('id', $root->id)
                ->update(['last_comment_at' => $maxChildDate ?? $root->created_at]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn('last_comment_at');
        });
    }
};

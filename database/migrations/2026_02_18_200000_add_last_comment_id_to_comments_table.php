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
            $table->unsignedBigInteger('last_comment_id')->nullable()->after('last_comment_at');
        });

        // Заполнить last_comment_id для существующих корневых комментариев
        $roots = DB::table('comments')->whereNull('parent_id')->get(['id', '_lft', '_rgt']);

        foreach ($roots as $root) {
            $lastChild = DB::table('comments')
                ->where('_lft', '>', $root->_lft)
                ->where('_rgt', '<', $root->_rgt)
                ->whereNull('deleted_at')
                ->orderByDesc('created_at')
                ->first(['id']);

            DB::table('comments')
                ->where('id', $root->id)
                ->update(['last_comment_id' => $lastChild ? $lastChild->id : $root->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn('last_comment_id');
        });
    }
};

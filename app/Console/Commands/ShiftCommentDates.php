<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShiftCommentDates extends Command
{
    protected $signature = 'comments:shift-dates';

    protected $description = 'Сдвинуть даты комментариев на -2 месяца (для тех, что позже 2026-02-18 08:00)';

    public function handle(): int
    {
        $cutoff = '2026-02-18 08:00:00';

        $affected = DB::table('comments')
            ->where('created_at', '>', $cutoff)
            ->update([
                'created_at' => DB::raw("DATE_SUB(created_at, INTERVAL 2 MONTH)"),
                'updated_at' => DB::raw("DATE_SUB(updated_at, INTERVAL 2 MONTH)"),
            ]);

        $this->info("Сдвинуто комментариев (created_at, updated_at): {$affected}");

        $affectedLast = DB::table('comments')
            ->where('last_comment_at', '>', $cutoff)
            ->update([
                'last_comment_at' => DB::raw("DATE_SUB(last_comment_at, INTERVAL 2 MONTH)"),
            ]);

        $this->info("Сдвинуто корневых тем (last_comment_at): {$affectedLast}");

        return self::SUCCESS;
    }
}

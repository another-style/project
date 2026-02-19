<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comment_votes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('comment_id');
            $table->string('ip_address', 45);
            $table->tinyInteger('vote'); // 1 = лайк, -1 = дизлайк
            $table->timestamps();

            $table->foreign('comment_id')
                ->references('id')
                ->on('comments')
                ->cascadeOnDelete();

            $table->unique(['comment_id', 'ip_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_votes');
    }
};

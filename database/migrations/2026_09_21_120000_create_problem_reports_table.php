<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('problem_reports', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('reporter_name', 120)->nullable();
            $table->string('contact', 200)->nullable();
            $table->string('category', 40);
            $table->text('description');
            $table->string('page_path', 1500);
            $table->string('screenshot_path')->nullable();
            $table->string('status', 30)->default('new')->index();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('problem_reports'); }
};

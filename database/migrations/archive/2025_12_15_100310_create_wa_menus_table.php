<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
public function up(): void
{
Schema::create('wa_menus', function (Blueprint $table) {
$table->id();
$table->foreignId('parent_id')->nullable()->constrained('wa_menus')->nullOnDelete();
$table->string('key', 5); // 1,2,3, dst
$table->string('title');
$table->text('reply_text')->nullable();
$table->enum('action_type', ['auto_reply', 'ask_input', 'handover']);
$table->boolean('is_active')->default(true);
$table->integer('order')->default(0);
$table->timestamps();
});
}


public function down(): void
{
Schema::dropIfExists('wa_menus');
}
};

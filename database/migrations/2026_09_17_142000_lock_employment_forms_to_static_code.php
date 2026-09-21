<?php

use App\Services\StaticEmploymentFormService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(StaticEmploymentFormService::class)->syncToDatabase();
    }

    public function down(): void
    {
        // Intentionally non-destructive. Existing submissions and answers must remain intact.
    }
};

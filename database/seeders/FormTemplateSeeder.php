<?php

namespace Database\Seeders;

use App\Services\StaticEmploymentFormService;
use Illuminate\Database\Seeder;

class FormTemplateSeeder extends Seeder
{
    public function run(): void
    {
        app(StaticEmploymentFormService::class)->syncToDatabase();
    }
}

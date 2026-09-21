<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            DepartmentSeeder::class,
            PositionSeeder::class,
            FormTemplateSeeder::class,
        ]);

        // Demo applicants are intentionally limited to local/testing environments
        // so production databases are never populated with sample recruitment data.
        if (app()->environment(['local', 'testing'])) {
            $this->call([
                AssessmentInsightsTestSeeder::class,
            ]);
        }
    }
}

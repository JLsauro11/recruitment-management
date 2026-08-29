<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            'Nanomoly  - Business Unit',
            'Boss J  - Corporate Services',
            'Dynamix Performance Blue Corp.',
            'E-Commerce - Business Unit',
            'Finance - Corporate Services',
            'HR & IT - Corporate Services',
            'Motoworkz OPC - Business Unit',
            'Purchasing - Corporate Services',
            'Research and Product Development - Corporate Services',
            'RS8 Taiwan Parts - Business Unit',
            'SRF Worldwide Racing  - Business Unit',
            'Team Redspeed Shop - Corporate Services',
            'Wholesale - Business Unit',
            'Security Guard',
        ];

        foreach ($departments as $name) {
            Department::updateOrCreate(
                ['name' => $name],
                [
                    'description' => null,
                    'status' => 'active',
                ]
            );
        }
    }
}

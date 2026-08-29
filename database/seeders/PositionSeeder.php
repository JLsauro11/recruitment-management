<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Seeder;
use RuntimeException;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * The supplied Excel export contains separate Department and Position
         * lists but no explicit department-to-position relationship. The map
         * below assigns each supplied position to the closest functional
         * department so every position can satisfy the required department_id.
         */
        $positionMap = [
            'FINANCE EXECUTIVE ASSISTANT' => 'Finance - Corporate Services',
            'INVOICER' => 'Finance - Corporate Services',
            'ACCOUNTING ASSOCIATE' => 'Finance - Corporate Services',
            'MESSENGER' => 'Boss J  - Corporate Services',
            'FINANCE ASSISTANT' => 'Finance - Corporate Services',
            'DISBURSEMENT OFFICER' => 'Finance - Corporate Services',
            'SENIOR FINANCE ASSISTANT' => 'Finance - Corporate Services',
            'ADMINISTRATIVE ASSOCIATE' => 'Boss J  - Corporate Services',
            'HUMAN RESOURCE MANAGER' => 'HR & IT - Corporate Services',
            'HR SPECIALIST' => 'HR & IT - Corporate Services',
            'IT SPECIALIST' => 'HR & IT - Corporate Services',
            'PURCHASING MANAGER' => 'Purchasing - Corporate Services',
            'PURCHASING SUPERVISOR' => 'Purchasing - Corporate Services',
            'EXECUTIVE OFFICER' => 'Boss J  - Corporate Services',
            'PRODUCT MANAGER' => 'Research and Product Development - Corporate Services',
            'MECHANIC' => 'Motoworkz OPC - Business Unit',
            'MACHINIST' => 'SRF Worldwide Racing  - Business Unit',
            'SALES & SERVICE CLERK' => 'Team Redspeed Shop - Corporate Services',
            'RESEARCH & PRODUCT DEVELOPMENT MANAGER' => 'Research and Product Development - Corporate Services',
            'CNC MACHINE OPERATOR' => 'SRF Worldwide Racing  - Business Unit',
            'SALES CLERK' => 'Wholesale - Business Unit',
            'PACKER' => 'Wholesale - Business Unit',
            'WAREHOUSE & LOGISTICS MANAGER' => 'RS8 Taiwan Parts - Business Unit',
            'WAREHOUSE SUPERVISOR' => 'RS8 Taiwan Parts - Business Unit',
            'INVENTORY CONTROL OFFICER' => 'RS8 Taiwan Parts - Business Unit',
            'WAREHOUSE ADMINISTRATIVE STAFF' => 'RS8 Taiwan Parts - Business Unit',
            'ONLINE SALES MARKETING MANAGER' => 'E-Commerce - Business Unit',
            'WELDER' => 'SRF Worldwide Racing  - Business Unit',
            'FIBER INSTALLER' => 'Dynamix Performance Blue Corp.',
            'ADMIN ASSISTANT' => 'Boss J  - Corporate Services',
            'EXECUTIVE ASSISTANT' => 'Boss J  - Corporate Services',
        ];

        $departments = Department::query()->get()->keyBy('name');

        foreach ($positionMap as $positionName => $departmentName) {
            $department = $departments->get($departmentName);

            if (! $department) {
                throw new RuntimeException("Department not found for position seeding: {$departmentName}");
            }

            Position::updateOrCreate(
                [
                    'department_id' => $department->id,
                    'name' => $positionName,
                ],
                [
                    'description' => null,
                    'status' => 'active',
                ]
            );
        }
    }
}

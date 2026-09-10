<?php

declare(strict_types=1);

namespace Domain\Projects\Database\Seeders;

use Domain\Projects\Enums\ProjectPriorityEnum;
use Domain\Projects\Enums\ProjectStatusEnum;
use Domain\Projects\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Seed the sample projects, keeping their ids so re-running updates instead of duplicating.
     */
    public function run(): void
    {
        $projects = [
            [
                'id' => 1,
                'client_name' => 'Acme Corporation',
                'project_name' => 'Corporate Website Redesign',
                'description' => "Redesign and modernize the company's corporate website.",
                'status' => ProjectStatusEnum::InProgress,
                'priority' => ProjectPriorityEnum::High,
                'start_date' => '2026-06-01',
                'due_date' => '2026-07-15',
            ],
            [
                'id' => 2,
                'client_name' => 'GreenLeaf Cafe',
                'project_name' => 'Online Ordering System',
                'description' => 'Develop an online ordering platform for customers.',
                'status' => ProjectStatusEnum::Planning,
                'priority' => ProjectPriorityEnum::Medium,
                'start_date' => '2026-06-10',
                'due_date' => '2026-08-01',
            ],
            [
                'id' => 3,
                'client_name' => 'Bright Realty',
                'project_name' => 'Property Listing Portal',
                'description' => 'Build a portal for managing property listings.',
                'status' => ProjectStatusEnum::OnHold,
                'priority' => ProjectPriorityEnum::Medium,
                'start_date' => '2026-05-15',
                'due_date' => '2026-07-30',
            ],
            [
                'id' => 4,
                'client_name' => 'Nova Fitness',
                'project_name' => 'Mobile App MVP',
                'description' => 'Develop the first version of the fitness tracking app.',
                'status' => ProjectStatusEnum::InProgress,
                'priority' => ProjectPriorityEnum::High,
                'start_date' => '2026-06-05',
                'due_date' => '2026-08-20',
            ],
            [
                'id' => 5,
                'client_name' => 'Blue Ocean Travel',
                'project_name' => 'Booking Platform Enhancement',
                'description' => 'Improve search and booking functionalities.',
                'status' => ProjectStatusEnum::Completed,
                'priority' => ProjectPriorityEnum::Medium,
                'start_date' => '2026-04-01',
                'due_date' => '2026-05-30',
            ],
            [
                'id' => 6,
                'client_name' => 'TechVision Solutions',
                'project_name' => 'CRM Dashboard',
                'description' => 'Develop an internal CRM dashboard.',
                'status' => ProjectStatusEnum::Planning,
                'priority' => ProjectPriorityEnum::High,
                'start_date' => '2026-06-15',
                'due_date' => '2026-08-15',
            ],
            [
                'id' => 7,
                'client_name' => 'Urban Living',
                'project_name' => 'Property Management System',
                'description' => 'Create a platform for managing rental properties.',
                'status' => ProjectStatusEnum::InProgress,
                'priority' => ProjectPriorityEnum::Medium,
                'start_date' => '2026-05-20',
                'due_date' => '2026-08-10',
            ],
            [
                'id' => 8,
                'client_name' => 'Elite Events',
                'project_name' => 'Event Registration Portal',
                'description' => 'Develop a registration and ticketing portal.',
                'status' => ProjectStatusEnum::Planning,
                'priority' => ProjectPriorityEnum::Low,
                'start_date' => '2026-06-20',
                'due_date' => '2026-09-01',
            ],
            [
                'id' => 9,
                'client_name' => 'HealthFirst Clinic',
                'project_name' => 'Patient Appointment System',
                'description' => 'Build an appointment scheduling application.',
                'status' => ProjectStatusEnum::Completed,
                'priority' => ProjectPriorityEnum::High,
                'start_date' => '2026-03-01',
                'due_date' => '2026-05-01',
            ],
            [
                'id' => 10,
                'client_name' => 'MarketPro',
                'project_name' => 'Marketing Campaign Dashboard',
                'description' => 'Track and manage digital marketing campaigns.',
                'status' => ProjectStatusEnum::InProgress,
                'priority' => ProjectPriorityEnum::Medium,
                'start_date' => '2026-06-01',
                'due_date' => '2026-07-31',
            ],
            [
                'id' => 11,
                'client_name' => 'Sunrise Education',
                'project_name' => 'Learning Management Portal',
                'description' => 'Develop a portal for students and instructors.',
                'status' => ProjectStatusEnum::Planning,
                'priority' => ProjectPriorityEnum::High,
                'start_date' => '2026-07-01',
                'due_date' => '2026-09-30',
            ],
            [
                'id' => 12,
                'client_name' => 'FreshFarm',
                'project_name' => 'Inventory Management System',
                'description' => 'Track inventory across multiple locations.',
                'status' => ProjectStatusEnum::OnHold,
                'priority' => ProjectPriorityEnum::Low,
                'start_date' => '2026-05-01',
                'due_date' => '2026-08-01',
            ],
        ];

        Project::unguarded(function () use ($projects): void {
            foreach ($projects as $project) {
                // withTrashed: a sample project soft-deleted through the API is found and restored,
                // instead of being re-inserted and colliding with its own id.
                Project::withTrashed()->updateOrCreate(['id' => $project['id']], [...$project, 'deleted_at' => null]);
            }
        });
    }
}

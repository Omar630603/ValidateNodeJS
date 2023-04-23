<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Project::insert([[
            'title' => 'api-experiment',
            'description' => 'This is an API and web project using NodeJS, ExpressJS, and MongoDB. The goal of this project is to try testing API endpoints and Web pages using Jest, Supertest, and Puppeteer.',
            'tech_stack' => json_encode([
                'framework' => 'ExpressJS',
                'language' => 'NodeJS',
                'database' => 'MongoDB',
                'testing' => 'Jest, Supertest, Puppeteer',
            ]),
            'github_url' => 'https://github.com/Omar630603/api-experiment',
            'image' => 'image',
            'created_at' => now(),
            'updated_at' => now(),
        ], [
            'title' => 'auth-experiment',
            'description' => 'This is an API and web project using NodeJS, ExpressJS, and MongoDB. The goal of this project is to try testing API endpoints and Web pages using Jest, Supertest, and Puppeteer.',
            'tech_stack' => json_encode([
                'framework' => 'ExpressJS',
                'language' => 'NodeJS',
                'database' => 'MongoDB',
                'testing' => 'Jest, Supertest, Puppeteer',
            ]),
            'github_url' => 'https://github.com/Omar630603/auth-experiment',
            'image' => 'image',
            'created_at' => now(),
            'updated_at' => now(),
        ]]);


        $project_api_experiment = Project::where('title', 'api-experiment')->first();
        $project_auth_experiment = Project::where('title', 'auth-experiment')->first();

        $project_api_experiment->addMedia(storage_path('app/public/assets/projects/api-experiment.png'))->toMediaCollection('projects', 'public_projects_images');
        $project_auth_experiment->addMedia(storage_path('app/public/assets/projects/auth-experiment.png'))->toMediaCollection('projects', 'public_projects_images');
    }
}

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

        $project_api_experiment->addMedia(storage_path('app/public/assets/projects/api-experiment/images/api-experiment.png'))->toMediaCollection('project_images', 'public_projects_files');
        $project_auth_experiment->addMedia(storage_path('app/public/assets/projects/auth-experiment/images/auth-experiment.png'))->toMediaCollection('project_images', 'public_projects_files');

        $project_api_experiment->addMedia(storage_path('app/public/assets/projects/api-experiment/files/.env'))->toMediaCollection('project_files', 'public_projects_files');
        $project_api_experiment->addMedia(storage_path('app/public/assets/projects/api-experiment/files/package.json'))->toMediaCollection('project_files', 'public_projects_files');

        $project_auth_experiment->addMedia(storage_path('app/public/assets/projects/auth-experiment/files/.env'))->toMediaCollection('project_files', 'public_projects_files');
        $project_auth_experiment->addMedia(storage_path('app/public/assets/projects/auth-experiment/files/package.json'))->toMediaCollection('project_files', 'public_projects_files');

        // tests
        $project_api_experiment->addMedia(storage_path('app/public/assets/projects/api-experiment/tests/api/testA01.test.js'))->toMediaCollection('project_tests_api', 'public_projects_files');
        $project_api_experiment->addMedia(storage_path('app/public/assets/projects/api-experiment/tests/api/testA02.test.js'))->toMediaCollection('project_tests_api', 'public_projects_files');
        $project_api_experiment->addMedia(storage_path('app/public/assets/projects/api-experiment/tests/api/testA03.test.js'))->toMediaCollection('project_tests_api', 'public_projects_files');
        $project_api_experiment->addMedia(storage_path('app/public/assets/projects/api-experiment/tests/api/testA04.test.js'))->toMediaCollection('project_tests_api', 'public_projects_files');
        $project_api_experiment->addMedia(storage_path('app/public/assets/projects/api-experiment/tests/api/testA05.test.js'))->toMediaCollection('project_tests_api', 'public_projects_files');
        $project_api_experiment->addMedia(storage_path('app/public/assets/projects/api-experiment/tests/web/testA01.test.js'))->toMediaCollection('project_tests_web', 'public_projects_files');
        $project_api_experiment->addMedia(storage_path('app/public/assets/projects/api-experiment/tests/web/testA02.test.js'))->toMediaCollection('project_tests_web', 'public_projects_files');
        $project_api_experiment->addMedia(storage_path('app/public/assets/projects/api-experiment/tests/web/testA03.test.js'))->toMediaCollection('project_tests_web', 'public_projects_files');
        $project_api_experiment->addMedia(storage_path('app/public/assets/projects/api-experiment/tests/web/testA04.test.js'))->toMediaCollection('project_tests_web', 'public_projects_files');
        $project_api_experiment->addMedia(storage_path('app/public/assets/projects/api-experiment/tests/web/testA05.test.js'))->toMediaCollection('project_tests_web', 'public_projects_files');

        $project_api_experiment->addMedia(storage_path('app/public/assets/projects/api-experiment/tests/web/images/create-product-page.png'))->toMediaCollection('project_tests_images', 'public_projects_files');
        $project_api_experiment->addMedia(storage_path('app/public/assets/projects/api-experiment/tests/web/images/error-notFound-page.png'))->toMediaCollection('project_tests_images', 'public_projects_files');
        $project_api_experiment->addMedia(storage_path('app/public/assets/projects/api-experiment/tests/web/images/index-page.png'))->toMediaCollection('project_tests_images', 'public_projects_files');
        $project_api_experiment->addMedia(storage_path('app/public/assets/projects/api-experiment/tests/web/images/no-products-found-page.png'))->toMediaCollection('project_tests_images', 'public_projects_files');
        $project_api_experiment->addMedia(storage_path('app/public/assets/projects/api-experiment/tests/web/images/not-found-product-page.png'))->toMediaCollection('project_tests_images', 'public_projects_files');
        $project_api_experiment->addMedia(storage_path('app/public/assets/projects/api-experiment/tests/web/images/product-details-page.png'))->toMediaCollection('project_tests_images', 'public_projects_files');
        $project_api_experiment->addMedia(storage_path('app/public/assets/projects/api-experiment/tests/web/images/products-table-page.png'))->toMediaCollection('project_tests_images', 'public_projects_files');
        $project_api_experiment->addMedia(storage_path('app/public/assets/projects/api-experiment/tests/web/images/update-product-page.png'))->toMediaCollection('project_tests_images', 'public_projects_files');

        $project_auth_experiment->addMedia(storage_path('app/public/assets/projects/auth-experiment/tests/api/testB01.test.js'))->toMediaCollection('project_tests_api', 'public_projects_files');
        $project_auth_experiment->addMedia(storage_path('app/public/assets/projects/auth-experiment/tests/api/testB02.test.js'))->toMediaCollection('project_tests_api', 'public_projects_files');
        $project_auth_experiment->addMedia(storage_path('app/public/assets/projects/auth-experiment/tests/api/testB03.test.js'))->toMediaCollection('project_tests_api', 'public_projects_files');
        $project_auth_experiment->addMedia(storage_path('app/public/assets/projects/auth-experiment/tests/api/testB04.test.js'))->toMediaCollection('project_tests_api', 'public_projects_files');
        $project_auth_experiment->addMedia(storage_path('app/public/assets/projects/auth-experiment/tests/api/testB05.test.js'))->toMediaCollection('project_tests_api', 'public_projects_files');
        $project_auth_experiment->addMedia(storage_path('app/public/assets/projects/auth-experiment/tests/web/testB01.test.js'))->toMediaCollection('project_tests_web', 'public_projects_files');
        $project_auth_experiment->addMedia(storage_path('app/public/assets/projects/auth-experiment/tests/web/testB02.test.js'))->toMediaCollection('project_tests_web', 'public_projects_files');
        $project_auth_experiment->addMedia(storage_path('app/public/assets/projects/auth-experiment/tests/web/testB03.test.js'))->toMediaCollection('project_tests_web', 'public_projects_files');
        $project_auth_experiment->addMedia(storage_path('app/public/assets/projects/auth-experiment/tests/web/testB04.test.js'))->toMediaCollection('project_tests_web', 'public_projects_files');
        $project_auth_experiment->addMedia(storage_path('app/public/assets/projects/auth-experiment/tests/web/testB05.test.js'))->toMediaCollection('project_tests_web', 'public_projects_files');

        $project_auth_experiment->addMedia(storage_path('app/public/assets/projects/auth-experiment/tests/web/images/edit-page.png'))->toMediaCollection('project_tests_images', 'public_projects_files');
        $project_auth_experiment->addMedia(storage_path('app/public/assets/projects/auth-experiment/tests/web/images/edit-password-page.png'))->toMediaCollection('project_tests_images', 'public_projects_files');
        $project_auth_experiment->addMedia(storage_path('app/public/assets/projects/auth-experiment/tests/web/images/error-notFound-page.png'))->toMediaCollection('project_tests_images', 'public_projects_files');
        $project_auth_experiment->addMedia(storage_path('app/public/assets/projects/auth-experiment/tests/web/images/index-page.png'))->toMediaCollection('project_tests_images', 'public_projects_files');
        $project_auth_experiment->addMedia(storage_path('app/public/assets/projects/auth-experiment/tests/web/images/index-page-after-register.png'))->toMediaCollection('project_tests_images', 'public_projects_files');
        $project_auth_experiment->addMedia(storage_path('app/public/assets/projects/auth-experiment/tests/web/images/login-page.png'))->toMediaCollection('project_tests_images', 'public_projects_files');
        $project_auth_experiment->addMedia(storage_path('app/public/assets/projects/auth-experiment/tests/web/images/login-page-with-error.png'))->toMediaCollection('project_tests_images', 'public_projects_files');
        $project_auth_experiment->addMedia(storage_path('app/public/assets/projects/auth-experiment/tests/web/images/profile-page.png'))->toMediaCollection('project_tests_images', 'public_projects_files');
        $project_auth_experiment->addMedia(storage_path('app/public/assets/projects/auth-experiment/tests/web/images/register-page.png'))->toMediaCollection('project_tests_images', 'public_projects_files');
    }
}

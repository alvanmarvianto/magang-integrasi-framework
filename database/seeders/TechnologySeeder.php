<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Technology;

class TechnologySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $technologies = [
            // Vendors
            ['type' => 'vendors', 'name' => 'PT. Praweda Ciptakarsa Informatika'],
            ['type' => 'vendors', 'name' => 'CMA Small Systems AB'],
            ['type' => 'vendors', 'name' => 'PT Murni Solusindo Nusantara'],
            ['type' => 'vendors', 'name' => 'PT. Metrocom Global Solusi'],
            ['type' => 'vendors', 'name' => 'Intellect Design Arena'],
            ['type' => 'vendors', 'name' => 'In-House'],
            ['type' => 'vendors', 'name' => 'Lintasarta'],
            
            // Operating Systems
            ['type' => 'operating_systems', 'name' => 'Oracle Solaris'],
            ['type' => 'operating_systems', 'name' => 'Windows Server'],
            ['type' => 'operating_systems', 'name' => 'RHEL'],
            
            // Databases
            ['type' => 'databases', 'name' => 'Oracle Database'],
            ['type' => 'databases', 'name' => 'Microsoft SQL Server'],
            ['type' => 'databases', 'name' => 'SQL Server'],
            
            // Programming Languages
            ['type' => 'programming_languages', 'name' => 'C++'],
            ['type' => 'programming_languages', 'name' => 'Java'],
            ['type' => 'programming_languages', 'name' => 'ASP.Net'],
            ['type' => 'programming_languages', 'name' => 'C#'],
            ['type' => 'programming_languages', 'name' => '.Net Programming Languages'],
            
            // Third Parties
            ['type' => 'third_parties', 'name' => 'Jasper Studio'],
            ['type' => 'third_parties', 'name' => 'Crystal Reports'],
            ['type' => 'third_parties', 'name' => 'Jasper'],
            ['type' => 'third_parties', 'name' => 'IIS'],
            ['type' => 'third_parties', 'name' => 'Tomcat'],
            ['type' => 'third_parties', 'name' => 'SecureBackBox'],
            ['type' => 'third_parties', 'name' => 'LDAP'],
            ['type' => 'third_parties', 'name' => 'N/A'],
            
            // Middlewares
            ['type' => 'middlewares', 'name' => 'Apache Tomcat'],
            ['type' => 'middlewares', 'name' => 'Web Logic'],
            ['type' => 'middlewares', 'name' => 'IBM WAS ND'],
            ['type' => 'middlewares', 'name' => 'Apache Kafka'],
            ['type' => 'middlewares', 'name' => 'Openshift'],
            ['type' => 'middlewares', 'name' => 'RabbitMQ'],
            ['type' => 'middlewares', 'name' => 'Jboss-EAP'],
            ['type' => 'middlewares', 'name' => 'Kafka'],
            ['type' => 'middlewares', 'name' => 'Oracle Advanced Queuing'],
            ['type' => 'middlewares', 'name' => 'Weblogic'],
            ['type' => 'middlewares', 'name' => 'IIS Server'],
            ['type' => 'middlewares', 'name' => 'N/A'],
            
            // Frameworks
            ['type' => 'frameworks', 'name' => 'JDK'],
            ['type' => 'frameworks', 'name' => '.Net Framework'],
            ['type' => 'frameworks', 'name' => 'R2dbc'],
            ['type' => 'frameworks', 'name' => 'Springboot'],
            ['type' => 'frameworks', 'name' => 'Spring Boot'],
            ['type' => 'frameworks', 'name' => 'Thymeleaf'],
            ['type' => 'frameworks', 'name' => 'Panacea'],
            ['type' => 'frameworks', 'name' => 'ASP.NET Core'],
            ['type' => 'frameworks', 'name' => 'Spring'],
            ['type' => 'frameworks', 'name' => 'dotnet Core'],
            ['type' => 'frameworks', 'name' => 'dotnet MVC'],
            ['type' => 'frameworks', 'name' => 'Kafka'],
            ['type' => 'frameworks', 'name' => 'Angular WASM'],
            ['type' => 'frameworks', 'name' => 'Maven'],
            ['type' => 'frameworks', 'name' => 'Api Gateway'],
            
            // Platforms
            ['type' => 'platforms', 'name' => 'Web Based'],
            ['type' => 'platforms', 'name' => 'STP'],
            ['type' => 'platforms', 'name' => 'Desktop Based'],
            ['type' => 'platforms', 'name' => 'Microservices'],
            ['type' => 'platforms', 'name' => 'Monolithic'],
            ['type' => 'platforms', 'name' => 'Mobile based'],
        ];

        foreach ($technologies as $tech) {
            Technology::firstOrCreate(
                ['type' => $tech['type'], 'name' => $tech['name']],
                $tech
            );
        }
    }
}

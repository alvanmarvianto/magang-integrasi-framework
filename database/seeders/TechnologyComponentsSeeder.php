<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Vendor;
use App\Models\OperatingSystem;
use App\Models\Database;
use App\Models\ProgrammingLanguage;
use App\Models\ThirdParty;
use App\Models\Middleware;
use App\Models\Framework;
use App\Models\Platform;

class TechnologyComponentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed Vendors
        $vendors = [
            ['name' => 'PT. Praweda Ciptakarsa Informatika', 'version' => null],
            ['name' => 'CMA Small Systems AB', 'version' => null],
            ['name' => 'PT Murni Solusindo Nusantara', 'version' => null],
            ['name' => 'PT. Metrocom Global Solusi', 'version' => null],
            ['name' => 'Intellect Design Arena', 'version' => null],
            ['name' => 'In-House', 'version' => null],
            ['name' => 'Lintasarta', 'version' => null],
        ];

        foreach ($vendors as $vendor) {
            Vendor::create($vendor);
        }

        // Seed Operating Systems
        $operatingSystems = [
            ['name' => 'Oracle Solaris', 'version' => '11.4.61.151.2'],
            ['name' => 'Red Hat Enterprise Linux', 'version' => '7.9:GA:server'],
            ['name' => 'Windows Server', 'version' => '2019'],
            ['name' => 'Red Hat Enterprise Linux', 'version' => '8.10'],
            ['name' => 'Windows Server', 'version' => '2016'],
            ['name' => 'Red Hat Enterprise Linux', 'version' => '8.3'],
            ['name' => 'Windows Server', 'version' => '2012'],
            ['name' => 'Linux RHEL', 'version' => '8.6 Kernel 8.9'],
            ['name' => 'Windows Server', 'version' => '2019 Standard'],
        ];

        foreach ($operatingSystems as $os) {
            OperatingSystem::create($os);
        }

        // Seed Databases
        $databases = [
            ['name' => 'Oracle Database', 'version' => '19.22.0.0.0'],
            ['name' => 'Oracle Database', 'version' => '19c Enterprise Edition'],
            ['name' => 'Microsoft SQL Server', 'version' => '2019'],
            ['name' => 'Oracle Database', 'version' => '19c'],
            ['name' => 'SQL Server', 'version' => '2017'],
            ['name' => 'Microsoft SQL Server', 'version' => '2016'],
            ['name' => 'SQL Server', 'version' => null],
        ];

        foreach ($databases as $database) {
            Database::create($database);
        }

        // Seed Programming Languages
        $programmingLanguages = [
            ['name' => 'C++', 'version' => null],
            ['name' => 'Java Programming', 'version' => 'JDK 8'],
            ['name' => 'Java Programming', 'version' => 'JDK 21'],
            ['name' => 'ASP.Net', 'version' => null],
            ['name' => 'Java Programming', 'version' => 'JDK 11'],
            ['name' => 'C#', 'version' => null],
            ['name' => '.Net Programming Languages', 'version' => null],
        ];

        foreach ($programmingLanguages as $language) {
            ProgrammingLanguage::create($language);
        }

        // Seed Third Parties
        $thirdParties = [
            ['name' => 'Jasper Studio', 'version' => '6'],
            ['name' => 'Crystal Reports', 'version' => '9'],
            ['name' => 'Jasper', 'version' => null],
            ['name' => 'IIS', 'version' => 'Internet Information Server'],
            ['name' => 'Tomcat', 'version' => null],
            ['name' => 'SecureBackBox', 'version' => null],
            ['name' => 'LDAP', 'version' => null],
            ['name' => 'N/A', 'version' => null],
        ];

        foreach ($thirdParties as $thirdParty) {
            ThirdParty::create($thirdParty);
        }

        // Seed Middlewares
        $middlewares = [
            ['name' => 'Apache Tomcat', 'version' => '8.0.21'],
            ['name' => 'Web Logic', 'version' => '12.2.1.4'],
            ['name' => 'IBM WAS ND', 'version' => '9.0.5.8'],
            ['name' => 'Apache Kafka', 'version' => null],
            ['name' => 'Openshift', 'version' => null],
            ['name' => 'RabbitMQ', 'version' => null],
            ['name' => 'Jboss-EAP', 'version' => '7.2'],
            ['name' => 'Kafka', 'version' => null],
            ['name' => 'Oracle Advanced Queuing', 'version' => null],
            ['name' => 'Weblogic', 'version' => null],
            ['name' => 'IIS Server', 'version' => null],
            ['name' => 'N/A', 'version' => null],
        ];

        foreach ($middlewares as $middleware) {
            Middleware::create($middleware);
        }

        // Seed Frameworks
        $frameworks = [
            ['name' => 'JDK', 'version' => '1.8.0_261'],
            ['name' => '.Net Framework', 'version' => 'v4.0.30319'],
            ['name' => 'R2dbc', 'version' => null],
            ['name' => 'Springboot', 'version' => '3'],
            ['name' => 'Kafka', 'version' => null],
            ['name' => 'Angular WASM', 'version' => null],
            ['name' => 'Maven', 'version' => null],
            ['name' => 'Api Gateway', 'version' => null],
            ['name' => '.Net Framework', 'version' => '4.5'],
            ['name' => 'Spring Boot', 'version' => null],
            ['name' => 'Springboot', 'version' => '2'],
            ['name' => 'Thymeleaf', 'version' => null],
            ['name' => 'Panacea', 'version' => null],
            ['name' => 'ASP.NET Core', 'version' => null],
            ['name' => '.Net Framework', 'version' => '4.8'],
            ['name' => 'Spring', 'version' => null],
            ['name' => 'dotnet Core', 'version' => null],
            ['name' => 'dotnet MVC', 'version' => null],
        ];

        foreach ($frameworks as $framework) {
            Framework::create($framework);
        }

        // Seed Platforms
        $platforms = [
            ['name' => 'Web Based', 'version' => null],
            ['name' => 'STP', 'version' => null],
            ['name' => 'Desktop Based', 'version' => null],
            ['name' => 'Microservices', 'version' => null],
            ['name' => 'Monolithic', 'version' => null],
            ['name' => 'Mobile based', 'version' => null],
        ];

        foreach ($platforms as $platform) {
            Platform::create($platform);
        }
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\App;

/**
 * Factory for Technology Vendors
 */
class TechnologyVendorFactory extends Factory
{
    public function definition(): array
    {
        $vendors = [
            'PT. Praweda Ciptakarsa Informatika',
            'CMA Small Systems AB',
            'PT Murni Solusindo Nusantara',
            'PT. Metrocom Global Solusi',
            'Intellect Design Arena',
            'In-House',
            'Lintasarta'
        ];

        return [
            'app_id' => App::factory(),
            'name' => $this->faker->randomElement($vendors),
            'version' => $this->faker->optional()->numerify('v#.#.#'),
        ];
    }
}

/**
 * Factory for Technology Operating Systems
 */
class TechnologyOperatingSystemFactory extends Factory
{
    public function definition(): array
    {
        $operatingSystems = [
            'Oracle Solaris',
            'Windows Server',
            'RHEL'
        ];

        return [
            'app_id' => App::factory(),
            'name' => $this->faker->randomElement($operatingSystems),
            'version' => $this->faker->optional()->numerify('#.#'),
        ];
    }
}

/**
 * Factory for Technology Databases
 */
class TechnologyDatabaseFactory extends Factory
{
    public function definition(): array
    {
        $databases = [
            'Oracle Database',
            'Microsoft SQL Server',
            'SQL Server'
        ];

        return [
            'app_id' => App::factory(),
            'name' => $this->faker->randomElement($databases),
            'version' => $this->faker->optional()->numerify('#.#'),
        ];
    }
}

/**
 * Factory for Technology Programming Languages
 */
class TechnologyProgrammingLanguageFactory extends Factory
{
    public function definition(): array
    {
        $languages = [
            'C++',
            'Java',
            'ASP.Net',
            'C#',
            '.Net Programming Languages'
        ];

        return [
            'app_id' => App::factory(),
            'name' => $this->faker->randomElement($languages),
            'version' => $this->faker->optional()->numerify('#.#'),
        ];
    }
}

/**
 * Factory for Technology Third Parties
 */
class TechnologyThirdPartyFactory extends Factory
{
    public function definition(): array
    {
        $thirdParties = [
            'Jasper Studio',
            'Crystal Reports',
            'Jasper',
            'IIS',
            'Tomcat',
            'SecureBackBox',
            'LDAP',
            'N/A'
        ];

        return [
            'app_id' => App::factory(),
            'name' => $this->faker->randomElement($thirdParties),
            'version' => $this->faker->optional()->numerify('#.#'),
        ];
    }
}

/**
 * Factory for Technology Middlewares
 */
class TechnologyMiddlewareFactory extends Factory
{
    public function definition(): array
    {
        $middlewares = [
            'Apache Tomcat',
            'Web Logic',
            'IBM WAS ND',
            'Apache Kafka',
            'Openshift',
            'RabbitMQ',
            'Jboss-EAP',
            'Kafka',
            'Oracle Advanced Queuing',
            'Weblogic',
            'IIS Server',
            'N/A'
        ];

        return [
            'app_id' => App::factory(),
            'name' => $this->faker->randomElement($middlewares),
            'version' => $this->faker->optional()->numerify('#.#'),
        ];
    }
}

/**
 * Factory for Technology Frameworks
 */
class TechnologyFrameworkFactory extends Factory
{
    public function definition(): array
    {
        $frameworks = [
            'JDK',
            '.Net Framework',
            'R2dbc',
            'Springboot',
            'Spring Boot',
            'Thymeleaf',
            'Panacea',
            'ASP.NET Core',
            'Spring',
            'dotnet Core',
            'dotnet MVC',
            'Kafka',
            'Angular WASM',
            'Maven',
            'Api Gateway'
        ];

        return [
            'app_id' => App::factory(),
            'name' => $this->faker->randomElement($frameworks),
            'version' => $this->faker->optional()->numerify('#.#'),
        ];
    }
}

/**
 * Factory for Technology Platforms
 */
class TechnologyPlatformFactory extends Factory
{
    public function definition(): array
    {
        $platforms = [
            'Web Based',
            'STP',
            'Desktop Based',
            'Microservices',
            'Monolithic',
            'Mobile based'
        ];

        return [
            'app_id' => App::factory(),
            'name' => $this->faker->randomElement($platforms),
            'version' => $this->faker->optional()->numerify('#.#'),
        ];
    }
}

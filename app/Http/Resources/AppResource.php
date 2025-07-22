<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'app_id' => $this->app_id,
            'app_name' => $this->app_name,
            'description' => $this->description,
            'stream_id' => $this->stream_id,
            'app_type' => $this->app_type,
            'stratification' => $this->stratification,
            'vendors' => $this->vendors->map(fn($vendor) => [
                'name' => $vendor->name,
                'version' => $vendor->version,
            ])->all(),
            'operating_systems' => $this->operatingSystems->map(fn($os) => [
                'name' => $os->name,
                'version' => $os->version,
            ])->all(),
            'databases' => $this->databases->map(fn($db) => [
                'name' => $db->name,
                'version' => $db->version,
            ])->all(),
            'programming_languages' => $this->programmingLanguages->map(fn($lang) => [
                'name' => $lang->name,
                'version' => $lang->version,
            ])->all(),
            'frameworks' => $this->frameworks->map(fn($framework) => [
                'name' => $framework->name,
                'version' => $framework->version,
            ])->all(),
            'middlewares' => $this->middlewares->map(fn($middleware) => [
                'name' => $middleware->name,
                'version' => $middleware->version,
            ])->all(),
            'third_parties' => $this->thirdParties->map(fn($thirdParty) => [
                'name' => $thirdParty->name,
                'version' => $thirdParty->version,
            ])->all(),
            'platforms' => $this->platforms->map(fn($platform) => [
                'name' => $platform->name,
                'version' => $platform->version,
            ])->all(),
        ];
    }
} 
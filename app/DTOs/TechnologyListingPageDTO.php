<?php

namespace App\DTOs;

readonly class TechnologyListingPageDTO
{
    public function __construct(
        public array $apps,
        public string $technologyType,
        public string $technologyName,
        public string $pageTitle,
        public string $icon
    ) {}

    public static function create(
        array $apps,
        string $technologyType,
        string $technologyName,
        string $icon
    ): self {
        return new self(
            apps: $apps,
            technologyType: $technologyType,
            technologyName: $technologyName,
            pageTitle: strtoupper(str_replace('_', ' ', $technologyName)),
            icon: $icon
        );
    }

    public function toArray(): array
    {
        return [
            'apps' => $this->apps,
            'technologyType' => $this->technologyType,
            'technologyName' => $this->technologyName,
            'pageTitle' => $this->pageTitle,
            'icon' => $this->icon,
        ];
    }
}

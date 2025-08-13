<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller as BaseController;
use App\Models\Stream;
use Inertia\Inertia;
use Inertia\Response;

class Controller extends BaseController
{
    public function index(): Response
    {
        // Get the first allowed stream by priority (sort_order)
        $firstAllowedStream = Stream::allowedForDiagram()
            ->orderedByPriority()
            ->value('stream_name');

        return Inertia::render('Admin/Index', [
            'firstAllowedStream' => $firstAllowedStream,
        ]);
    }
}

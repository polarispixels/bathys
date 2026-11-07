<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Resources\BookResource;
use Bathys\Application\ListShelf;
use Inertia\Inertia;
use Inertia\Response;

final class ShelfController
{
    public function __invoke(ListShelf $listShelf): Response
    {
        return Inertia::render('shelf', [
            'books' => ['data' => BookResource::collection($listShelf->execute())->resolve()],
        ]);
    }
}

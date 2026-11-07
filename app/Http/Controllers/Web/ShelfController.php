<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Resources\BookResource;
use Bathys\Application\ListShelf;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A driving adapter: it calls a use case and hands the result to the resource.
 * It never sees a repository or a model, and an architecture test enforces that.
 */
final class ShelfController
{
    public function __invoke(ListShelf $listShelf): Response
    {
        return Inertia::render('shelf', [
            'books' => ['data' => BookResource::collection($listShelf->execute())->resolve()],
        ]);
    }
}

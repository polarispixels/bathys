<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Bathys\Domain\Book\Author;
use Bathys\Domain\Book\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One class serializes a book for both the web UI and the API (ADR-0008), so
 * the shape in contracts/openapi.yaml covers every path a book takes out.
 *
 * @property Book $resource
 */
final class BookResource extends JsonResource
{
    /**
     * @return array{type: string, id: string, attributes: array{title: string, authors: list<string>, status: string, addedAt: string}}
     */
    public function toArray(Request $request): array
    {
        $book = $this->resource;

        return [
            'type' => 'books',
            'id' => $book->id()->toString(),
            'attributes' => [
                'title' => $book->title()->toString(),
                'authors' => array_map(fn (Author $author): string => $author->toString(), $book->authors()),
                'status' => $book->status()->value,
                'addedAt' => $book->addedAt()->format(DATE_RFC3339),
            ],
        ];
    }
}

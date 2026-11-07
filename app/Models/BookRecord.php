<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * The database row for a book. This is persistence only: the real Book, with
 * its rules, lives in core/. The suffix "Record" keeps the two apart, and only
 * the persistence adapter, factories, and seeders may touch this class (an
 * architecture test checks).
 *
 * @property string $id
 * @property string $title
 * @property list<string> $authors
 * @property string $status
 * @property \DateTimeImmutable $added_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class BookRecord extends Model
{
    protected $table = 'books';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'title', 'authors', 'status', 'added_at'];

    // Writing the UTC offset ("+00:00") with every timestamp keeps the stored
    // instant independent of the database session's time zone.
    protected $dateFormat = 'Y-m-d H:i:sP';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'authors' => 'array',
            'added_at' => 'immutable_datetime',
        ];
    }
}

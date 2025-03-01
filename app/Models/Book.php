<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Builder;

class Book extends Model
{
    use HasFactory;

    public function reviews() {
        return $this->hasMany(Review::class);
    }

    public function scopeTitleLike(Builder $query, string $title): Builder {
        return $query->where('title', 'LIKE', '%'.$title.'%');
    }

    public function scopeWithReviewsCount(Builder $query, $from = null, $to = null): Builder {
        return $query->withCount([
            'reviews' => fn (Builder $q) => $this->dateRangeFilter($q, $from, $to)
        ]);
    }

    public function scopeWithAvgRating(Builder $query, $from = null, $to = null): Builder {
        return $query->withAvg([
            'reviews' => fn (Builder $q) => $this->dateRangeFilter($q, $from, $to)
        ], 'rating');
    }

    /**
     * Order books by the highest number of reviews
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return Builder
     */
    public function scopePopular(Builder $query, $from = null, $to = null): Builder {
        return $query->withReviewsCount()
                    ->orderBy('reviews_count', 'DESC');
    }

    /**
     * Order books by the highest ratings
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return Builder
     */
    public function scopeHighestRated(Builder $query, $from = null, $to = null): Builder {
        return $query->withAvgRating()
                    ->orderBy('reviews_avg_rating', 'DESC');
    }

    public function scopeMinReviews(Builder $query, int $minReviews) : Builder {
        return $query->having('reviews_count', '>=', $minReviews);
    }
    

    private function dateRangeFilter(Builder $query, $from = null, $to = null) {
        if($from && !$to) {
            $query->where('created_at', '>=', $from);
        } elseif(!$from && $to) {
            $query->where('created_at', '<=', $to);
        } elseif($from && $to) {
            $query->whereBetween('created_at', [$from, $to]);
        }
    }

    public function scopePopularLastMonth(Builder $query): Builder {
        return $query
                ->popular(now()->subMonth(), now())
                ->highestRated(now()->subMonth(), now())
                ->minReviews(2);
    }

    public function scopePopularLastSixMonths(Builder $query): Builder {
        return $query
                ->popular(now()->subMonths(6), now())
                ->highestRated(now()->subMonths(6), now())
                ->minReviews(5);
    }

    public function scopeHihestRatedLastMonth(Builder $query): Builder {
        return $query
                ->highestRated(now()->subMonth(), now())
                ->popular(now()->subMonth(), now())
                ->minReviews(2);
    }

    public function scopeHihestRatedLastSixMonths(Builder $query): Builder {
        return $query
                ->highestRated(now()->subMonths(6), now())
                ->popular(now()->subMonths(6), now())
                ->minReviews(5);
    }

    protected static function booted() {
        static::updated(fn(Book $book) => cache()->forget("book:$book->id"));
        static::deleted(fn(Book $book) => cache()->forget("book:$book->id"));
    }
}

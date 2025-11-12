<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait CommonQueryScopes
{
    /**
     * Filter by date range
     *
     * @param Builder $query
     * @param string $startDate (YYYY-MM-DD)
     * @param string $endDate (YYYY-MM-DD)
     * @param string $column Date column name (default: 'created_at')
     * @return Builder
     */
    public function scopeFilterByDate(Builder $query, string $startDate, string $endDate, string $column = 'created_at'): Builder
    {
        return $query->whereDate($column, '>=', $startDate)
            ->whereDate($column, '<=', $endDate);
    }

    /**
     * Filter by single date
     *
     * @param Builder $query
     * @param string $date (YYYY-MM-DD)
     * @param string $column Date column name (default: 'created_at')
     * @return Builder
     */
    public function scopeFilterByExactDate(Builder $query, string $date, string $column = 'created_at'): Builder
    {
        return $query->whereDate($column, $date);
    }

    /**
     * Filter by date from today onwards
     *
     * @param Builder $query
     * @param string $column Date column name (default: 'created_at')
     * @return Builder
     */
    public function scopeFilterFromToday(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->whereDate($column, '>=', now()->format('Y-m-d'));
    }

    /**
     * Search by title
     *
     * @param Builder $query
     * @param string $searchTerm
     * @param array $columns Columns to search in (default: ['title'])
     * @return Builder
     */
    public function scopeSearchByTitle(Builder $query, string $searchTerm, array $columns = ['title']): Builder
    {
        return $query->where(function ($q) use ($searchTerm, $columns) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', "%{$searchTerm}%");
            }
        });
    }

    /**
     * Search by multiple fields
     *
     * @param Builder $query
     * @param string $searchTerm
     * @param array $columns
     * @return Builder
     */
    public function scopeSearch(Builder $query, string $searchTerm, array $columns): Builder
    {
        return $query->where(function ($q) use ($searchTerm, $columns) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', "%{$searchTerm}%");
            }
        });
    }

    /**
     * Filter by status
     *
     * @param Builder $query
     * @param string|array $status
     * @return Builder
     */
    public function scopeFilterByStatus(Builder $query, $status): Builder
    {
        if (is_array($status)) {
            return $query->whereIn('status', $status);
        }

        return $query->where('status', $status);
    }

    /**
     * Order by latest
     *
     * @param Builder $query
     * @param string $column (default: 'created_at')
     * @return Builder
     */
    public function scopeLatest(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->orderBy($column, 'desc');
    }

    /**
     * Order by oldest
     *
     * @param Builder $query
     * @param string $column (default: 'created_at')
     * @return Builder
     */
    public function scopeOldest(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->orderBy($column, 'asc');
    }
}

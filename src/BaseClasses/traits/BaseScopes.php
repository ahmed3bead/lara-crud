<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

trait BaseScopes
{

    public function scopeCreatedFrom(Builder $query, $date): Builder
    {
        return $query->where(
            'created_at',
            '>=',
            \Carbon\Carbon::parse($date . ' 00:00:00')
        );
    }

    public function scopeIsParent(Builder $query, $date): Builder
    {
        return $query->whereNull('parent_id');
    }
    // Local Scope for Active Users
    public function scopeIsActive(Builder $query,$is_active)
    {
        return $query->where('is_active', $is_active);
    }

    public function scopeCreatedTo(Builder $query, $date): Builder
    {
        return $query->where(
            'created_at',
            '<=',
            Carbon::parse($date . ' 23:59:59')
        );
    }
    public function scopeByStatus(Builder $query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOnDate(Builder $query, $date)
    {
        return $query->whereDate('created_at', $date)->orWhereNull('created_at');
    }

    public function scopeBetweenDates(Builder $query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeByYear(Builder $query, $year)
    {
        return $query->whereYear('created_at', $year);
    }

    public function scopeByMonth(Builder $query, $month, $year = null)
    {
        return $query->whereMonth('created_at', $month)
            ->when($year, function ($query) use ($year) {
                return $query->whereYear('created_at', $year);
            });
    }

    public function scopeByDay(Builder $query, $day, $month = null, $year = null)
    {
        return $query->whereDay('created_at', $day)
            ->when($month, function ($query) use ($month) {
                return $query->whereMonth('created_at', $month);
            })
            ->when($year, function ($query) use ($year) {
                return $query->whereYear('created_at', $year);
            });
    }

    public function scopeLastNDays(Builder $query, $days)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeUpcoming(Builder $query, $field = 'created_at')
    {
        return $query->whereDate($field, '>', now());
    }

    public function scopeOnlyTrashed(Builder $query)
    {
        return $query->onlyTrashed();
    }

    public function scopeWithTrashed(Builder $query)
    {
        return $query->withTrashed();
    }

    public function scopeWithoutTrashed(Builder $query)
    {
        return $query->withoutTrashed();
    }

    public function scopeCreatedToday(Builder $query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeUpdatedThisWeek(Builder $query)
    {
        return $query->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }
    public function scopeByYearAndMonth(Builder $query, $year, $month)
    {
        return $query->whereYear('created_at', $year)->whereMonth('created_at', $month);
    }

    public function scopeLastNWeeks(Builder $query, $weeks)
    {
        return $query->where('created_at', '>=', now()->subWeeks($weeks));
    }

    public function scopeLastNMonths(Builder $query, $months)
    {
        return $query->where('created_at', '>=', now()->subMonths($months));
    }

    public function scopeLastNYears(Builder $query, $years)
    {
        return $query->where('created_at', '>=', now()->subYears($years));
    }
    public function scopeOwnedByUser(Builder $query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOwnedByAuthUser(Builder $query)
    {
        return $query->where('user_id', auth()->id());
    }
    public function scopeActiveAndRecent(Builder $query)
    {
        return $query->active(true)->lastNDays(7);
    }
    public function scopePaginateDefault(Builder $query, $perPage = 15)
    {
        return $query->paginate($perPage);
    }








}

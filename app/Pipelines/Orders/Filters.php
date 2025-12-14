<?php


namespace App\Pipelines\Orders;

use Closure;
use Illuminate\Database\Eloquent\Builder;

class Filters
{
    public function handle(Builder $builder, Closure $next)
    {
        if (request()->has('date_from')) {
            $builder->whereDate('created_at', '>=', request('date_from'));
        }
        if (request()->has('date_to')) {
            $builder->whereDate('created_at', '<=', request('date_to'));
        }
        if (request()->has('status')) {
            $builder->where('status', request('status'));
        }
        if (request()->has('min_total')) {
            $builder->where('total_amount', '>=', request('min_total'));
        }

        return $next($builder);
    }
}

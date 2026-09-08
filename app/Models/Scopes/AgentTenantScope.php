<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\FreelanceSales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class AgentTenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->check() && (auth()->user()->hasRole('freelance_sales') || auth()->user()->freelance_sales_id !== null)) {
            $freelanceSalesId = auth()->user()->freelance_sales_id
                ?? FreelanceSales::where('user_id', auth()->id())->value('id');

            if ($freelanceSalesId) {
                $builder->where($model->getTable().'.freelance_sales_id', $freelanceSalesId);
            } else {
                // Agent role with no associated profile is blocked from accessing data
                $builder->whereRaw('1 = 0');
            }
        }
    }
}

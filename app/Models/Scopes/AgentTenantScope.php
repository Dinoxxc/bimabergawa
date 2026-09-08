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
        if (! auth()->check()) {
            return;
        }

        $user = auth()->user();

        // Staf internal memiliki akses penuh tanpa pembatasan tenant
        if ($user->hasRole('super_admin') || $user->hasRole('sales_pic') || $user->hasRole('finance') || $user->hasRole('logistics')) {
            return;
        }

        // Penegakan isolasi tenant untuk mitra agen freelance
        if ($user->hasRole('freelance_sales') || $user->freelance_sales_id !== null) {
            $freelanceSalesId = $user->freelance_sales_id
                ?? FreelanceSales::where('user_id', $user->id)->value('id');

            if ($freelanceSalesId) {
                $builder->where($model->getTable().'.freelance_sales_id', $freelanceSalesId);
            } else {
                // Agent role with no associated profile is blocked from accessing data
                $builder->whereRaw('1 = 0');
            }
        }
    }
}

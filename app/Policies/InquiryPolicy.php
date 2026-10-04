<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Inquiry;
use App\Models\User;

class InquiryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('super_admin') || $user->can('view_inquiries')) {
            return true;
        }

        // Freelance sales can view inquiries in their isolated portal
        if ($user->hasRole('freelance_sales') && $user->can('access_mitra_portal')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Inquiry $inquiry): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Logistics is strictly forbidden
        if ($user->hasRole('logistics')) {
            return false;
        }

        // Finance can view any inquiry
        if ($user->hasRole('finance')) {
            return true;
        }

        // Sales PIC can view
        if ($user->hasRole('sales_pic') && $user->can('view_inquiries')) {
            return true;
        }

        // Freelance sales can only view their own inquiries (Anti-IDOR)
        if ($user->hasRole('freelance_sales')) {
            return $inquiry->freelance_sales_id !== null
                && (int) $inquiry->freelance_sales_id === (int) $user->freelance_sales_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('super_admin') || $user->hasRole('sales_pic') || $user->hasRole('freelance_sales')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     * TC-ERP-CRM-011 & TC-ERP-CRM-013: Sales isolation
     */
    public function update(User $user, Inquiry $inquiry): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Logistics and Finance cannot modify inquiries
        if ($user->hasRole('logistics') || $user->hasRole('finance')) {
            return false;
        }

        // TC-ERP-CRM-011: Sales PIC cannot edit inquiry assigned to another sales
        if ($user->hasRole('sales_pic')) {
            if ($inquiry->assigned_user_id === null) {
                return true; // Unassigned lead can be picked up
            }

            return (int) $inquiry->assigned_user_id === (int) $user->id;
        }

        // Freelance sales cannot edit inquiry once submitted to ERP
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Inquiry $inquiry): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Inquiry $inquiry): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Inquiry $inquiry): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can assign sales PIC.
     */
    public function assignPic(User $user, Inquiry $inquiry): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->hasRole('sales_pic') && $user->can('assign_inquiries');
    }
}

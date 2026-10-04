<?php

declare(strict_types=1);

namespace App\Models;

/**
 * AgentProfile represents the agent profile model for freelance sales partners,
 * mapping to the freelance_sales table in accordance with UU PDP No. 27/2022.
 */
class AgentProfile extends FreelanceSales
{
    protected $table = 'freelance_sales';
}

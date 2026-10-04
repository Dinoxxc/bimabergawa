<?php

declare(strict_types=1);

namespace App\Filament\Agent\Pages\Auth;

use App\Http\Controllers\AgentRegistrationController;
use Filament\Pages\Page;
use Illuminate\Http\Request;

class AgentRegistration extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-plus';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'auth.agent-register';

    public function register(Request $request)
    {
        return app(AgentRegistrationController::class)->register($request);
    }
}

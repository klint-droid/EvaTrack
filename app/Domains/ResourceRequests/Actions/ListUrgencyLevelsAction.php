<?php

namespace App\Domains\ResourceRequests\Actions;

use App\Domains\Notifications\Models\UrgencyLevel;
use Illuminate\Database\Eloquent\Collection;

class ListUrgencyLevelsAction
{
    public function execute(): Collection
    {
        return UrgencyLevel::orderBy('urgency_label')->get();
    }
}

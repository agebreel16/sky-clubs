<?php

namespace App\Livewire\AgentPortal;

class AgentOpportunities extends AgentPortalPage
{
    public function render()
    {
        $opportunities = $this->agent->opportunities()->with('club')->get();

        $maintenanceThisMonth = $this->agent->opportunities()
            ->where('type', 'maintenance')
            ->whereYear('earned_date', now()->year)
            ->whereMonth('earned_date', now()->month)
            ->exists();

        return $this->renderWithLayout('livewire.agent-portal.opportunities', [
            'grouped'              => $opportunities->groupBy('club_id'),
            'total'                => $opportunities->count(),
            'maintenanceThisMonth' => $maintenanceThisMonth,
            'club'                 => $this->agent->club,
        ]);
    }
}

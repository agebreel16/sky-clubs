<?php

namespace App\Livewire\AgentPortal;

class AgentHistory extends AgentPortalPage
{
    public string $filter = 'all';

    public function render()
    {
        $query = $this->agent->historyLogs()->with(['fromClub', 'toClub']);

        if ($this->filter !== 'all') {
            $query->where('event_type', $this->filter);
        }

        $logs = $query->orderByDesc('event_timestamp')->get();

        return $this->renderWithLayout('livewire.agent-portal.history', compact('logs'));
    }
}

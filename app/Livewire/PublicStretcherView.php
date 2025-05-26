<?php
// app/Livewire/PublicStretcherView.php

namespace App\Livewire;

use App\Models\MyStretcher;
use Livewire\Component;
use Carbon\Carbon;

class PublicStretcherView extends Component
{
    public $stretcherRequests;
    public $refreshInterval = 30; // seconds

    protected $listeners = [
        'echo:stretcher-updates,StretcherUpdated' => 'handleStretcherUpdate'
    ];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->stretcherRequests = MyStretcher::today()
            ->orderBy('stretcher_register_id', 'DESC')
            ->get()
            ->groupBy('stretcher_work_status_name');
    }

    public function handleStretcherUpdate($event)
    {
        $this->loadData();
    }

    public function getTotalRequestsProperty()
    {
        return MyStretcher::today()->count();
    }

    public function getCompletedRequestsProperty()
    {
        return MyStretcher::today()->where('stretcher_work_status_id', 4)->count();
    }

    public function getPendingRequestsProperty()
    {
        return MyStretcher::today()->whereIn('stretcher_work_status_id', [1, 2, 3])->count();
    }

    public function render()
    {
        return view('livewire.public-stretcher-view');
    }
}
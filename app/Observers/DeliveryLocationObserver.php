<?php

namespace App\Observers;

use App\Models\Buy;

class DeliveryLocationObserver
{
    /**
     * Handle the Buy "created" event.
     */
    public function created(Buy $buy): void
    {
        //
    }

    /**
     * Handle the Buy "updated" event.
     */
    public function updated(Buy $buy): void
    {
        //
    }

    /**
     * Handle the Buy "deleted" event.
     */
    public function deleted(Buy $buy): void
    {
        //
    }

    /**
     * Handle the Buy "restored" event.
     */
    public function restored(Buy $buy): void
    {
        //
    }

    /**
     * Handle the Buy "force deleted" event.
     */
    public function forceDeleted(Buy $buy): void
    {
        //
    }
}

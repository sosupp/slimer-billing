<?php
namespace Sosupp\SlimerBilling\Listeners;

use Illuminate\Support\Facades\Log;

class BillingEventListener
{
    public function handleBillingCreated(BillingCreated $event)
    {
        $billing = $event->billing;
        
        // Send notification to the billable entity
        if ($billing->billable && method_exists($billing->billable, 'notify')) {
            $billing->billable->notify(new BillingNotification($billing));
        }
        
        // Log the event
        Log::info('Billing created: ' . $billing->billing_number);
    }
    
    public function handleBillingPaid(BillingPaid $event)
    {
        $billing = $event->billing;
        
        // Update related records
        // Send confirmation email
        // Update accounting system
    }
}
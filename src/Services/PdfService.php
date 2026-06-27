<?php
namespace YourVendor\SchoolFeeBilling\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Sosupp\SlimerBilling\Models\Billing;

class PdfService
{
    /**
     * Generate PDF for billing.
     */
    public function generateBillingPdf(Billing $billing)
    {
        $config = config('slimerbilling.pdf');
        
        $pdf = Pdf::loadView('slimerbilling::billings.pdf', compact('billing'));
        $pdf->setPaper($config['paper_size'] ?? 'A4', $config['orientation'] ?? 'portrait');
        
        return $pdf;
    }

    /**
     * Generate bulk PDF for multiple billings.
     */
    public function generateBulkPdf(array $billingIds)
    {
        $billings = Billing::with(['class', 'items', 'creator'])
            ->whereIn('id', $billingIds)
            ->get();

        $pdf = Pdf::loadView('slimerbilling::billings.bulk-pdf', compact('billings'));
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf;
    }
}
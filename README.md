# Introduction

# Installation

# Using It in Your App

## Querying Polymorphic Billings
1. Get all billings for a specific entity type
$studentBillings = Billing::billableType(Student::class)->get();

2. Get all overdue billings
$overdueBillings = Billing::overdue()->get();

3. Get billings for a specific student
$student = Student::find(1);
$studentBillings = $student->billings;

4. Get all billings with their items and payments
$billings = Billing::with(['items', 'payments', 'billable'])->get();

5. Get billings by date range and status
$billings = Billing::dateRange('2026-01-01', '2026-12-31')
    ->status('paid')
    ->get();
<?php

namespace Sosupp\SlimerBilling\Traits;

use Illuminate\Database\Eloquent\Relations\MorphTo;

trait BillingRelations
{
    /**
     * Get the billable entity.
     */
    public function billable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the billable entity with a specific type.
     */
    public function getBillableEntity(string $type = null)
    {
        if ($type) {
            return $this->billable()->where('billable_type', $type)->first();
        }
        
        return $this->billable;
    }

    /**
     * Check if billable entity is a specific type.
     */
    public function isBillableType(string $type): bool
    {
        return $this->billable_type === $type;
    }

    /**
     * Get billable model class.
     */
    public function getBillableClass(): string
    {
        return $this->billable_type;
    }

    /**
     * Get billable ID.
     */
    public function getBillableId(): int
    {
        return $this->billable_id;
    }

    /**
     * Get a summary of the billable entity.
     */
    public function getBillableSummary(): array
    {
        $entity = $this->billable;
        
        if (!$entity) {
            return [
                'type' => $this->billable_type,
                'id' => $this->billable_id,
                'name' => 'Deleted Entity',
            ];
        }

        return [
            'type' => class_basename($entity),
            'id' => $entity->getKey(),
            'name' => method_exists($entity, 'getNameAttribute') 
                ? $entity->name 
                : (method_exists($entity, 'getFullNameAttribute') 
                    ? $entity->full_name 
                    : $entity->getKey()),
        ];
    }
}
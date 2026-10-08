<?php

namespace App\Http\Resources\V1;

use App\Enums\Palette;
use App\Enums\TicketState;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A category, priority or status: the reference data a ticket points at.
 */
class LookupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $slug = $this->resource->slug ?? null;
        $color = $this->resource->color ?? null;

        return array_filter([
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'slug' => $slug instanceof TicketState ? $slug->value : $slug,
            'color' => $color instanceof Palette ? $color->value : $color,
            'description' => $this->resource->description ?? null,
            'response_hours' => $this->resource->response_hours ?? null,
            'resolution_hours' => $this->resource->resolution_hours ?? null,
        ], fn ($value): bool => $value !== null);
    }
}

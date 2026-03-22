<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserShippingAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contact_info' => [
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'contact_number' => $this->contact_number,
            ],
            'geo' => [
                'lat' => $this->lat,
                'lng' => $this->lng,
            ],
            'address' => [
                'label' => $this->label,
                'landmark' => $this->landmark,
                'city' => $this->city,
                'district' => $this->district,
                'province' => $this->province,
                'country' => $this->country,
                'is_default' => (bool)$this->is_default,
            ],
        ];
    }
}

<?php

namespace GetCandy\Api\Http\Resources\RecycleBin;

use Illuminate\Http\Resources\Json\JsonResource;
use GetCandy\Api\Http\Resources\DynamicResource;

class RecycleBinResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->encoded_id,
            'type' => $this->recyclable_type,
            'name' => $this->recyclable->getRecycleName(),
            'thumbnail' => $this->recyclable->getRecycleThumbnail(),
            'deleted_at' => $this->recyclable->deleted_at,
            'recyclable' => $this->whenLoaded('recyclable', new DynamicResource($this->recyclable))
        ];
    }
}
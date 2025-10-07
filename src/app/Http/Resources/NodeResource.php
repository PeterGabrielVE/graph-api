<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class NodeResource extends JsonResource
{
    public function toArray($request): array
    {
        $tz = $request->attributes->get('tz') ?: config('app.timezone', 'UTC');
        $locale = substr($request->header('X-Lang') ?: $request->header('Accept-Language') ?: 'en', 0, 2);

        return [
            'id' => (int) $this->id,
            'parent' => $this->parent ? (int) $this->parent : null,
            'title' => $this->translated_title ?? $this->title,
            'created_at' => Carbon::parse($this->created_at, 'UTC')
                ->setTimezone($tz)
                ->toDateTimeString(),
        ];
    }
}

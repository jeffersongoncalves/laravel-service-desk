<?php

namespace JeffersonGoncalves\ServiceDesk\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\ServiceDesk\Models\TicketAttachment;

/**
 * @property TicketAttachment $resource
 *
 * `contents` (base64) is only included when explicitly asked for (the show
 * endpoint, for downloading) -- a listing returns metadata only, so a
 * satellite isn't forced to pull every attachment's bytes just to render a
 * file list.
 */
class TicketAttachmentApiResource extends JsonResource
{
    protected bool $withContents = false;

    public function withContents(): static
    {
        $this->withContents = true;

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->resource->uuid,
            'file_name' => $this->resource->file_name,
            'mime_type' => $this->resource->mime_type,
            'file_size' => $this->resource->file_size,
            'uploaded_by_name' => $this->resource->uploaded_by_name,
            'uploaded_by_email' => $this->resource->uploaded_by_email,
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'contents' => $this->when(
                $this->withContents,
                fn () => base64_encode((string) Storage::disk($this->resource->disk)->get($this->resource->file_path))
            ),
        ];
    }
}

<?php

namespace JeffersonGoncalves\ServiceDesk\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JeffersonGoncalves\ServiceDesk\Concerns\HasActorSnapshot;
use JeffersonGoncalves\ServiceDesk\Concerns\UsesServiceDeskConnection;

/**
 * @property int $id
 * @property string $uuid
 * @property int $ticket_id
 * @property int|null $comment_id
 * @property string $uploaded_by_type
 * @property int $uploaded_by_id
 * @property string|null $uploaded_by_name
 * @property string|null $uploaded_by_email
 * @property string $file_name
 * @property string $file_path
 * @property string $disk
 * @property string $mime_type
 * @property int $file_size
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Ticket $ticket
 * @property-read TicketComment|null $comment
 * @property-read Model|\Eloquent $uploadedBy
 */
class TicketAttachment extends Model
{
    /** @use HasFactory<Factory<static>> */
    use HasActorSnapshot, HasFactory, UsesServiceDeskConnection;

    protected $table = 'service_desk_ticket_attachments';

    protected $fillable = [
        'uuid',
        'ticket_id',
        'comment_id',
        'uploaded_by_type',
        'uploaded_by_id',
        'uploaded_by_name',
        'uploaded_by_email',
        'file_name',
        'file_path',
        'disk',
        'mime_type',
        'file_size',
        'metadata',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (TicketAttachment $attachment) {
            if (empty($attachment->uuid)) {
                $attachment->uuid = (string) Str::uuid();
            }

            if (empty($attachment->disk)) {
                $attachment->disk = config('service-desk.ticket.attachment_disk', 'local');
            }
        });
    }

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /** @return BelongsTo<TicketComment, $this> */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(TicketComment::class, 'comment_id');
    }

    /** @return MorphTo<Model, $this> */
    public function uploadedBy(): MorphTo
    {
        return $this->morphTo('uploadedBy');
    }

    /**
     * The uploader, if its class still exists in this app -- null (never a
     * fatal error) when it doesn't. See HasActorSnapshot.
     */
    public function resolvedUploadedBy(): ?Model
    {
        return $this->resolveActor('uploadedBy', 'uploaded_by_type');
    }

    /** @return array<int, array{0: string, 1: string, 2: string, 3: string}> */
    protected function actorSnapshots(): array
    {
        return [
            ['uploaded_by_type', 'uploaded_by_id', 'uploaded_by_name', 'uploaded_by_email'],
        ];
    }

    public function getUrl(): ?string
    {
        return Storage::disk($this->disk)->url($this->file_path);
    }

    public function getTemporaryUrl(int $minutes = 5): ?string
    {
        return Storage::disk($this->disk)->temporaryUrl(
            $this->file_path,
            now()->addMinutes($minutes)
        );
    }

    public function getFileSizeForHumans(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}

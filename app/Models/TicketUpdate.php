<?php

namespace App\Models;

use App\Enums\TicketUpdateType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketUpdate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ticket_id',
        'created_by_user_id',
        'body',
        'type',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TicketUpdateType::class,
        ];
    }

    /**
     * Get the ticket that owns the update.
     *
     * @return BelongsTo<Ticket, TicketUpdate>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the user who created the update.
     *
     * @return BelongsTo<User, TicketUpdate>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Check if the update is an internal note.
     */
    public function isInternalNote(): bool
    {
        return $this->type === TicketUpdateType::INTERNAL_NOTE;
    }

    /**
     * Check if the update is a comment.
     */
    public function isComment(): bool
    {
        return $this->type === TicketUpdateType::COMMENT;
    }

    /**
     * Check if the update is a status change.
     */
    public function isStatusChange(): bool
    {
        return $this->type === TicketUpdateType::STATUS_CHANGE;
    }
}


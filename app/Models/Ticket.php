<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'subject',
        'description',
        'status',
        'priority',
        'assigned_user_id',
        'opened_at',
        'resolved_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'opened_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * Get the customer that owns the ticket.
     *
     * @return BelongsTo<Customer, Ticket>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user assigned to the ticket.
     *
     * @return BelongsTo<User, Ticket>
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Get the updates for the ticket.
     *
     * @return HasMany<TicketUpdate>
     */
    public function updates(): HasMany
    {
        return $this->hasMany(TicketUpdate::class);
    }

    /**
     * Get the tags for the ticket.
     *
     * @return BelongsToMany<Tag>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'ticket_tag');
    }
}


<?php

namespace App\Enums;

enum TicketUpdateType: string
{
    case COMMENT = 'comment';
    case INTERNAL_NOTE = 'internal_note';
    case STATUS_CHANGE = 'status_change';
}


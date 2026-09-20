<?php

namespace JeffersonGoncalves\ServiceDesk\Api;

use Illuminate\Database\Eloquent\Model;

/**
 * A requester/performer identity asserted by a signed API request, not a
 * row this app's database holds -- the satellite vouches for its own
 * user's type/id/name/email, and the signature is what makes that
 * assertion trustworthy (see VerifyServiceDeskApiSignature's actor_types
 * allow-list). Stands in for a real Model wherever TicketService expects
 * one; never persisted.
 *
 * Note: HasActorSnapshot's name/email snapshot still re-queries the local
 * DB by (type, id) rather than trusting this object's name/email directly,
 * so the snapshot comes back null unless the central app happens to have a
 * matching row for that identity. Fixing that is a separate concern from
 * wiring the API transport up at all.
 */
class RemoteActor extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->exists = true;
    }

    public function getMorphClass(): string
    {
        return (string) $this->getAttribute('type');
    }
}

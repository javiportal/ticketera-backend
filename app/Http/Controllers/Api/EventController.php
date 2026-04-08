<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $events = Event::where('is_active', true)
            ->with('organizer', 'ticketTypes')
            ->orderBy('date')
            ->paginate(15);

        return EventResource::collection($events);
    }

    public function show(Event $event): EventResource
    {
        if (!$event->is_active) {
            abort(422, 'Event is not active');
        }

        $event->load('organizer', 'ticketTypes');

        return new EventResource($event);
    }
}
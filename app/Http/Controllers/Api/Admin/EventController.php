<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Http\Resources\TicketResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('create', Event::class);

        $user = auth()->user();
        $query = Event::with('organizer', 'ticketTypes');

        if (!$user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        }

        return EventResource::collection($query->latest()->paginate(15));
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        $this->authorize('create', Event::class);

        $event = Event::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'description' => $request->description,
            'location' => $request->location,
            'date' => $request->date,
            'is_active' => $request->is_active ?? true,
        ]);

        foreach ($request->ticket_types as $type) {
            $event->ticketTypes()->create($type);
        }

        $event->load('organizer', 'ticketTypes');

        return (new EventResource($event))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Event $event): EventResource
    {
        $this->authorize('update', $event);

        $event->load('organizer', 'ticketTypes');

        return new EventResource($event);
    }

    public function update(UpdateEventRequest $request, Event $event): EventResource
    {
        $this->authorize('update', $event);

        $event->update($request->validated());
        $event->load('organizer', 'ticketTypes');

        return new EventResource($event);
    }

    public function destroy(Event $event): JsonResponse
    {
        $this->authorize('delete', $event);

        $event->delete();

        return response()->json(['message' => 'Event deleted successfully']);
    }

    public function attendees(Event $event): AnonymousResourceCollection
    {
        $this->authorize('viewAttendees', $event);

        $tickets = $event->tickets()
            ->with('user', 'ticketType', 'attendance')
            ->paginate(30);

        return TicketResource::collection($tickets);
    }
}
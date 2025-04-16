<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTicketRequest;
use App\Http\Requests\DeleteTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Ticket;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Support\Facades\Gate;

class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Start with a base query for the authenticated user's tickets
        $query = Ticket::where('user_id', auth()->id());
        
        // Apply status filter if provided
        if ($request->has('status') && in_array($request->status, ['open', 'closed'])) {
            $query->where('status', $request->status);
        }
        
        // Apply priority filter if provided
        if ($request->has('priority') && in_array($request->priority, ['low', 'medium', 'high'])) {
            $query->where('priority', $request->priority);
        }
        
        // Get the filtered tickets with pagination
        $tickets = $query->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString(); // Preserve the query parameters in pagination links

        // Ensure the tickets data is properly formatted even when empty
        if ($tickets->isEmpty()) {
            $tickets->data = [];
        }

        return Inertia::render('Tickets/Index', [
            'tickets' => $tickets,
            'filters' => [
                'status' => $request->status,
                'priority' => $request->priority,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Not needed with Inertia.js as we'll use a modal in the Index page
        return redirect()->route('tickets.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateTicketRequest $request)
    {
        // Authorization is handled in the form request

        $validated = $request->validated();
        
        // Handle file upload if attachment is provided
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('tickets', 'public');
            $validated['attachment'] = $path;
        }
        
        // Add user_id to the validated data
        $validated['user_id'] = auth()->id();
        
        // Create the ticket
        Ticket::create($validated);
        
        return redirect()->route('tickets.index')
            ->with('success', 'Ticket created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Ticket $ticket)
    {
        // Check if the authenticated user owns the ticket
        if ($ticket->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }
        
        // Load the user relationship
        $ticket->load('user');
        
        return Inertia::render('Tickets/Show', [
            'ticket' => $ticket,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ticket $ticket)
    {
        // Not needed with Inertia.js as we'll handle editing in the Show page
        return redirect()->route('tickets.show', $ticket);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket)
    {
        // Authorization is handled in the form request
        
        // Validate that we're only allowing status changes to 'closed'
        $validated = $request->validated();
        
        // Update the ticket status to closed
        $ticket->update([
            'status' => 'closed',
        ]);
        
        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Ticket closed successfully.',
                'ticket' => $ticket,
            ]);
        }
        
        return redirect()->route('tickets.index')
            ->with('success', 'Ticket closed successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeleteTicketRequest $request, Ticket $ticket)
    {
        // Authorization is handled in the form request

        // Delete the attachment if it exists
        if ($ticket->attachment) {
            Storage::disk('public')->delete($ticket->attachment);
        }
        
        // Soft delete the ticket
        $ticket->delete();
        
        return redirect()->route('tickets.index')
            ->with('success', 'Ticket deleted successfully.');
    }
    
    /**
     * Download the ticket attachment.
     */
    public function downloadAttachment(Ticket $ticket)
    {
        // Check if the authenticated user owns the ticket
        if ($ticket->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }
        
        // Check if the ticket has an attachment
        if (!$ticket->attachment) {
            abort(404, 'Attachment not found.');
        }
        
        return Storage::disk('public')->download($ticket->attachment);
    }
}

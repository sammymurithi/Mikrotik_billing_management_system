<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\Router;
use App\Models\HotspotProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class VoucherController extends Controller
{
    public function index()
    {
        $vouchers = Voucher::with(['router', 'profile'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $routers = Router::all();
        $profiles = HotspotProfile::all();

        return Inertia::render('Vouchers/Index', [
            'vouchers' => $vouchers,
            'routers' => $routers,
            'profiles' => $profiles,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'count' => 'required|integer|min:1|max:100',
            'profile' => 'required|string',
            'router_id' => 'required|exists:routers,id',
            'limit_mb' => 'nullable|integer',
        ]);

        $router = Router::findOrFail($validated['router_id']);
        $profile = HotspotProfile::where('name', $validated['profile'])->firstOrFail();

        $vouchers = [];
        for ($i = 0; $i < $validated['count']; $i++) {
            $username = 'voucher_' . strtolower(Str::random(8));
            $password = Str::random(8);

            $voucher = Voucher::create([
                'username' => $username,
                'password' => $password,
                'profile' => $validated['profile'],
                'router_id' => $validated['router_id'],
                'limit_mb' => $validated['limit_mb'],
                'status' => 'active',
            ]);

            $vouchers[] = $voucher;
        }

        return redirect()->back()->with('success', 'Vouchers generated successfully.');
    }

    public function destroy(Voucher $voucher)
    {
        $voucher->delete();
        return back()->with('success', 'Voucher deleted.');
    }

    public function disable(Voucher $voucher)
    {
        $voucher->update([
            'status' => 'used',
            'used_at' => now()
        ]);
        return back()->with('success', 'Voucher marked as used.');
    }

    public function batchDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:vouchers,id'
        ]);

        // Only allow deleting used vouchers
        Voucher::whereIn('id', $request->ids)
            ->where('status', 'used')
            ->delete();
            
        return back()->with('success', 'Used vouchers deleted successfully.');
    }

    public function batchDisable(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:vouchers,id'
        ]);

        Voucher::whereIn('id', $request->ids)
            ->where('status', 'active')
            ->update([
                'status' => 'used',
                'used_at' => now()
            ]);
            
        return back()->with('success', 'Selected vouchers marked as used.');
    }

    // Add a method to handle voucher usage through captive portal
    public function markAsUsed($username)
    {
        $voucher = Voucher::where('username', $username)
            ->where('status', 'active')
            ->first();

        if ($voucher) {
            $voucher->update([
                'status' => 'used',
                'used_at' => now()
            ]);
            return true;
        }

        return false;
    }
}
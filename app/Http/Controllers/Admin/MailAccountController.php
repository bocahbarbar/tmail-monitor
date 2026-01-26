<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MailAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MailAccountController extends Controller
{
    /**
     * Display listing of mail accounts
     */
    public function index()
    {
        $accounts = MailAccount::orderBy('created_at', 'desc')->get();
        return view('admin.mail-accounts.index', compact('accounts'));
    }

    /**
     * Show form to create new account
     */
    public function create()
    {
        return view('admin.mail-accounts.create');
    }

    /**
     * Store new mail account
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bearer_token' => 'required|string',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->has('is_active') ? true : false;

        // Fetch account info dari mail.tm API
        try {
            $accountInfo = $this->fetchAccountInfo($validated['bearer_token']);
            
            // Merge account info ke validated data
            $validated = array_merge($validated, $accountInfo);
            
        } catch (\Exception $e) {
            return back()->withErrors(['bearer_token' => 'Gagal fetch account info: ' . $e->getMessage()])->withInput();
        }

        MailAccount::create($validated);

        return redirect()->route('admin.mail-accounts.index')
            ->with('success', 'Mail account berhasil ditambahkan!');
    }

    /**
     * Show form to edit account
     */
    public function edit(MailAccount $mailAccount)
    {
        return view('admin.mail-accounts.edit', compact('mailAccount'));
    }

    /**
     * Update mail account
     */
    public function update(Request $request, MailAccount $mailAccount)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bearer_token' => 'required|string',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->has('is_active') ? true : false;

        // Fetch account info if token changed
        if ($validated['bearer_token'] !== $mailAccount->bearer_token) {
            try {
                $accountInfo = $this->fetchAccountInfo($validated['bearer_token']);
                $validated = array_merge($validated, $accountInfo);
            } catch (\Exception $e) {
                return back()->withErrors(['bearer_token' => 'Gagal fetch account info: ' . $e->getMessage()])->withInput();
            }
        }

        $mailAccount->update($validated);

        return redirect()->route('admin.mail-accounts.index')
            ->with('success', 'Mail account berhasil diupdate!');
    }

    /**
     * Delete mail account
     */
    public function destroy(MailAccount $mailAccount)
    {
        $mailAccount->delete();

        return redirect()->route('admin.mail-accounts.index')
            ->with('success', 'Mail account berhasil dihapus!');
    }

    /**
     * Toggle active status
     */
    public function toggleStatus(MailAccount $mailAccount)
    {
        $mailAccount->update(['is_active' => !$mailAccount->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $mailAccount->is_active,
            'message' => 'Status berhasil diubah'
        ]);
    }

    /**
     * Test API connection for an account
     */
    public function testConnection(MailAccount $mailAccount)
    {
        try {
            $response = Http::timeout(10)->withHeaders([
                'accept' => '*/*',
                'authorization' => "Bearer {$mailAccount->bearer_token}",
            ])->get('https://api.mail.tm/messages');

            $body = $response->json();

            return response()->json([
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'message_count' => isset($body['hydra:member']) ? count($body['hydra:member']) : 0,
                'response' => $body,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh account info dari API
     */
    public function refreshAccountInfo(MailAccount $mailAccount)
    {
        try {
            $accountInfo = $this->fetchAccountInfo($mailAccount->bearer_token);
            $mailAccount->update($accountInfo);

            return response()->json([
                'success' => true,
                'message' => 'Account info berhasil di-refresh!',
                'data' => $accountInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fetch account info dari mail.tm API /me endpoint
     */
    private function fetchAccountInfo($bearerToken)
    {
        $response = Http::timeout(10)->withHeaders([
            'accept' => '*/*',
            'authorization' => "Bearer {$bearerToken}",
        ])->get('https://api.mail.tm/me');

        if (!$response->successful()) {
            throw new \Exception('Token tidak valid atau API error (HTTP ' . $response->status() . ')');
        }

        $data = $response->json();

        // Extract domain dari address
        $email = $data['address'] ?? null;
        $domain = $email ? '@' . explode('@', $email)[1] : null;

        return [
            'account_id' => $data['id'] ?? null,
            'email' => $email,
            'domain' => $domain,
            'quota' => $data['quota'] ?? null,
            'used' => $data['used'] ?? null,
            'is_disabled' => $data['isDisabled'] ?? false,
            'is_deleted' => $data['isDeleted'] ?? false,
            'account_created_at' => isset($data['createdAt']) ? \Carbon\Carbon::parse($data['createdAt']) : null,
            'account_updated_at' => isset($data['updatedAt']) ? \Carbon\Carbon::parse($data['updatedAt']) : null,
        ];
    }
}

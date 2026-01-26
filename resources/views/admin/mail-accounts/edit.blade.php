@extends('layouts.admin')

@section('title', 'Edit Mail Account')

@section('content')
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-edit"></i> Edit Mail Account</h5>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.mail-accounts.update', $mailAccount) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Nama <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" 
                                   value="{{ old('name', $mailAccount->name) }}" 
                                   required>
                        </div>

                        <!-- Display current email/domain (read-only) -->
                        <div class="mb-3">
                            <label class="form-label">Email Address (Auto-detected)</label>
                            <input type="text" class="form-control" 
                                   value="{{ $mailAccount->email }}" 
                                   readonly>
                            <small class="text-muted">Email akan di-update otomatis jika token diubah</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Domain (Auto-detected)</label>
                            <input type="text" class="form-control" 
                                   value="{{ $mailAccount->domain }}" 
                                   readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Bearer Token <span class="text-danger">*</span></label>
                            <textarea name="bearer_token" class="form-control font-monospace" 
                                      rows="4" 
                                      required>{{ old('bearer_token', $mailAccount->bearer_token) }}</textarea>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> Account info akan di-refresh jika token diubah
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" 
                                      rows="3">{{ old('notes', $mailAccount->notes) }}</textarea>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" 
                                       id="is_active" {{ old('is_active', $mailAccount->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    <strong>Aktifkan akun ini</strong>
                                </label>
                            </div>
                        </div>

                        <!-- Stats Info -->
                        <div class="alert alert-info">
                            <strong>Account Info:</strong><br>
                            Account ID: <code>{{ $mailAccount->account_id ?? 'N/A' }}</code><br>
                            @if($mailAccount->quota)
                                Storage: {{ number_format($mailAccount->used / 1024 / 1024, 2) }} MB / {{ number_format($mailAccount->quota / 1024 / 1024, 2) }} MB
                                ({{ number_format(($mailAccount->used / $mailAccount->quota) * 100, 1) }}% used)<br>
                            @endif
                            Total Messages Fetched: {{ $mailAccount->message_count }}<br>
                            Last Fetch: {{ $mailAccount->last_fetch_at ? $mailAccount->last_fetch_at->format('Y-m-d H:i:s') : 'Never' }}<br>
                            Account Created: {{ $mailAccount->account_created_at ? $mailAccount->account_created_at->format('Y-m-d H:i:s') : 'N/A' }}<br>
                            Status: 
                            @if($mailAccount->is_disabled)
                                <span class="badge bg-danger">Disabled</span>
                            @elseif($mailAccount->is_deleted)
                                <span class="badge bg-dark">Deleted</span>
                            @else
                                <span class="badge bg-success">Active</span>
                            @endif
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.mail-accounts.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save"></i> Update Akun
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

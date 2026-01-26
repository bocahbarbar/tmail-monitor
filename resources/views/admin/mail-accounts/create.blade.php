@extends('layouts.admin')

@section('title', 'Tambah Mail Account')

@section('content')
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-plus"></i> Tambah Mail Account Baru</h5>
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

                    <form action="{{ route('admin.mail-accounts.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Nama <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" 
                                   value="{{ old('name') }}" 
                                   placeholder="Contoh: Main Account, Backup Email, etc"
                                   required>
                            <small class="text-muted">Nama identifikasi untuk akun ini</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Bearer Token <span class="text-danger">*</span></label>
                            <textarea name="bearer_token" class="form-control font-monospace" 
                                      rows="4" 
                                      placeholder="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
                                      required>{{ old('bearer_token') }}</textarea>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> Bearer token untuk API mail.tm. 
                                <strong>Email dan domain akan otomatis diambil dari API!</strong>
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" 
                                      rows="3" 
                                      placeholder="Catatan tambahan (opsional)">{{ old('notes') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" 
                                       id="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    <strong>Aktifkan akun ini</strong>
                                    <br><small class="text-muted">Akun aktif akan otomatis di-fetch untuk OTP</small>
                                </label>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.mail-accounts.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Akun
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info Card -->
            <div class="card mt-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-question-circle"></i> Cara Mendapatkan Bearer Token</h6>
                </div>
                <div class="card-body">
                    <ol class="mb-0">
                        <li>Buka <a href="https://mail.tm" target="_blank">https://mail.tm</a></li>
                        <li>Buat akun baru atau login ke akun existing</li>
                        <li>Buka Developer Tools (F12) → Network tab</li>
                        <li>Lakukan action apapun di mail.tm (refresh, buka email, dll)</li>
                        <li>Cari request ke <code>api.mail.tm</code></li>
                        <li>Di Request Headers, copy nilai dari <code>Authorization: Bearer ...</code></li>
                        <li>Paste token tersebut (tanpa kata "Bearer") ke form di atas</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

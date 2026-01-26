@extends('layouts.admin')

@section('title', 'Manage Mail Accounts')

@section('header-actions')
    <a href="{{ route('admin.mail-accounts.create') }}" class="btn btn-primary btn-sm">
        <i class="fas fa-plus"></i> Tambah Akun
    </a>
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-envelope"></i> Mail Accounts</h5>
                </div>
                <div class="card-body">
                    @if($accounts->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Belum ada mail account. Silakan tambahkan akun baru.</p>
                            <a href="{{ route('admin.mail-accounts.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Tambah Akun Pertama
                            </a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="15%">Nama</th>
                                        <th width="20%">Email</th>
                                        <th width="10%">Storage</th>
                                        <th width="8%">Status</th>
                                        <th width="8%">Messages</th>
                                        <th width="12%">Last Fetch</th>
                                        <th width="15%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($accounts as $account)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <strong>{{ $account->name }}</strong>
                                            </td>
                                            <td>
                                                <code>{{ $account->email }}</code>
                                            </td>
                                            <td>
                                                @if($account->quota)
                                                    <small>
                                                        {{ number_format($account->used / 1024 / 1024, 1) }}MB / 
                                                        {{ number_format($account->quota / 1024 / 1024, 1) }}MB
                                                        <br>
                                                        <div class="progress" style="height: 5px;">
                                                            <div class="progress-bar {{ ($account->used / $account->quota * 100) > 80 ? 'bg-danger' : 'bg-success' }}" 
                                                                 style="width: {{ ($account->used / $account->quota * 100) }}%"></div>
                                                        </div>
                                                    </small>
                                                @else
                                                    <small class="text-muted">N/A</small>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input toggle-status" 
                                                           type="checkbox" 
                                                           {{ $account->is_active ? 'checked' : '' }}
                                                           data-id="{{ $account->id }}">
                                                    <label class="form-check-label">
                                                        <span class="badge bg-{{ $account->is_active ? 'success' : 'secondary' }}">
                                                            {{ $account->is_active ? 'Active' : 'Inactive' }}
                                                        </span>
                                                    </label>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">{{ $account->message_count }}</span>
                                            </td>
                                            <td>
                                                @if($account->last_fetch_at)
                                                    <small>{{ $account->last_fetch_at->diffForHumans() }}</small>
                                                @else
                                                    <small class="text-muted">Never</small>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-info test-connection" data-id="{{ $account->id }}" title="Test Connection">
                                                        <i class="fas fa-flask"></i>
                                                    </button>
                                                    <button class="btn btn-success refresh-info" data-id="{{ $account->id }}" title="Refresh Account Info">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </button>
                                                    <a href="{{ route('admin.mail-accounts.edit', $account) }}" class="btn btn-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('admin.mail-accounts.destroy', $account) }}" 
                                                          method="POST" 
                                                          class="d-inline"
                                                          onsubmit="return confirm('Yakin hapus akun ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Test Connection Modal -->
    <div class="modal fade" id="testResultModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Test Connection Result</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <pre id="test-result" style="max-height: 400px; overflow-y: auto;"></pre>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Toggle Status
    $('.toggle-status').change(function() {
        const accountId = $(this).data('id');
        const isChecked = $(this).is(':checked');
        
        $.post(`/admin/mail-accounts/${accountId}/toggle-status`, {
            _token: '{{ csrf_token() }}'
        }).done(function(response) {
            if(response.success) {
                const badge = $(`.toggle-status[data-id="${accountId}"]`)
                    .closest('td')
                    .find('.badge');
                    
                if(response.is_active) {
                    badge.removeClass('bg-secondary').addClass('bg-success').text('Active');
                } else {
                    badge.removeClass('bg-success').addClass('bg-secondary').text('Inactive');
                }
                
                showNotification(response.message, 'success');
            }
        }).fail(function() {
            showNotification('Gagal mengubah status', 'danger');
            // Revert checkbox
            $(this).prop('checked', !isChecked);
        });
    });
    
    // Test Connection
    $('.test-connection').click(function() {
        const accountId = $(this).data('id');
        const btn = $(this);
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.get(`/admin/mail-accounts/${accountId}/test-connection`)
        .done(function(response) {
            $('#test-result').text(JSON.stringify(response, null, 2));
            $('#testResultModal').modal('show');
        })
        .fail(function(xhr) {
            $('#test-result').text(xhr.responseText);
            $('#testResultModal').modal('show');
        })
        .always(function() {
            btn.prop('disabled', false).html('<i class="fas fa-flask"></i>');
        });
    });
    
    // Refresh Account Info
    $('.refresh-info').click(function() {
        const accountId = $(this).data('id');
        const btn = $(this);
        
        if(!confirm('Refresh account info dari API?')) return;
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.post(`/admin/mail-accounts/${accountId}/refresh-info`, {
            _token: '{{ csrf_token() }}'
        })
        .done(function(response) {
            if(response.success) {
                showNotification(response.message, 'success');
                setTimeout(() => location.reload(), 1500);
            }
        })
        .fail(function(xhr) {
            const error = xhr.responseJSON?.error || 'Gagal refresh account info';
            showNotification(error, 'danger');
        })
        .always(function() {
            btn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i>');
        });
    });
    
    function showNotification(message, type) {
        const alert = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        $('.card-body').prepend(alert);
        
        setTimeout(() => {
            $('.alert').fadeOut();
        }, 3000);
    }
});
</script>
@endpush

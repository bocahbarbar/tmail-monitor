@extends('layouts.admin')

@section('title', 'OTP Mail Monitor')

@section('header-actions')
    <div class="refresh-info d-flex flex-wrap align-items-center gap-2">
        <i class="fas fa-sync-alt status-badge text-primary"></i>
        <span class="d-none d-md-inline">Auto refresh setiap <strong id="refresh-interval">5</strong> detik</span>
        <span class="d-md-none"><strong id="refresh-interval-mobile">5</strong>s</span>
        <button class="btn btn-sm btn-outline-primary" id="toggle-refresh">
            <i class="fas fa-pause"></i> <span class="d-none d-md-inline">Pause</span>
        </button>
        <select class="form-select form-select-sm" style="width: auto;" id="interval-select">
            <option value="3">3s</option>
            <option value="5" selected>5s</option>
            <option value="10">10s</option>
            <option value="15">15s</option>
            <option value="30">30s</option>
        </select>
    </div>
@endsection


@section('content')
    <!-- Filter Section -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-filter"></i> Custom Sort Filter</span>
                    <button class="btn btn-sm btn-light" id="toggle-filter">
                        <i class="fas fa-chevron-up"></i>
                    </button>
                </div>
                <div class="card-body" id="filter-section">
                    <div class="mb-3">
                        <label class="form-label fw-bold">List ID (1 baris = 1 item)</label>
                        <textarea id="visaList" class="form-control font-monospace" rows="4"
                            placeholder="Contoh:&#10;12349877167&#10;12349876940&#10;12349877174&#10;12349877169"></textarea>
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> Urutan otomatis dari <b>BAWAH ke ATAS</b> (yang paling bawah
                            akan tampil pertama)
                        </small>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-success btn-sm" id="apply-filter">
                            <i class="fas fa-check"></i> Terapkan
                        </button>
                        <button class="btn btn-secondary btn-sm" id="clear-filter">
                            <i class="fas fa-times"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats & Actions -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-2">
                    <!-- Stats Badges - Mobile Optimized -->
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <span class="badge bg-success">
                            <i class="fas fa-database"></i> <span id="total-count">0</span>
                        </span>
                        <span class="badge bg-info">
                            <i class="fas fa-clock"></i> <span id="last-update" class="d-none d-md-inline">-</span><span
                                class="d-md-none" id="last-update-short">-</span>
                        </span>
                        <span class="badge bg-warning" id="filter-status" style="display: none;">
                            <i class="fas fa-filter"></i> <span id="filter-count">0</span>
                        </span>
                        <span class="badge bg-primary" id="fetch-status">
                            <i class="fas fa-cloud-download-alt"></i> <span id="fetch-text">Standby</span>
                        </span>
                    </div>

                    <!-- Action Buttons - Mobile Optimized -->
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-warning btn-sm" id="test-api">
                            <i class="fas fa-flask"></i> <span class="d-none d-md-inline">Test API</span>
                        </button>
                        <button class="btn btn-success btn-sm" id="fetch-now">
                            <i class="fas fa-download"></i> <span class="d-none d-md-inline">Fetch Now</span>
                        </button>
                        <button class="btn btn-primary btn-sm" id="manual-refresh">
                            <i class="fas fa-sync-alt"></i> <span class="d-none d-md-inline">Refresh</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- API Test Result Modal Area -->
    <div id="api-test-result" class="alert alert-info" style="display: none;">
        <h5><i class="fas fa-info-circle"></i> Test API Result:</h5>
        <pre id="api-test-content"
            style="background: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto;"></pre>
    </div>

    <!-- Table Section - Mobile Responsive -->
    <div class="table-responsive">
        <table class="table table-hover table-bordered table-sm">
            <thead class="table-primary">
                <tr>
                    <th class="d-none d-md-table-cell" width="5%">#</th>
                    <th class="d-none d-lg-table-cell" width="15%">Email Tujuan</th>
                    <th width="20%">Kode OTP</th>
                    <th class="d-none d-md-table-cell" width="15%">Sumber</th>
                    <th class="d-none d-lg-table-cell" width="10%">Status</th>
                    <th class="d-none d-xl-table-cell" width="20%">Message ID</th>
                    <th class="d-none d-md-table-cell" width="20%">Waktu</th>
                </tr>
            </thead>
            <tbody id="otp-table-body">
                <tr>
                    <td colspan="7" class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Memuat data OTP...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Alert untuk notifikasi -->
    <div id="notification-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>
@endsection

@push('styles')
    <style>
        .table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        .otp-code {
            font-family: 'Courier New', monospace;
            font-size: 1.2rem;
            font-weight: bold;
            color: #667eea;
            background: #f0f0f0;
            padding: 5px 10px;
            border-radius: 5px;
            display: inline-block;
        }

        /* Mobile responsive OTP code */
        @media (max-width: 768px) {
            .otp-code {
                font-size: 1rem;
                padding: 4px 8px;
            }

            .table {
                font-size: 0.875rem;
            }

            .badge {
                font-size: 0.75rem;
            }

            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
        }

        .new-row {
            animation: highlightRow 2s ease;
        }

        @keyframes highlightRow {
            0% {
                background-color: #d1ecf1;
            }

            100% {
                background-color: transparent;
            }
        }

        .copy-btn {
            cursor: pointer;
            transition: all 0.3s;
        }

        .copy-btn:hover {
            transform: scale(1.1);
        }

        .filtered-row {
            background-color: #fff3cd !important;
            border-left: 4px solid #ffc107;
        }

        .font-monospace {
            font-family: 'Courier New', monospace;
        }

        /* Mobile: Stack action buttons vertically on very small screens */
        @media (max-width: 576px) {
            .refresh-info {
                font-size: 0.85rem;
            }
        }

        /* Smooth transition for filter collapse */
        #filter-section {
            transition: all 0.3s ease;
            overflow: hidden;
        }

        #filter-section.collapsed {
            display: none;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            let refreshInterval;
            let isRefreshing = true;
            let currentInterval = 5000; // 5 detik default
            let lastData = [];
            let customFilterList = []; // Array untuk menyimpan filter custom
            let isFilterVisible = true; // Track filter visibility

            // Set CSRF token untuk AJAX
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Toggle filter visibility
            $('#toggle-filter').click(function() {
                isFilterVisible = !isFilterVisible;
                const $filterSection = $('#filter-section');
                const $icon = $(this).find('i');

                if (isFilterVisible) {
                    $filterSection.slideDown(300);
                    $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                } else {
                    $filterSection.slideUp(300);
                    $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                }

                // Save preference to localStorage
                localStorage.setItem('filterVisible', isFilterVisible);
            });

            // Restore filter visibility from localStorage
            const savedFilterVisible = localStorage.getItem('filterVisible');
            if (savedFilterVisible !== null) {
                isFilterVisible = savedFilterVisible === 'true';
                if (!isFilterVisible) {
                    $('#filter-section').hide();
                    $('#toggle-filter i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                }
            }

            // Fungsi untuk mendapatkan visa list dari textarea
            function getVisaList() {
                return $('#visaList')
                    .val()
                    .split('\n')
                    .map(v => v.trim())
                    .filter(v => v !== '')
                    .reverse(); // Yang paling bawah jadi pertama
            }

            // Apply filter button
            $('#apply-filter').click(function() {
                customFilterList = getVisaList();
                if (customFilterList.length > 0) {
                    $('#filter-status').show();
                    $('#filter-count').text(customFilterList.length);
                    showNotification(`Filter diterapkan: ${customFilterList.length} items`, 'success');
                    loadOtpData();
                } else {
                    showNotification('Masukkan list ID/Visa terlebih dahulu', 'warning');
                }
            });

            // Clear filter button
            $('#clear-filter').click(function() {
                customFilterList = [];
                $('#visaList').val('');
                $('#filter-status').hide();
                showNotification('Filter direset', 'info');
                loadOtpData();
            });

            // Fungsi untuk memuat data OTP
            function loadOtpData(showLoader = false) {
                if (showLoader) {
                    $('#fetch-status').removeClass('bg-primary bg-success bg-danger').addClass('bg-warning');
                    $('#fetch-text').html('<span class="spinner-border spinner-border-sm me-1"></span>Loading...');
                }

                $.ajax({
                    url: '{{ route('admin.otp.data') }}',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log('✓ Data received:', response);

                        if (response.success) {
                            updateTable(response.data);
                            $('#last-update').text(response.timestamp);
                            // Mobile short version of timestamp
                            const shortTime = new Date().toLocaleTimeString('id-ID', {
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                            $('#last-update-short').text(shortTime);
                            $('#total-count').text(response.data.length);

                            // Update fetch status
                            $('#fetch-status').removeClass('bg-warning bg-danger').addClass(
                                'bg-success');
                            $('#fetch-text').text('Connected');

                            // Show fetch info if available
                            if (response.message_count !== undefined) {
                                console.log(
                                    `📊 Stats: ${response.message_count} messages, ${response.otp_count} OTPs`
                                );
                                
                                // Tampilkan notifikasi fetch berhasil
                                if (response.message_count > 0) {
                                    showNotification(`Fetch berhasil: ${response.message_count} pesan baru diproses, ${response.otp_count} OTP ditemukan (5 menit terakhir)`, 'info');
                                }
                            }

                            // Cek data baru
                            checkNewData(response.data);
                        } else {
                            console.error('❌ Request failed:', response.error);
                            $('#fetch-status').removeClass('bg-warning bg-success').addClass(
                                'bg-danger');
                            $('#fetch-text').text('Error');
                            showNotification('Failed: ' + (response.error || 'Unknown error'),
                                'danger');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ AJAX Error:', {
                            status: xhr.status,
                            statusText: xhr.statusText,
                            responseText: xhr.responseText,
                            error: error
                        });

                        $('#fetch-status').removeClass('bg-warning bg-success').addClass('bg-danger');
                        $('#fetch-text').text('Error');

                        // Check if setup is needed
                        try {
                            const response = JSON.parse(xhr.responseText);
                            console.log('Error response:', response);

                            if (response.setup_url) {
                                const setupMsg = `
                                    <div>
                                        Database belum disetup! 
                                        <a href="${response.setup_url}" target="_blank" class="alert-link">
                                            <strong>Klik disini untuk setup database</strong>
                                        </a>
                                    </div>
                                `;
                                showNotification(setupMsg, 'warning');
                            } else {
                                showNotification('Gagal memuat data OTP: ' + (response.error || error),
                                    'danger');
                            }
                        } catch (e) {
                            showNotification('Gagal memuat data OTP', 'danger');
                        }
                    }
                });
            }

            // Fetch now button
            $('#fetch-now').click(function() {
                const btn = $(this);
                btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span>Fetching...');

                showNotification('Mengambil data dari Mail.TM...', 'info');
                loadOtpData(true);

                setTimeout(() => {
                    btn.prop('disabled', false).html(
                        '<i class="fas fa-download"></i> Fetch Mail.TM Sekarang');
                }, 3000);
            });

            // Test API button
            $('#test-api').click(function() {
                const btn = $(this);
                btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span>Testing...');

                $.ajax({
                    url: '{{ route('admin.otp.test') }}',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        $('#api-test-result').show();
                        $('#api-test-content').text(JSON.stringify(response, null, 2));

                        if (response.success) {
                            showNotification(
                                `API Test Success! ${response.total_messages} messages found`,
                                'success');
                        } else {
                            showNotification('API Test Failed: ' + (response.error ||
                                'Unknown error'), 'danger');
                        }
                    },
                    error: function(xhr) {
                        $('#api-test-result').show();
                        let errorText = 'Unknown error';
                        try {
                            const errorData = JSON.parse(xhr.responseText);
                            errorText = JSON.stringify(errorData, null, 2);
                        } catch (e) {
                            errorText = xhr.responseText || xhr.statusText;
                        }
                        $('#api-test-content').text(errorText);
                        showNotification('API Test Error: ' + xhr.status, 'danger');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(
                            '<i class="fas fa-flask"></i> Test API Mail.TM');
                    }
                });
            });

            // Fungsi untuk sort dan filter data
            function sortAndFilterData(data) {
                if (customFilterList.length === 0) {
                    // Jika tidak ada filter, return data biasa (sorted by created_at desc)
                    return data;
                }

                // Filter dan sort berdasarkan customFilterList
                let filtered = [];
                let others = [];

                // Pisahkan data yang match dan tidak match
                data.forEach(function(item) {
                    let matchFound = false;

                    // Cek apakah ada field yang match dengan filter list
                    customFilterList.forEach(function(filterId) {
                        const filterLower = filterId.toLowerCase();
                        const checkFields = [
                            item.message_id,
                            item.to_address,
                            item.otp,
                            item.source,
                            item.id ? item.id.toString() : null
                        ];

                        checkFields.forEach(function(field) {
                            if (field && field.toString().toLowerCase().includes(
                                    filterLower)) {
                                matchFound = true;
                            }
                        });
                    });

                    if (matchFound) {
                        filtered.push(item);
                    } else {
                        others.push(item);
                    }
                });

                // Sort filtered items berdasarkan urutan di customFilterList (reversed)
                filtered.sort(function(a, b) {
                    let indexA = -1;
                    let indexB = -1;

                    customFilterList.forEach(function(filterId, index) {
                        const filterLower = filterId.toLowerCase();

                        // Check item A
                        const checkFieldsA = [
                            a.message_id,
                            a.to_address,
                            a.otp,
                            a.source,
                            a.id ? a.id.toString() : null
                        ];
                        checkFieldsA.forEach(function(field) {
                            if (field && field.toString().toLowerCase().includes(
                                    filterLower) && indexA === -1) {
                                indexA = index;
                            }
                        });

                        // Check item B
                        const checkFieldsB = [
                            b.message_id,
                            b.to_address,
                            b.otp,
                            b.source,
                            b.id ? b.id.toString() : null
                        ];
                        checkFieldsB.forEach(function(field) {
                            if (field && field.toString().toLowerCase().includes(
                                    filterLower) && indexB === -1) {
                                indexB = index;
                            }
                        });
                    });

                    return indexA - indexB;
                });

                // Gabungkan: filtered items di atas, others di bawah
                return [...filtered, ...others];
            }

            // Fungsi untuk update tabel
            function updateTable(data) {
                // Sort and filter data
                const sortedData = sortAndFilterData(data);

                if (sortedData.length === 0) {
                    $('#otp-table-body').html(`
                <tr>
                    <td colspan="7" class="text-center text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>Belum ada data OTP</p>
                    </td>
                </tr>
            `);
                    return;
                }

                let html = '';
                sortedData.forEach(function(otp, index) {
                    const isNew = !lastData.find(item => item.id === otp.id);
                    const isFiltered = customFilterList.length > 0 && isMatchingFilter(otp);
                    const rowClass = isNew ? 'new-row' : (isFiltered ? 'filtered-row' : '');

                    html += `
                <tr class="${rowClass}">
                    <td class="d-none d-md-table-cell">${index + 1}</td>
                    <td class="d-none d-lg-table-cell">
                        <i class="fas fa-envelope text-primary"></i> 
                        <small>${otp.to_address || '-'}</small>
                    </td>
                    <td>
                        <span class="otp-code">${otp.otp || '-'}</span>
                        ${otp.otp ? `<i class="fas fa-copy copy-btn ms-2 text-secondary" onclick="copyToClipboard('${otp.otp}')" title="Copy OTP"></i>` : ''}
                        <div class="d-md-none mt-1">
                            <small class="text-muted d-block"><i class="fas fa-envelope"></i> ${otp.to_address || '-'}</small>
                            <small class="text-muted d-block"><i class="far fa-clock"></i> ${formatDate(otp.created_at)}</small>
                        </div>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <span class="badge bg-secondary">${otp.source || '-'}</span>
                    </td>
                    <td class="d-none d-lg-table-cell">
                        <span class="badge bg-${otp.status === 'active' ? 'success' : 'warning'}">
                            ${otp.status || 'pending'}
                        </span>
                    </td>
                    <td class="d-none d-xl-table-cell">
                        <small class="text-muted">${otp.message_id ? otp.message_id.substring(0, 30) + '...' : '-'}</small>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <small><i class="far fa-clock"></i> ${formatDate(otp.created_at)}</small>
                    </td>
                </tr>
            `;
                });

                $('#otp-table-body').html(html);
                lastData = data;
            }

            // Check if item matches filter
            function isMatchingFilter(item) {
                if (customFilterList.length === 0) return false;

                let matchFound = false;
                customFilterList.forEach(function(filterId) {
                    const filterLower = filterId.toLowerCase();
                    const checkFields = [
                        item.message_id,
                        item.to_address,
                        item.otp,
                        item.source,
                        item.id ? item.id.toString() : null
                    ];

                    checkFields.forEach(function(field) {
                        if (field && field.toString().toLowerCase().includes(filterLower)) {
                            matchFound = true;
                        }
                    });
                });

                return matchFound;
            }

            // Cek data baru dan tampilkan notifikasi
            function checkNewData(data) {
                const newItems = data.filter(item => !lastData.find(old => old.id === item.id));
                if (newItems.length > 0 && lastData.length > 0) {
                    showNotification(`${newItems.length} OTP baru diterima!`, 'success');

                    // Play sound notification (optional)
                    playNotificationSound();
                }
            }

            // Fungsi untuk format tanggal
            function formatDate(dateString) {
                if (!dateString) return '-';
                const date = new Date(dateString);
                return date.toLocaleString('id-ID');
            }

            // Copy to clipboard dengan fallback
            window.copyToClipboard = function(text) {
                // Method 1: Modern Clipboard API
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(function() {
                        showNotification('OTP "' + text + '" berhasil disalin ke clipboard!', 'success');
                    }).catch(function(err) {
                        // Fallback jika clipboard API gagal
                        fallbackCopyToClipboard(text);
                    });
                } else {
                    // Fallback untuk browser lama atau non-HTTPS
                    fallbackCopyToClipboard(text);
                }
            };

            // Fallback copy menggunakan textarea hidden
            function fallbackCopyToClipboard(text) {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                
                // Pastikan tidak terlihat
                textArea.style.position = 'fixed';
                textArea.style.left = '-9999px';
                textArea.style.top = '-9999px';
                
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                try {
                    const successful = document.execCommand('copy');
                    if (successful) {
                        showNotification('OTP "' + text + '" berhasil disalin ke clipboard!', 'success');
                    } else {
                        showNotification('Gagal menyalin OTP. Coba copy manual: ' + text, 'warning');
                    }
                } catch (err) {
                    showNotification('Gagal menyalin OTP. Coba copy manual: ' + text, 'warning');
                }
                
                document.body.removeChild(textArea);
            }

            // Tampilkan notifikasi
            function showNotification(message, type = 'info') {
                const alert = $(`
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);

                $('#notification-container').append(alert);

                setTimeout(function() {
                    alert.fadeOut(function() {
                        $(this).remove();
                    });
                }, 5000); // Extended to 5 seconds for setup messages
            }

            // Play notification sound
            function playNotificationSound() {
                try {
                    const audio = new Audio(
                        'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG'
                    );
                    audio.play();
                } catch (e) {
                    console.log('Notification sound not available');
                }
            }

            // Toggle auto refresh
            $('#toggle-refresh').click(function() {
                isRefreshing = !isRefreshing;

                if (isRefreshing) {
                    $(this).html('<i class="fas fa-pause"></i> Pause');
                    startAutoRefresh();
                    showNotification('Auto refresh diaktifkan', 'info');
                } else {
                    $(this).html('<i class="fas fa-play"></i> Resume');
                    stopAutoRefresh();
                    showNotification('Auto refresh dinonaktifkan', 'warning');
                }
            });

            // Change interval
            $('#interval-select').change(function() {
                currentInterval = parseInt($(this).val()) * 1000;
                $('#refresh-interval').text($(this).val());
                $('#refresh-interval-mobile').text($(this).val());

                if (isRefreshing) {
                    stopAutoRefresh();
                    startAutoRefresh();
                    showNotification(`Interval diubah menjadi ${$(this).val()} detik`, 'info');
                }
            });

            // Manual refresh
            $('#manual-refresh').click(function() {
                $(this).html('<i class="fas fa-sync-alt fa-spin"></i> Refreshing...');
                loadOtpData();

                setTimeout(() => {
                    $(this).html('<i class="fas fa-sync-alt"></i> Refresh Manual');
                }, 1000);
            });

            // Start auto refresh
            function startAutoRefresh() {
                refreshInterval = setInterval(loadOtpData, currentInterval);
            }

            // Stop auto refresh
            function stopAutoRefresh() {
                if (refreshInterval) {
                    clearInterval(refreshInterval);
                }
            }

            // Load data pertama kali
            loadOtpData();

            // Mulai auto refresh
            startAutoRefresh();

            // Stop refresh saat user meninggalkan halaman
            $(window).on('beforeunload', function() {
                stopAutoRefresh();
            });
        });
    </script>
@endpush

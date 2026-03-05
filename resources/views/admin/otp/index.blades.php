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
                        <button class="btn btn-info btn-sm text-white" id="copy-all-otp" title="Copy semua OTP dari bawah ke atas">
                            <i class="fas fa-copy"></i> <span class="d-none d-md-inline">Copy All OTP</span>
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

            // Debug current environment
            const currentDomain = window.location.origin;
            const isProduction = currentDomain.includes('mbah.my.id') || currentDomain.includes('elnusatour.id');
            console.log('🌐 Current domain:', currentDomain);
            console.log('🏭 Is Production:', isProduction);

            // Set CSRF token dan konfigurasi AJAX untuk mengatasi CORS
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                    // Removed Content-Type to avoid preflight CORS
                },
                timeout: 30000, // 30 detik timeout
                cache: false,
                crossDomain: false, // Ensure same-origin requests
                // Tambahan untuk production
                xhrFields: {
                    withCredentials: false
                },
                beforeSend: function(xhr, settings) {
                    console.log('📤 AJAX Request:', {
                        url: settings.url,
                        method: settings.type,
                        contentType: settings.contentType
                    });
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

            // Fungsi untuk test konektivitas jaringan
            function checkNetworkConnection() {
                return new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();
                    xhr.timeout = 5000; // 5 detik timeout
                    xhr.onload = () => {
                        console.log('✅ Network test OK');
                        resolve(true);
                    };
                    xhr.onerror = () => {
                        console.error('❌ Network test failed');
                        reject(false);
                    };
                    xhr.ontimeout = () => {
                        console.warn('⏰ Network test timeout');
                        reject(false);
                    };

                    // Test dengan endpoint yang sama seperti aplikasi
                    const testUrl = isProduction ?
                        'https://kode.mbah.my.id/' :
                        window.location.origin + '/';

                    console.log('🔍 Testing network to:', testUrl);
                    xhr.open('HEAD', testUrl, true);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.send();
                });
            }

            // Helper function untuk URL yang correct di production
            function getApiUrl(endpoint) {
                let baseUrl;

                if (isProduction) {
                    // Paksa HTTPS untuk production dan detect domain yang benar
                    if (currentDomain.includes('mbah.my.id')) {
                        baseUrl = 'https://kode.mbah.my.id';
                    } else if (currentDomain.includes('elnusatour.id')) {
                        baseUrl = 'https://kode.elnusatour.id';
                    } else {
                        // Fallback paksa HTTPS
                        baseUrl = window.location.origin.replace('http:', 'https:');
                    }

                    // Untuk production, pastikan menggunakan HTTPS dan path yang benar
                    const fullUrl = `${baseUrl}${endpoint}`;
                    console.log(`🔗 Production URL: ${fullUrl}`);
                    return fullUrl;
                } else {
                    baseUrl = window.location.origin;

                    // Untuk local development, gunakan route Laravel yang sesuai dengan path baru
                    const routes = {
                        '/data': '{{ url('/data') }}',
                        '/test': '{{ url('/test') }}'
                    };

                    // Fallback jika route tidak tersedia
                    let url;
                    try {
                        url = routes[endpoint];
                        if (!url || url.includes('route(')) {
                            // Route tidak tersedia, gunakan manual URL
                            url = `${baseUrl}${endpoint}`;
                            console.warn(`⚠️ Route fallback used: ${url}`);
                        }
                    } catch (e) {
                        url = `${baseUrl}${endpoint}`;
                        console.warn(`⚠️ Route error, using manual URL: ${url}`);
                    }

                    console.log(`🔗 Development URL: ${url}`);
                    return url;
                }
            }

            // Force HTTPS di production
            if (isProduction && window.location.protocol !== 'https:') {
                console.warn('⚠️ Redirecting to HTTPS...');
                window.location.href = window.location.href.replace('http:', 'https:');
                return;
            }

            // Fungsi untuk memuat data OTP
            function loadOtpData(showLoader = false) {
                if (showLoader) {
                    $('#fetch-status').removeClass('bg-primary bg-success bg-danger').addClass('bg-warning');
                    $('#fetch-text').html('<span class="spinner-border spinner-border-sm me-1"></span>Loading...');
                }

                // Periksa konektivitas jaringan terlebih dahulu jika showLoader true
                if (showLoader) {
                    checkNetworkConnection()
                        .then(() => {
                            console.log('✓ Network connection OK');
                            performAjaxRequest();
                        })
                        .catch(() => {
                            console.warn('⚠️ Network connection issue');
                            $('#fetch-status').removeClass('bg-warning bg-success').addClass('bg-danger');
                            $('#fetch-text').text('No Connection');
                            showNotification('Tidak dapat terhubung ke server. Periksa koneksi internet Anda.',
                                'danger');
                        });
                } else {
                    performAjaxRequest();
                }

                function performAjaxRequest() {
                    const apiUrl = getApiUrl('/data');
                    console.log('📡 Making request to:', apiUrl);

                    // Gunakan fetch API yang lebih modern untuk menghindari CORS preflight
                    if (isProduction) {
                        fetch(apiUrl, {
                                method: 'GET',
                                credentials: 'same-origin',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                                }
                                return response.json();
                            })
                            .then(response => {
                                console.log('✓ Data received:', response);
                                handleSuccessResponse(response);
                            })
                            .catch(error => {
                                console.error('❌ Fetch Error:', error);
                                handleErrorResponse(error);
                            });
                    } else {
                        // Fallback ke jQuery AJAX untuk development
                        $.ajax({
                            url: apiUrl,
                            method: 'GET',
                            success: handleSuccessResponse,
                            error: function(xhr, status, error) {
                                handleErrorResponse({
                                    xhr,
                                    status,
                                    error
                                });
                            }
                        });
                    }

                    function handleSuccessResponse(response) {
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
                                    showNotification(
                                        `Fetch berhasil: ${response.message_count} pesan baru diproses, ${response.otp_count} OTP ditemukan (5 menit terakhir)`,
                                        'info');
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
                    }

                    function handleErrorResponse(errorData) {
                        // Handle both fetch API errors and jQuery AJAX errors
                        let xhr, status, error;

                        if (errorData.xhr) {
                            // jQuery AJAX error format
                            xhr = errorData.xhr;
                            status = errorData.status;
                            error = errorData.error;
                        } else {
                            // Fetch API error format
                            xhr = {
                                status: errorData.message?.includes('HTTP') ? parseInt(errorData.message.split(
                                    ' ')[1]) : 0,
                                statusText: errorData.message || 'Network Error',
                                responseText: '',
                                readyState: 4
                            };
                            status = 'error';
                            error = errorData.message || 'Fetch failed';
                        }

                        console.error('❌ Request Error:', {
                            status: xhr.status,
                            statusText: xhr.statusText,
                            responseText: xhr.responseText,
                            error: error,
                            readyState: xhr.readyState
                        });

                        $('#fetch-status').removeClass('bg-warning bg-success').addClass('bg-danger');
                        $('#fetch-text').text('Error');

                        let errorMessage = 'Gagal memuat data OTP';
                        let errorType = 'danger';

                        // Handle specific error types
                        if (xhr.status === 0) {
                            // Network error or CORS issue
                            if (status === 'error') {
                                const currentUrl = window.location.href;
                                const expectedUrl = isProduction ? 'https://kode.mbah.my.id' : 'http://localhost';

                                errorMessage = `
                                    <div>
                                        <strong>CORS/Network Error!</strong><br>
                                        <small>Current: ${currentUrl}</small><br>
                                        <small>Expected: ${expectedUrl}</small><br><br>
                                        Kemungkinan penyebab:<br>
                                        • Mixed Content (HTTP vs HTTPS)<br>
                                        • CORS Policy tidak dikonfigurasi<br>
                                        • Server tidak dapat diakses<br>
                                        • Firewall atau CDN blocking<br>
                                        <br>
                                        <button class="btn btn-sm btn-primary mt-2" onclick="location.reload()">
                                            <i class="fas fa-sync-alt"></i> Refresh Halaman
                                        </button>
                                        <a href="https://kode.mbah.my.id/" class="btn btn-sm btn-success mt-2 ms-2">
                                            <i class="fas fa-external-link-alt"></i> Direct Link
                                        </a>
                                    </div>
                                `;
                                errorType = 'warning';
                            }
                        } else if (xhr.status === 419) {
                            // CSRF Token expired
                            errorMessage = `
                                <div>
                                    <strong>Session Expired!</strong><br>
                                    Token CSRF telah kedaluwarsa.<br>
                                    <button class="btn btn-sm btn-primary mt-2" onclick="location.reload()">
                                        <i class="fas fa-sync-alt"></i> Refresh Halaman
                                    </button>
                                </div>
                            `;
                        } else if (xhr.status === 500) {
                            errorMessage = 'Server Error (500) - Periksa log server';
                        } else if (xhr.status === 404) {
                            errorMessage = 'Endpoint tidak ditemukan (404)';
                        } else if (xhr.status === 403) {
                            errorMessage = 'Akses ditolak (403) - Periksa permission';
                        }

                        // Check if setup is needed (only for valid responses)
                        if (xhr.responseText && xhr.responseText.trim()) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                console.log('Error response:', response);

                                if (response.setup_url) {
                                    const setupMsg = `
                                        <div>
                                            <strong>Database belum disetup!</strong><br>
                                            <a href="${response.setup_url}" target="_blank" class="alert-link btn btn-warning btn-sm mt-2">
                                                <i class="fas fa-database"></i> Setup Database Sekarang
                                            </a>
                                        </div>
                                    `;
                                    showNotification(setupMsg, 'warning');
                                    return;
                                } else if (response.error) {
                                    errorMessage = 'Server Error: ' + response.error;
                                }
                            } catch (e) {
                                // JSON parse error - responseText is not valid JSON
                                console.warn('Response is not valid JSON:', xhr.responseText.substring(0, 200));
                            }
                        }

                        showNotification(errorMessage, errorType);
                    }
                } // End performAjaxRequest function
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

                const testUrl = getApiUrl('/test');
                console.log('🧪 Testing API at:', testUrl);

                // Gunakan fetch API untuk production, jQuery untuk development
                if (isProduction) {
                    fetch(testUrl, {
                            method: 'GET',
                            credentials: 'same-origin',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                            }
                            return response.json();
                        })
                        .then(response => {
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
                        })
                        .catch(error => {
                            $('#api-test-result').show();
                            $('#api-test-content').text('Error: ' + error.message);
                            showNotification('API Test Error: ' + error.message, 'danger');
                        })
                        .finally(() => {
                            btn.prop('disabled', false).html(
                                '<i class="fas fa-flask"></i> <span class="d-none d-md-inline">Test API</span>'
                                );
                        });
                } else {
                    // Fallback jQuery untuk development
                    $.ajax({
                        url: testUrl,
                        method: 'GET',
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
                                '<i class="fas fa-flask"></i> <span class="d-none d-md-inline">Test API</span>'
                                );
                        }
                    });
                }
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

            // Copy semua OTP dari bawah ke atas
            $('#copy-all-otp').click(function() {
                const rows = $('#otp-table-body tr').get().reverse();
                const otpList = [];

                rows.forEach(function(row) {
                    const otpEl = $(row).find('.otp-code');
                    if (otpEl.length > 0) {
                        const otpText = otpEl.text().trim();
                        if (otpText && otpText !== '-') {
                            otpList.push(otpText);
                        }
                    }
                });

                if (otpList.length === 0) {
                    showNotification('Tidak ada data OTP untuk disalin.', 'warning');
                    return;
                }

                const textToCopy = otpList.join('\n');
                const btn = $(this);

                const doCopy = function(text) {
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(text).then(function() {
                            btn.html('<i class="fas fa-check"></i> <span class="d-none d-md-inline">Copied!</span>');
                            setTimeout(function() {
                                btn.html('<i class="fas fa-copy"></i> <span class="d-none d-md-inline">Copy All OTP</span>');
                            }, 2000);
                            showNotification(`${otpList.length} OTP berhasil disalin (bawah ke atas)!`, 'success');
                        }).catch(function() {
                            fallbackCopy(text);
                        });
                    } else {
                        fallbackCopy(text);
                    }
                };

                const fallbackCopy = function(text) {
                    const ta = document.createElement('textarea');
                    ta.value = text;
                    ta.style.position = 'fixed';
                    ta.style.left = '-9999px';
                    ta.style.top = '-9999px';
                    document.body.appendChild(ta);
                    ta.focus();
                    ta.select();
                    try {
                        document.execCommand('copy');
                        btn.html('<i class="fas fa-check"></i> <span class="d-none d-md-inline">Copied!</span>');
                        setTimeout(function() {
                            btn.html('<i class="fas fa-copy"></i> <span class="d-none d-md-inline">Copy All OTP</span>');
                        }, 2000);
                        showNotification(`${otpList.length} OTP berhasil disalin (bawah ke atas)!`, 'success');
                    } catch (e) {
                        showNotification('Gagal menyalin OTP. Coba manual.', 'danger');
                    }
                    document.body.removeChild(ta);
                };

                doCopy(textToCopy);
            });

            // Copy to clipboard dengan fallback
            window.copyToClipboard = function(text) {
                // Method 1: Modern Clipboard API
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(function() {
                        showNotification('OTP "' + text + '" berhasil disalin ke clipboard!',
                        'success');
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

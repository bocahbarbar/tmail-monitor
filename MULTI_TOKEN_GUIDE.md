# Multi Authorization Token System - MailTm

## 📋 Fitur yang Telah Ditambahkan

Sistem sekarang mendukung **multiple bearer tokens** dengan **auto-fetch account info dari API**.

### ✨ Fitur Utama:

1. **Auto-Fetch Account Info** ⚡
   - Cukup masukkan **Bearer Token** saja!
   - Email, domain, quota, dan info lainnya otomatis diambil dari API mail.tm
   - Tidak perlu input manual lagi

2. **Multi-Account Management**
   - Tambah, edit, hapus mail accounts
   - Setiap account punya bearer token sendiri
   - Toggle active/inactive untuk setiap account

3. **Real-time Account Monitoring**
   - Storage usage tracking (quota & used space)
   - Message count per account
   - Last fetch timestamp
   - Account status (active/disabled/deleted)

4. **Easy Refresh**
   - Button untuk refresh account info kapan saja
   - Auto-update saat edit token
   - Test connection untuk validasi

## 🚀 Cara Menggunakan

### 1. Akses Mail Accounts Management
```
http://localhost/MailTm/admin/mail-accounts
```

### 2. Tambah Account Baru (Super Simple!)

**Yang perlu Anda isi:**
1. **Nama**: Identifikasi account (contoh: "Main Account")
2. **Bearer Token**: Token dari mail.tm
3. **Notes** (opsional): Catatan tambahan

**Itu saja!** Email, domain, quota, dll akan otomatis terisi! 🎉

### 3. Cara Mendapatkan Bearer Token (Mudah!)

1. Buka https://mail.tm
2. Login atau buat account baru
3. Buka **Developer Tools** (tekan F12)
4. Buka tab **Network**
5. Refresh halaman atau klik email apapun
6. Cari request ke `api.mail.tm`
7. Di **Request Headers**, copy value dari `Authorization: Bearer ...`
8. Paste token (tanpa kata "Bearer") ke form
9. Klik simpan - **DONE!** Email dan info lain otomatis terisi 🎯

### 4. Refresh Account Info
- Klik button **<i class="fas fa-sync-alt"></i>** hijau untuk refresh account info
- Storage usage, quota, dll akan di-update dari API
- Berguna untuk cek space usage terkini

### 5. Monitoring Dashboard
- Menu **OTP Monitor** otomatis fetch dari semua account aktif
- Lihat storage usage di halaman **Mail Accounts**
- Progress bar merah jika storage > 80%

## 📊 Database Schema

### Table: `mail_accounts`
```sql
- id
- name (varchar) - Nama identifikasi
- email (varchar, unique) - Email address (auto-fetch)
- domain (varchar) - Domain email (auto-fetch)
- account_id (varchar) - ID dari mail.tm (auto-fetch)
- bearer_token (text) - Bearer token API
- quota (bigint) - Total quota dalam bytes (auto-fetch)
- used (bigint) - Space terpakai dalam bytes (auto-fetch)
- is_active (boolean) - Status aktif di sistem
- is_disabled (boolean) - Status disabled di mail.tm (auto-fetch)
- is_deleted (boolean) - Status deleted di mail.tm (auto-fetch)
- message_count (int) - Total pesan di-fetch
- last_fetch_at (timestamp) - Waktu terakhir fetch messages
- account_created_at (timestamp) - Waktu pembuatan di mail.tm (auto-fetch)
- account_updated_at (timestamp) - Waktu update di mail.tm (auto-fetch)
- notes (text) - Catatan tambahan
- created_at
- updated_at
```

**Field dengan (auto-fetch)** = otomatis diisi dari API `/me` endpoint

## 🔧 API Endpoints

### Mail Account Management
```
GET    /admin/mail-accounts                          - List semua accounts
GET    /ad & Features

1. **Super Simple Setup**: Hanya perlu nama & token, sisanya otomatis!
2. **Storage Monitoring**: Lihat usage dengan progress bar real-time
3. **Multiple Domains**: Bisa manage banyak domain email sekaligus
4. **Smart Refresh**: Auto-refresh saat edit token, atau manual refresh kapan saja
5. **Active Management**: Toggle on/off tanpa hapus account
6. **Storage Alerts**: Progress bar merah otomatis jika usage > 80%

## 🎯 Auto-Fetch Info dari API

Sistem otomatis memanggil endpoint `/me` dari mail.tm untuk mendapatkan:

```json
{
  "@id": "/me",
  "@type": "Account",
  "id": "68c26fd2e72e9a293103ff95",
  "address": "rhe@powerscrews.com",
  "quota": 40000000,
  "used": 2250651,
  "isDisabled": false,
  "isDeleted": false,
  "createdAt": "2025-09-11T06:44:34+00:00",
  "updatedAt": "2025-09-11T06:44:34+00:00"
}
```

Semua field ini disimpan otomatis ke database!
GET    /admin/mail-accounts/{id}/test-connection     - Test API connection
POST   /admin/mail-accounts/{id}/refresh-info        - Refresh account info dari API
```

### OTP Monitor (Existing)
```
GET    /admin/otp                        - Dashboard OTP Monitor
GET    /admin/otp/data                   - Fetch OTP data (auto multi-account)
GET    /admin/otp/test-api               - Test API
```

## 💡 Tips

1. **Multiple Domains**: Anda bisa menambahkan account dari berbagai domain mail.tm
2. **Active Management**: Nonaktifkan account yang tidak diperlukan tanpa menghapusnya
3. **Token Expiry**: Token mail.tm bisa expired, update token di halaman edit jika API error
4. **Stats Tracking**: Monitor performa setiap account melalui stats dashboard

## 🔄 Migration dari Single Token

Jika sebelumnya menggunakan single token di config, sistem masih support backward compatibility. Namun disarankan untuk:

1. Buat account baru di **Mail Accounts**
2. Copy token dari config ke account baru
3. Aktifkan account tersebut
4. Nonaktifkan/hapus token di config

## 🐛 Troubleshooting

### Token tidak valid
- Verify token masih aktif di mail.tm
- Gunakan fitur **Test Connection** untuk validasi
- Generate token baru jika expired

### Account tidak fetch data
- Check status **Active** di toggle switch
- Verify API connection dengan Test Connection
- Check logs untuk error messages

### Performance Issue
- Limit jumlah active accounts (5-10 optimal)
- Nonaktifkan account yang jarang digunakan
- Monitor stats untuk identifikasi account bermasalah

## 📝 Migration Files

File yang ditambahkan:
```
database/migrations/2026_01_26_004423_create_mail_accounts_table.php
app/Models/MailAccount.php
app/Http/Controllers/Admin/MailAccountController.php
resources/views/admin/mail-accounts/index.blade.php
resources/views/admin/mail-accounts/create.blade.php
resources/views/admin/mail-accounts/edit.blade.php
```

File yang dimodifikasi:
```
app/Http/Controllers/Admin/OtpController.php
routes/web.php
resources/views/layouts/admin.blade.php
```

# Dokumentasi Diagram Aplikasi Landing Page

Dokumen ini merangkum diagram proses, ERD, dan kardinalitas berdasarkan route, controller, model, dan migration pada project Laravel ini.

## 1. Diagram Proses Utama

### 1.1 Registrasi, Login, dan Dashboard User

```mermaid
flowchart TD
    A["Pengunjung membuka aplikasi"] --> B{"Sudah punya akun?"}
    B -- "Belum" --> C["Isi form register"]
    C --> D["Validasi nama, email, WhatsApp, password"]
    D --> E["Buat data users"]
    E --> F["Buat landing_pages default untuk user"]
    F --> G["Login otomatis"]
    B -- "Sudah" --> H["Isi form login"]
    H --> I["Validasi email, password, status aktif"]
    I --> J{"Role user"}
    G --> K["Dashboard user"]
    J -- "user" --> K
    J -- "admin" --> L["Panel admin"]
    K --> M["Edit profil landing page"]
    K --> N["Kelola link"]
    K --> O["Lihat analytics dan export"]
    M --> P["Simpan landing_pages"]
    N --> Q["Simpan landing_page_links"]
    O --> R["Baca landing_page_events"]
```

### 1.2 Proses Publik Landing Page dan Tracking

```mermaid
flowchart TD
    A["Visitor membuka /u/{slug} atau custom domain"] --> B["Resolve landing_pages"]
    B --> C{"Landing page aktif dan user aktif?"}
    C -- "Tidak" --> D["404"]
    C -- "Ya" --> E["Load activeLinks"]
    E --> F["Catat page_view ke landing_page_events"]
    F --> G["Tampilkan landing page publik"]
    G --> H{"Aksi visitor"}
    H -- "Klik CTA WhatsApp" --> I["Catat cta_click"]
    I --> J["Redirect ke wa.me"]
    H -- "Klik link eksternal" --> K["Validasi link milik landing page dan aktif"]
    K --> L{"URL valid?"}
    L -- "Tidak" --> D
    L -- "Ya" --> M["Catat link_click"]
    M --> N["Redirect ke URL tujuan"]
```

### 1.3 Proses Admin

```mermaid
flowchart TD
    A["Admin login"] --> B["Middleware auth"]
    B --> C["EnsureUserIsActive"]
    C --> D["EnsureUserIsAdmin"]
    D --> E{"Menu admin"}
    E -- "Kelola user" --> F["List, filter, tambah, edit, hapus user"]
    F --> G["Update users"]
    G --> H["Buat atau update landing_pages user"]
    E -- "Analytics komparasi" --> I["Ambil semua landing_pages"]
    I --> J["Hitung summary dari landing_page_events"]
    J --> K["Tampilkan ranking, total views, CTA, conversion"]
```

## 2. ERD

```mermaid
erDiagram
    USERS {
        bigint id PK
        string name
        string email UK
        timestamp email_verified_at
        string password
        string role
        boolean is_active
        string remember_token
        timestamp created_at
        timestamp updated_at
    }

    LANDING_PAGES {
        bigint id PK
        bigint user_id FK,UK
        string slug UK
        string title
        string headline
        text bio
        string avatar_url
        string avatar_path
        string whatsapp_number
        string whatsapp_message
        string cta_label
        string theme
        boolean is_active
        string custom_domain UK
        timestamp custom_domain_connected_at
        string custom_domain_dns_status
        string custom_domain_dns_target
        timestamp custom_domain_dns_checked_at
        string custom_domain_dns_message
        string custom_domain_ssl_status
        string custom_domain_ssl_issuer
        timestamp custom_domain_ssl_expires_at
        timestamp custom_domain_ssl_checked_at
        string custom_domain_ssl_message
        timestamp created_at
        timestamp updated_at
    }

    LANDING_PAGE_LINKS {
        bigint id PK
        bigint landing_page_id FK
        string label
        string description
        string url
        int sort_order
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    LANDING_PAGE_EVENTS {
        bigint id PK
        bigint landing_page_id FK
        bigint landing_page_link_id FK "nullable"
        string event_type
        string session_id
        string ip_address
        text user_agent
        string referrer
        string clicked_url
        timestamp created_at
        timestamp updated_at
    }

    SESSIONS {
        string id PK
        bigint user_id FK "nullable"
        string ip_address
        text user_agent
        longtext payload
        int last_activity
    }

    PASSWORD_RESET_TOKENS {
        string email PK
        string token
        timestamp created_at
    }

    USERS ||--o| LANDING_PAGES : "memiliki"
    LANDING_PAGES ||--o{ LANDING_PAGE_LINKS : "memiliki"
    LANDING_PAGES ||--o{ LANDING_PAGE_EVENTS : "mencatat"
    LANDING_PAGE_LINKS ||--o{ LANDING_PAGE_EVENTS : "diklik pada"
    USERS ||--o{ SESSIONS : "login pada"
```

## 3. Kardinalitas

| Relasi | Kardinalitas | Dasar implementasi | Catatan |
| --- | --- | --- | --- |
| `users` ke `landing_pages` | 1 : 0..1 | `landing_pages.user_id` adalah FK dan `unique` | Satu user maksimal memiliki satu landing page. Landing page wajib punya satu user. |
| `landing_pages` ke `landing_page_links` | 1 : 0..N | `landing_page_links.landing_page_id` FK | Satu landing page dapat memiliki banyak link. Link dihapus otomatis saat landing page dihapus. |
| `landing_pages` ke `landing_page_events` | 1 : 0..N | `landing_page_events.landing_page_id` FK | Semua event wajib terkait satu landing page. Event dihapus otomatis saat landing page dihapus. |
| `landing_page_links` ke `landing_page_events` | 1 : 0..N, event 0..1 link | `landing_page_events.landing_page_link_id` nullable FK | `page_view` dan `cta_click` tidak memakai link. `link_click` memakai link. Jika link dihapus, FK event menjadi `NULL`. |
| `users` ke `sessions` | 1 : 0..N, session 0..1 user | `sessions.user_id` nullable index | Session bisa milik user login atau guest. |
| `password_reset_tokens` ke `users` | Referensi logis via email | `password_reset_tokens.email` primary key | Tidak ada foreign key eksplisit ke `users.email`. |

## 4. Ringkasan Entitas

| Entitas | Fungsi |
| --- | --- |
| `users` | Menyimpan akun, role (`admin` atau `user`), dan status aktif. |
| `landing_pages` | Profil landing page milik user, termasuk slug, tema, WhatsApp, avatar, status aktif, dan custom domain. |
| `landing_page_links` | Daftar link eksternal yang tampil pada landing page publik. |
| `landing_page_events` | Log analytics untuk `page_view`, `cta_click`, dan `link_click`. |
| `sessions` | Penyimpanan session Laravel. |
| `password_reset_tokens` | Token reset password Laravel. |

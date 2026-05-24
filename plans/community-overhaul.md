# Community Tab Overhaul Plan

## Overview
Three major improvements to the community tab in StepUp LMS dashboard.

---

## 1. Toolbar Active Tab — Blue Pill Indicator

### Problem
- Initial HTML for Diskusi button uses `bg-blue-600 text-white` (blue filled pill)
- But `swCommTab()` JS applies `bg-white text-blue-600` for active state (white pill with blue text)
- Mismatch: first load looks different from after clicking

### Fix
- Unify to **blue filled pill** style: active = `bg-blue-600 text-white shadow-lg shadow-blue-500/20`
- Inactive = `text-slate-400 hover:text-blue-500` (no bg)
- Update `swCommTab()` in `dashboard.php` to add/remove blue pill classes
- Update initial HTML in `inc_tab_community.php` to match

### Files
- `src/views/components/inc_tab_community.php` — line 91 initial Diskusi button classes
- `src/views/dashboard.php` — `swCommTab()` ~line 2453-2461

---

## 2. Video Panel Light Mode Colors

### Problem
- `comm-panel-video` has `bg-slate-950` hardcoded — always dark even in light mode
- User wants: light mode = light background, dark mode only = dark background

### Fix
- Change `bg-slate-950` → `bg-slate-100 dark:bg-slate-950`
- Update `videoJoinOverlay` backdrop: `bg-slate-950/80` → `bg-slate-200/90 dark:bg-slate-950/80`
- Update join overlay card: `bg-white/5` → `bg-white dark:bg-white/5`
- Update text colors to be dark/light mode aware
- Update control bar gradient for light mode

### Files
- `src/views/components/inc_tab_community.php` — lines 171-232

---

## 3. Info Tab — Members Only with Role Badges

### Problem
- Info tab currently shows: avatar, name, description, visi, misi, member list
- User wants: Info tab should ONLY show member list with role badges

### Fix
- Strip Info panel HTML to only the member list section
- Add 3 role badges: Owner (gold), Admin (blue), Member (slate)
- Show profile pic, name, role badge, joined date
- Keep `loadCommDetails()` JS to populate member data

### Files
- `src/views/components/inc_tab_community.php` — lines 142-168
- `src/views/dashboard.php` — `loadCommDetails()` ~line 2497

---

## 4. Settings Popup — 20+ Community Management Features

The settings gear opens a Swal.fire popup. Currently has: member list, restrict chat toggle, leave, delete. 

### New Feature Categories & Items (organized in tabs within the popup)

#### A. UMUM / General (6 features)
1. **Edit Nama Komunitas** — change community name (owner only)
2. **Edit Deskripsi** — change description (owner/admin)
3. **Edit Visi & Misi** — change vision/mission (owner/admin)
4. **Ubah Avatar** — change community avatar/icon color (owner/admin)
5. **Privasi Komunitas** — toggle public/private (owner only)
6. **Kode Undangan** — generate/reset invite code for private communities (owner/admin)

#### B. ANGGOTA / Members (5 features)
7. **Daftar Anggota** — view all members with roles (existing)
8. **Ubah Role** — change member role: owner→admin→member (existing, owner only)
9. **Keluarkan Anggota** — kick member (existing, owner/admin)
10. **Ban Anggota** — ban user from rejoining (owner/admin)
11. **Undang Anggota** — invite by username search (owner/admin)

#### C. DISKUSI / Chat (4 features)
12. **Batasi Chat** — only admin/owner can chat (existing toggle)
13. **Slowmode** — set cooldown between messages: off/10s/30s/1m/5m (owner/admin)
14. **Hapus Semua Pesan** — clear all messages (owner only)
15. **Pin Pesan** — pin important message to top (owner/admin) [future]

#### D. VIDEO ROOM (3 features)
16. **Nonaktifkan Video Room** — disable video room entirely (owner only)
17. **Batas Peserta Video** — max participants: 2/4/6/8/unlimited (owner only)
18. **Hanya Admin Mulai Video** — restrict who can initiate video (owner only)

#### E. NOTIFIKASI / Notifications (2 features)
19. **Notifikasi Pesan Baru** — toggle notification for new messages (per-user)
20. **Notifikasi Anggota Baru** — notify when someone joins (per-user)

#### F. BAHAYA / Danger Zone (3 features)
21. **Transfer Kepemilikan** — transfer owner role to another member (owner only)
22. **Keluar Komunitas** — leave community (existing)
23. **Hapus Komunitas** — delete permanently (existing, owner only)

### Implementation Architecture

The Swal popup will use a **tabbed layout** inside the popup body:

```
┌─────────────────────────────────────┐
│ ⚙ Pengaturan Komunitas         [X] │
├─────────────────────────────────────┤
│ [Umum] [Anggota] [Diskusi]          │
│ [Video] [Notifikasi] [Bahaya]       │
├─────────────────────────────────────┤
│                                     │
│  (content of selected settings tab) │
│                                     │
└─────────────────────────────────────┘
```

### Database Changes Required

```sql
ALTER TABLE communities 
  ADD COLUMN IF NOT EXISTS chat_disabled TINYINT(1) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS slowmode_seconds INT DEFAULT 0,
  ADD COLUMN IF NOT EXISTS video_disabled TINYINT(1) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS video_max_participants INT DEFAULT 0,
  ADD COLUMN IF NOT EXISTS video_admin_only TINYINT(1) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS invite_code VARCHAR(20) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS vision TEXT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS mission TEXT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS avatar_color VARCHAR(20) DEFAULT 'blue';

ALTER TABLE community_members
  ADD COLUMN IF NOT EXISTS is_banned TINYINT(1) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS notify_messages TINYINT(1) DEFAULT 1,
  ADD COLUMN IF NOT EXISTS notify_members TINYINT(1) DEFAULT 1;
```

### New API Endpoints (set_preference.php)

| Action | Method | Params | Auth |
|--------|--------|--------|------|
| `update_community_info` | POST | community_id, name, description, vision, mission | owner/admin |
| `update_community_privacy` | POST | community_id, privacy | owner |
| `generate_invite_code` | POST | community_id | owner/admin |
| `join_by_invite` | POST | invite_code | any user |
| `ban_community_member` | POST | community_id, user_id | owner/admin |
| `unban_community_member` | POST | community_id, user_id | owner/admin |
| `invite_member` | POST | community_id, username | owner/admin |
| `set_community_slowmode` | POST | community_id, seconds | owner/admin |
| `clear_community_messages` | POST | community_id | owner |
| `update_video_settings` | POST | community_id, disabled, max, admin_only | owner |
| `update_notification_prefs` | POST | community_id, notify_messages, notify_members | self |
| `transfer_ownership` | POST | community_id, new_owner_id | owner |

### Files to Modify

1. **`sql/schema.sql`** — add new columns
2. **`src/views/set_preference.php`** — add 12 new API actions
3. **`src/views/dashboard.php`** — rewrite `openCommSettings()` with tabbed Swal popup
4. **`src/views/components/inc_tab_community.php`** — video panel colors, info tab, toolbar tabs

---

## Execution Order

1. DB migration (add columns)
2. Fix toolbar active tab styling
3. Fix video panel light/dark mode colors
4. Redesign Info tab (members-only)
5. Add API endpoints to set_preference.php
6. Rewrite openCommSettings() with 20+ features in tabbed Swal popup
7. Test everything

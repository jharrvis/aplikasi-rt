# Plan: Advanced Bot Features

## Overview
Enhance the bot with fuzzy name matching, duplicate validation, correction flow, empty report support, and recap functionality.

## 1. Fuzzy Name Matching
*   **Problem**: Typos like "Yulian" -> "Julian" cause mismatches.
*   **Solution**: Use Levenshtein Distance.
*   **Logic**:
    1.  Get all Warga from DB.
    2.  For each input name, calculate distance to all DB names.
    3.  Select closest match IF `distance <= 3` (adjustable threshold).
    4.  Log the match decision.

## 2. Validation & Correction
*   **Requirement**:
    1.  Reject duplicate report for same Warga + Date.
    2.  Allow "Koreksi" to overwrite.
*   **Logic**:
    *   **Normal Lapor**: Check `exists(warga_id, date)`.
        *   If exists: Reply "Sudah ada data Rp XXX. Ketik 'Koreksi [Nama] [Nominal]' untuk ubah."
        *   If not: Create new.
    *   **Koreksi**: Check `exists(warga_id, date)`.
        *   If exists: `update(nominal)`. Reply "Berhasil dikoreksi ✅".
        *   If not: Create new (treat as late report).

## 3. Empty Reports ("Kosong")
*   **Requirement**: Support "Luther kosong" or " Luther 0".
*   **Logic**:
    *   AI Prompt must extract `amount: 0` for keyword "kosong".
    *   Backend accepts `0` as valid nominal.

## 4. Recap Functionality
*   **Requirement**: "Rekap hari ini", "Rekap bulan ini".
*   **Logic**:
    *   **Prompt**: Identify intent `rekap`, extract `period` (today/month).
    *   **Query**: `sum('nominal')` filtered by date range.
    *   **Reply**: "Total Jimpitian [Hari/Bulan] ini: Rp [Total]".

## Technical Changes

### `App\Services\OpenRouterService.php`
*   **Prompt Update**:
    *   Add instruction for "Koreksi" intent -> return `type: 'correction'`.
    *   Add instruction for "Kosong" -> return `amount: 0`.
    *   Add instruction for "Rekap" -> return `type: 'rekap'`.

### `App\Http\Controllers\WebhookController.php`
*   **Refactor `handleReport`**:
    *   Add `$isCorrection` flag.
    *   Implement Duplicate Check.
*   **Helper `findWarga($name)`**:
    *   Implement Levenshtein logic here.
*   **Handle `Recap`**:
    *   New case in switch/if logic.

## Verification
1.  **Fuzzy**: Send "Lapor Yulian 2000" -> Expect success (Pak Julian).
2.  **Duplicate**: Send "Lapor Julian 2000" again -> Expect "Sudah ada...".
3.  **Correction**: Send "Koreksi Julian 5000" -> Expect "Berhasil dikoreksi...".
4.  **Zero**: Send "Lapor Julian kosong" -> Expect "Rp 0".
5.  **Recap**: Send "Rekap hari ini" -> Expect total sum.

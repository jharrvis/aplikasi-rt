# Plan: Fuzzy Match & Recap Feature

## Goal
1.  **Improve Name Matching**: User reported "Julian" vs "Yulian" fails. We will implement `levenshtein` distance to find the closest match in the `wargas` table.
2.  **Add Recap Capability**: Users want to ask "rekap hari ini" or "rekap bulan ini" to see total collections.

## Proposed Changes

### 1. `App\Services\OpenRouterService.php`
*   **Prompt Engineering**: Update the system prompt to recognize "rekap" intent.
*   **Output Format**: Add a new JSON output type: `{"type": "rekap", "period": "daily|monthly|all"}`.

### 2. `App\Http\Controllers\WebhookController.php`
*   **Fuzzy Logic**:
    *   Fetch all warga data (cache it if possible, but for 21 records `all()` is fine).
    *   Iterate through all warga names.
    *   Calculate `levenshtein($inputName, $dbName)`.
    *   If shortest distance is small (e.g., < 3 or < 30% of length), accept it.
*   **Recap Logic**:
    *   Handle `type: rekap`.
    *   Query `TransaksiJimpitian` based on `period`.
    *   Sum `nominal`.
    *   Group by `warga_id` (optional, for detailed recap).
    *   Format response string.

## Verification Plan

### Automated Test?
*   We can create a unit test for the "Find Warga Logic" to ensure "Yulian" matches "Pak Julian".

### Manual Verification
1.  **Fuzzy Test**:
    *   User sends: "Lapor joko 1000" (Exact match)
    *   User sends: "Lapor yulian 1000" (Should match "Pak Julian")
    *   User sends: "Lapor dela 1000" (Should match "Mbak Della")
2.  **Recap Test**:
    *   User sends: "Rekap hari ini"
    *   User sends: "Rekap bulan ini"
    *   Verify totals match the DB seeded data + reported data.

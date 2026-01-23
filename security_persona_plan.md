# Plan: Security, Persona & Detailed Recap

## 1. Updates to Persona & NLU (`OpenRouterService.php`)

### Goal
*   **Persona**: Javanese (Mixing Halus/Ngoko), funny/witty ("banyol").
*   **New Intents**:
    *   `check_stats`: "Berapa total Joko?"
    *   Enhanced `rekap`: "Rekap detail hari ini".

### Prompt Strategy
Update the `System Instruction` with:
> "Gunakan bahasa Indonesia campur Jawa ( santai, sedikit lucu/banyol). Posisikan dirimu sebagai Pak RT yang gaul."
> "Jika user tanya statistik 'Joko bulan ini', 'Berapa jimpitan Budi?', return type: 'query_stats'."

## 2. Updates to Logic (`WebhookController.php`)

### A. Detailed Recap
*   **Function**: `handleRecap`.
*   **Change**:
    *   Instead of just `count()` and `sum()`, fetch rows: `get()`.
    *   Loop results to build a list:
        > 1. Pak Joko: Rp 1.000
        > 2. Bu Endang: Rp 2.000
        > Total: Rp 3.000 (2 Rumah)
        > "Maturnuwun sadayana, mugi berkah!"

### B. Security (Restriction)
*   User Request: "Security agar tidak mudah diedit".
*   **Restrictions**:
    1.  **Time Limit**: Reports can only be corrected/edited on the **same day** (`created_at is today`).
    2.  **Auth (Simple)**:
        *   Ideally, we match `message.sender` with `transaksi.pelapor_id`.
        *   **Phone Normalization**: Since DB has `+62` or `08` and WA sends `628`, we must normalize both to standard `628` format before comparing.
        *   Function: `normalizePhoneNumber($phone)`.
        *   Strictly enforce **Time Limit** (Today Only).
        *   Check if verified sender exists in `wargas`.

### C. Specific Queries
*   **Function**: `handleQueryStats`.
*   **Logic**:
    *   Parse `name` and `period` from AI.
    *   Fuzzy match the name.
    *   Query `sum` for that `warga_id`.
    *   Reply: "Mas Joko bulan ini total Rp 50.000. Rajin tenan panjenengan!"

## 3. Database Changes
*   Ensure `pelapor_id` is filled in `TransaksiJimpitian`. (Already exists in schema, just need to fill it).

## Verification Plan

### Manual Test Scenarios
1.  **Recap Detail**:
    *   Input: "Rekap hari ini"
    *   Expect: List of names and amounts + Total + Funny Javanese closing.
2.  **Query Stats**:
    *   Input: "Berapa total Joko bulan ini?"
    *   Expect: "Joko total Rp X.XXX".
3.  **Security**:
    *   Try to edit yesterday's report (Mock data/date).
    *   Expect: "Waduh, sampun telat mboten saged diedit. Lapor Pak RT mawon nggih."

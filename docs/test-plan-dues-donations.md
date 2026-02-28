# Test Plan: Extended Dues & Donation System

**Date:** 2026-02-28
**Scope:** Couple payments, pledge-based cycles, non-monetary item donations, item fulfillment
**Roles tested:** Financial Secretary / Admin (Part I), Member (Part II)

**Test data:**
- Spouse pairs: Chukwuemeka Obi ↔ Ngozi Obi, Ngozi Eze ↔ Emeka Nwosu, Ikechi Oguru ↔ Justina Oguru
- Cycles: Annual Dues 2026 (couple_shared=true), Annual Dues 2025 (couple_shared=false), iriji2026 (event_levy)

---

## Prerequisites

- [ ] All 4 migrations applied (`php artisan migrate` shows them as DONE)
- [ ] Logged in as a Financial Secretary or Admin account for Part I
- [ ] Member accounts available for Part II

---

# PART I — Admin / Financial Secretary Activities

---

## A. Dues Cycle Management

### A1 — Create a standard (non-pledge, non-item) cycle

1. Go to **Admin → Dues Cycles → Create**
2. Fill in: Title = "Test Levy 2026", Type = Event Levy, Amount = 50.00, Dates = any range, Status = Active
3. Leave "Pledge-based" and "Accepts item donations" **unticked**
4. Save
5. **Expected:** Cycle appears in index. Show page has no "Manage Pledges" or "Donation Items" buttons. No badges for pledge or items.

### A2 — Create a couple_shared cycle

1. Create a new cycle: Title = "Annual Dues 2027", Type = Yearly Dues, Amount = 100.00
2. Tick **"Couple shared"**
3. Save
4. **Expected:** Show page shows a "Couple shared" badge

### A3 — Edit cycle retains all flags

1. Edit **Annual Dues 2026**
2. **Expected:** couple_shared = ticked, is_pledge_based = unticked, accepts_items = unticked
3. Save without changes
4. **Expected:** No values changed, success redirect

---

## B. Couple Payment at Recording Time

### B1 — Checkbox hidden for couple_shared cycles

1. Go to **Admin → Record Payment** (`/admin/payments/create`)
2. Search for and select **Chukwuemeka Obi** (has a spouse)
3. Select **Annual Dues 2026** (couple_shared = true)
4. **Expected:** The "Pay for Spouse too" checkbox does **not** appear
5. **Expected:** Obligation hint shows "Couple rate: £[amount]"

### B2 — Checkbox appears for non-couple_shared cycles with spouse

1. On the same form, keep **Chukwuemeka Obi** selected
2. Switch cycle to **iriji2026** (couple_shared = false)
3. **Expected:** A "Pay for Spouse too" checkbox appears
4. **Expected:** The hint reads: "This will also create a £[amount] record for Ngozi Obi."

### B3 — Checkbox NOT shown for members without a spouse

1. Select a member with no spouse (e.g. **Uche Ikechukwu**)
2. Select **iriji2026**
3. **Expected:** No "Pay for Spouse too" checkbox appears

### B4 — Couple payment creates two linked records

1. Select **Chukwuemeka Obi**, cycle **iriji2026**
2. Tick "Pay for Spouse too"
3. Enter a date, submit the form
4. **Expected:** Success message reads "Payment recorded for member and spouse."
5. Go to **Admin → Payments list**
6. **Expected:** Two rows — one for Chukwuemeka Obi, one for Ngozi Obi — with the same amount and date
7. Click into either payment row → **Expected:** The payment show page does not error
8. Verify `linked_payment_id` in the database:
   ```
   php artisan tinker --execute="App\Models\Payment::latest()->take(2)->get(['id','user_id','amount','linked_payment_id'])->each(fn(\$p) => print_r(\$p->toArray()));"
   ```

### B5 — Individual payment still works

1. Select **Ngozi Eze**, cycle **Annual Dues 2025** (couple_shared=false, member has spouse)
2. Do **not** tick "Pay for Spouse"
3. Submit
4. **Expected:** Only one payment record created (for Ngozi Eze only)

---

## C. Pledge-Based Donation Cycles

### C1 — Create a pledge-based cycle

1. Go to **Admin → Dues Cycles → Create**
2. Fill in: Title = "Harvest 2026 Fund", Type = Donation, Amount = 1.00 (placeholder), Dates = any future range, Status = Active
3. Tick **"Pledge-based"** — leave "Accepts item donations" unticked
4. Save
5. **Expected:** Show page shows a "Pledge-based" badge
6. **Expected:** A **"Manage Pledges"** button appears in the action bar
7. **Expected:** No separate "Donation Items" button in the action bar

### C2 — Record a pledge for a member

1. From the cycle show page, click **"Manage Pledges"**
2. Search for and select **Chukwuemeka Obi**, enter Pledge Amount = 150.00, optionally add Notes = "Pledged at Feb meeting", save
3. **Expected:** Pledge appears in the "Pledged Amounts" table with £150.00
4. **Expected:** Member Contributions summary table shows Chukwuemeka Obi with Pledged = £150.00, Paid = —, Balance = £150.00

### C3 — Record another pledge

1. On the same contributions page, select **Ngozi Eze**, enter Pledge Amount = 200.00, save
2. **Expected:** Pledged Amounts table footer shows Total Pledged = £350.00

### C4 — Update existing pledge (upsert)

1. On the same page, select **Chukwuemeka Obi** again, enter £175.00, save
2. **Expected:** The existing pledge is updated to £175.00 (not a duplicate row)
3. **Expected:** Totals update accordingly

### C5 — Payment form pre-fills from pledge

1. Go to **Admin → Record Payment**
2. Select **Chukwuemeka Obi**
3. Select the **"Harvest 2026 Fund"** cycle
4. **Expected:** Amount field is pre-filled with **£175.00** (Chukwuemeka's pledge)
5. **Expected:** Hint reads "Pledge on record: £175.00"

### C6 — Payment form with no pledge recorded

1. Select a member with no pledge on this cycle (e.g. **Uche Ikechukwu**)
2. Select **"Harvest 2026 Fund"**
3. **Expected:** Hint reads "No pledge recorded for this member — enter amount manually."
4. **Expected:** Amount field is **not** pre-filled

### C7 — Payment redeems pledge balance

1. Record a payment: **Chukwuemeka Obi**, "Harvest 2026 Fund", £100.00
2. Go to **Manage Pledges** for "Harvest 2026 Fund"
3. **Expected:** Chukwuemeka's row shows Pledged = £175.00, Redeemed = £100.00, Balance = £75.00
4. **Expected:** Member Contributions summary shows the same figures

### C8 — Pledge fully redeemed shows "Settled"

1. Record another payment: **Chukwuemeka Obi**, "Harvest 2026 Fund", £75.00
2. **Expected:** Chukwuemeka's row in the pledges table shows **"Settled"** badge
3. **Expected:** Member Contributions summary shows **"Settled"** badge

### C9 — Delete a pledge

1. As FS, go to **Manage Pledges** for "Harvest 2026 Fund"
2. Click the delete button for **Ngozi Eze's** pledge, confirm
3. **Expected:** Pledge is removed; totals update; no error

---

## D. Non-Monetary Item Donations (items-only cycle)

### D1 — Create a cycle that accepts items (not pledge-based)

1. Go to **Admin → Dues Cycles → Create**
2. Fill in: Title = "December Party Contributions", Type = Donation, Status = Active
3. Tick **"Accepts item donations"** — leave "Pledge-based" unticked
4. Save
5. **Expected:** Show page displays "Accepts item donations" badge
6. **Expected:** A **"Donation Items"** button appears in the action bar (separate from Manage Pledges)

### D2 — Record an "Other" type item via the standalone form

1. From the cycle show page, click **"Donation Items"** → **"Record Item"**
2. Select **Ngozi Eze**
3. Type = **Other (food, drinks, etc.)**, Description = "2 crates of Guinness", Quantity = "2 crates", Est. Value = 60.00, Date = today
4. Submit
5. **Expected:** Redirected to items list; row shows Ngozi Eze, "Other" badge, description, quantity, £60.00, Status = **Pending**

### D3 — Record a "Money" type item

1. Click "Record Item" again
2. Select **Emeka Nwosu**
3. Type = **Money (cash)**, Description = "Cash contribution", Est. Value = 50.00, Date = today
4. Quantity and notes optional — leave blank
5. Submit
6. **Expected:** Row shows "Money" badge in green, £50.00

### D4 — Item without estimated value

1. Record: **Uche Ikechukwu**, Type = Other, Description = "Bag of rice", Quantity = "1 bag", no estimated value
2. Submit
3. **Expected:** Row shows "—" in the Est. Value column (not an error)

### D5 — Mark item as received (fulfillment toggle)

1. On the donation items index, click the circle/toggle button on the "2 crates of Guinness" row
2. **Expected:** Status changes from **Pending** to **Received**, icon changes to a filled check
3. Click the button again
4. **Expected:** Status reverts to **Pending**

### D6 — Delete a donation item

1. On the donation items index, click the delete button for the "2 crates of Guinness" item, confirm
2. **Expected:** Item is removed; list updates; no error

### D7 — Cycle show page displays donation items card

1. Go to the "December Party Contributions" show page
2. **Expected:** A "Donation Items" card is shown at the bottom with the items table and an "Add Item" button
3. Delete all items and revisit
4. **Expected:** Card shows "No donation items recorded yet." with a "Record Item" button

---

## E. Combined Pledge + Item Cycle

> A cycle can have **both** `is_pledge_based` AND `accepts_items` set. In this case, items are managed from within the **Contributions** page (not a separate Donation Items button).

### E1 — Create a combined cycle

1. Go to **Admin → Dues Cycles → Create**
2. Title = "Harvest 2026 Combined", Type = Donation, Status = Active
3. Tick **both** "Pledge-based" **and** "Accepts item donations"
4. Save
5. **Expected:** Show page shows **both** "Pledge-based" and "Accepts item donations" badges
6. **Expected:** Only **"Manage Pledges"** button appears in the action bar — **no** separate "Donation Items" button

### E2 — Record pledge and item together from the contributions page

1. Click **"Manage Pledges"**
2. Select **Chukwuemeka Obi**, Pledge Amount = 100.00
3. Click **"Add Item"** in the form, fill in: Type = Physical item, Description = "2 bottles of wine", Quantity = "2"
4. Save
5. **Expected:** Money pledges table shows Chukwuemeka Obi with £100.00
6. **Expected:** Item contributions table shows Chukwuemeka Obi with "2 bottles of wine", Status = Pending

### E3 — Item fulfillment on combined contributions page

1. On the contributions page for "Harvest 2026 Combined", click the toggle button on Chukwuemeka's item row
2. **Expected:** Item status changes to **Received**

### E4 — Member Contributions summary reflects both pledge and item

1. On the contributions page, check the Member Contributions summary table
2. **Expected:** Chukwuemeka Obi row shows Pledged = £100.00, Paid = —, Balance = £100.00, and the item listed in the Items column with a pending/received indicator

---

# PART II — Member Activities

---

## F. Member Dashboard

### F1 — Standard cycle card (non-pledge)

1. Log in as a member enrolled in **Annual Dues 2026** (couple_shared cycle)
2. Go to the dashboard
3. **Expected:** Cycle card shows title, date range, progress bar
4. **Expected:** Shows "Paid: £X.XX / £Y.YY" format
5. **Expected:** If unpaid, online payment buttons ("Pay by Card", "Pay via Paystack") are visible
6. **Expected:** If fully paid, "Fully Paid" badge is shown instead of payment buttons

### F2 — Pledge-based cycle card — pledge recorded

1. Log in as **Chukwuemeka Obi**
2. Go to the dashboard (ensure "Harvest 2026 Fund" is active and he has a pledge of £175.00)
3. **Expected:** Cycle card shows: "Pledge: £175.00 · Paid: £X.XX"
4. **Expected:** No online payment buttons shown (pledge-based cycles are paid in person)

### F3 — Pledge-based cycle card — no pledge recorded

1. Log in as a member with no pledge on "Harvest 2026 Fund"
2. Go to the dashboard
3. **Expected:** Cycle card shows: "No pledge recorded yet · Paid: £0.00"
4. **Expected:** No online payment buttons shown

### F4 — Outstanding balance stat card

1. On the member dashboard
2. **Expected:** The "Outstanding" stat card reflects the sum of all remaining obligations across active cycles
3. For pledge-based cycles with no pledge, obligation = £0.00 (no contribution to outstanding)

### F5 — Recent Payments list

1. On the member dashboard
2. **Expected:** Recent payments list shows up to 5 recent payments with cycle name, method, date, amount, and status badge
3. Click **"All"** → **Expected:** Navigates to the full payments history page

### F6 — My Item Contributions card appears when items exist

1. Log in as **Ngozi Eze** (has a donation item: "2 crates of Guinness" for "December Party Contributions")
2. Go to the dashboard
3. **Expected:** A "My Item Contributions" card appears below the main row
4. **Expected:** Row shows description, cycle title, quantity, date, and estimated value badge
5. **Expected:** "Money" type items show a green badge; "Other" type shows grey

### F7 — My Item Contributions card hidden when no items

1. Log in as a member with no donation items
2. Go to the dashboard
3. **Expected:** The "My Item Contributions" card is **not** shown at all

### F8 — "Active Cycles" stat card count

1. On the dashboard
2. **Expected:** "Active Cycles" count matches the number of active dues cycles in the system

---

## G. Member Online Payment

### G1 — Pay by Card navigates to Stripe checkout

1. As a member with an outstanding balance, click **"Pay by Card"** on a cycle card
2. **Expected:** Browser navigates to a Stripe Checkout page for the correct amount

### G2 — Pay via Paystack initiates payment

1. As a member with an outstanding balance, click **"Pay via Paystack"**
2. **Expected:** Redirects to the Paystack payment page

### G3 — Buttons hidden after full payment

1. After completing a payment that clears the balance
2. **Expected:** Cycle card shows "Fully Paid" badge — payment buttons no longer appear

### G4 — Buttons hidden for pledge-based cycles

1. As a member on a pledge-based cycle
2. **Expected:** No "Pay by Card" or "Pay via Paystack" buttons regardless of paid amount

---

# PART III — Regression Tests

### R1 — couple_shared cycle obligation unchanged

1. Log in as **Chukwuemeka Obi** (has spouse)
2. Dashboard → **Annual Dues 2026** (couple_shared)
3. **Expected:** Shows couple rate (full amount) as obligation — same as before

### R2 — Single member on couple_shared cycle shows half amount

1. Log in as a member with no spouse
2. Dashboard → **Annual Dues 2026**
3. **Expected:** Shows half the cycle amount as obligation

### R3 — Regular individual payment still works correctly

1. As FS, record a payment for **Adaeze Okafor** on **Annual Dues 2025** (no spouse checkbox visible)
2. Submit
3. **Expected:** Single payment record created, success message, payment appears in list

### R4 — Cycle show page — pledge-based obligation in member matrix

1. As admin, view the "Harvest 2026 Fund" show page
2. **Expected:** Members with pledges show their pledge amount as "Obligation"
3. **Expected:** Members with no pledge show £0.00 as "Obligation"

### R5 — Cycle show page — items section hidden when accepts_items=false

1. View the **Annual Dues 2026** show page
2. **Expected:** No "Donation Items" card is shown

### R6 — Manage Pledges button hidden for non-pledge cycles

1. View the **Annual Dues 2026** show page
2. **Expected:** No "Manage Pledges" button in the action bar

---

# PART IV — Edge Cases

| Scenario | Expected |
|---|---|
| Pay for spouse when spouse has no active account | "Pay for Spouse" checkbox does not appear |
| Submit couple payment form without ticking spouse checkbox | Only primary member's record is created |
| Pledge-based + couple_shared both ticked | Pledge amount used as obligation; couple_shared still controls `totalPaidWithSpouse` |
| Donation item with estimated_value = 0 | Saved as £0.00, not null; shows "GBP 0.00" |
| Recording a pledge for a member who already has one | Existing pledge is updated (upsert), no duplicate row |
| Viewing cycle show page when accepts_items=false | Donation Items section is hidden entirely |
| Viewing cycle show page when is_pledge_based=false | "Manage Pledges" button is hidden entirely |
| Pledge-based + accepts_items both ticked | "Manage Pledges" button shown; **no** separate "Donation Items" button; items managed from contributions page |
| Member dashboard with no active cycles | "No active dues cycles at the moment." shown in the cycle card area |
| Member dashboard with no recent payments | "No payments recorded yet." shown in recent payments list |
| Item fulfillment toggle on already-received item | Reverts to Pending status |
| Submitting contribution form with no pledge amount and no items | Validation error — at least one field required |

# Testing Guide: Failed Delivery & Real-time Updates

## Prerequisites
- System is running (Laravel backend + React frontend)
- At least one test order exists in "Shipped" status
- Driver account is set up and logged in
- Admin account is set up and logged in

## Test 1: Failed Delivery Logic

### Steps:
1. **Setup**
   - Log in as Admin
   - Create a new order or use existing order
   - Progress order to "Shipped" status
   - Note the Order ID (e.g., ORD-20260504-001)

2. **Driver Marks as Failed**
   - Log in as Driver (in different browser/incognito)
   - Navigate to "My Deliveries"
   - Find the shipped order
   - Click on the order to expand details
   - Click "Mark as Failed" button
   - Enter driver notes (e.g., "Customer not available at delivery address")
   - Submit

3. **Verify Backend Changes**
   - Check database: `SELECT status FROM sales WHERE transaction_id = 'ORD-20260504-001'`
   - Expected: status should be `return_requested`
   - Check notes field: should contain "Driver (Failed): Customer not available..."

4. **Verify Admin Notifications**
   - Switch to Admin browser
   - Check notification bell (should have new notification)
   - Check email inbox (should receive "Delivery Failed - Return Requested" email)
   - Verify notification message mentions driver name and order ID

5. **Verify Customer Notifications**
   - Check customer email inbox
   - Should receive "Delivery Issue - Return Requested" email
   - Email should explain delivery failed and return was initiated

6. **Verify UI Updates**
   - In Admin portal, go to Orders page
   - Click "Returns & Cancelled" tab
   - Order should appear with "Return Requested" status
   - Driver notes should be visible
   - Order should NOT appear in "Shipped" tab anymore

### Expected Results:
✅ Order status changes from "shipped" to "return_requested"
✅ Driver notes saved with "(Failed)" prefix
✅ Admin receives in-app notification
✅ Admin receives email notification
✅ Customer receives email notification
✅ Order appears in "Returns & Cancelled" tab
✅ Order removed from "Shipped" tab

---

## Test 2: Real-time Updates (Admin Orders Page)

### Steps:
1. **Setup**
   - Open Admin portal in Browser 1
   - Navigate to Orders page
   - Keep page open (do NOT refresh)
   - Note current order statuses

2. **Make Changes from Driver Portal**
   - Open Driver portal in Browser 2 (or incognito)
   - Find a shipped order
   - Mark it as "Delivered" (with proof of delivery)
   - Submit

3. **Observe Admin Portal (Browser 1)**
   - Wait up to 5 seconds
   - Watch the Orders page
   - Order should automatically update to "Delivered" status
   - NO manual refresh needed

4. **Test with Multiple Changes**
   - From Driver portal, mark another order as "Failed"
   - From Secretary portal (if available), update payment status
   - Each change should appear in Admin portal within 5 seconds

5. **Test Modal Behavior**
   - In Admin portal, click "View" on any order (opens modal)
   - From Driver portal, change another order status
   - Verify Admin portal does NOT refresh while modal is open
   - Close modal
   - Verify polling resumes and updates appear

### Expected Results:
✅ Orders page updates automatically every 5 seconds
✅ Changes from other portals appear without manual refresh
✅ Polling pauses when modal is open
✅ Polling resumes when modal is closed
✅ No data loss or UI glitches during updates

---

## Test 3: Real-time Updates (Driver Deliveries Page)

### Steps:
1. **Setup**
   - Open Driver portal
   - Navigate to "My Deliveries"
   - Keep page open (do NOT refresh)

2. **Make Changes from Admin Portal**
   - Open Admin portal in different browser
   - Find an order assigned to this driver
   - Update payment status to "Paid"
   - Or accept a return request

3. **Observe Driver Portal**
   - Wait up to 5 seconds
   - Delivery list should update automatically
   - Payment status badge should change
   - NO manual refresh needed

### Expected Results:
✅ Deliveries page updates automatically every 5 seconds
✅ Changes from Admin portal appear without manual refresh
✅ Payment status updates are visible
✅ Return status updates are visible

---

## Test 4: Cross-Portal Synchronization

### Steps:
1. **Setup**
   - Open 3 browsers:
     - Browser 1: Admin Orders page
     - Browser 2: Driver Deliveries page
     - Browser 3: Secretary Orders page (if available)

2. **Make a Change**
   - From Browser 2 (Driver), mark an order as "Delivered"

3. **Observe All Browsers**
   - Within 5 seconds, all browsers should show updated status
   - Browser 1 (Admin): Order moves to "Delivered & Completed" tab
   - Browser 2 (Driver): Order status updates to "Delivered"
   - Browser 3 (Secretary): Order status updates to "Delivered"

### Expected Results:
✅ All portals update within 5 seconds
✅ Status changes are consistent across all portals
✅ No conflicts or race conditions
✅ All users see the same data

---

## Test 5: Performance & Edge Cases

### Test 5.1: Background Tab Behavior
1. Open Admin Orders page
2. Switch to different tab (Orders page in background)
3. Wait 30 seconds
4. Switch back to Orders page
5. Verify: Polling should resume and fetch latest data

### Test 5.2: Network Interruption
1. Open Admin Orders page
2. Disconnect internet
3. Wait 10 seconds
4. Reconnect internet
5. Verify: Polling resumes and data updates

### Test 5.3: Large Dataset
1. Create 50+ orders
2. Open Orders page
3. Verify: Polling still works smoothly
4. Check browser console for errors
5. Monitor network tab for excessive requests

### Expected Results:
✅ Polling pauses when tab is in background
✅ Polling resumes when tab becomes active
✅ Graceful handling of network errors
✅ No performance degradation with large datasets
✅ No excessive API calls or memory leaks

---

## Troubleshooting

### Issue: Orders not updating automatically
**Check:**
- Browser console for JavaScript errors
- Network tab for failed API requests
- Verify polling interval is set to 5000ms
- Check if modals are open (polling pauses)
- Verify tab is visible (not in background)

### Issue: Failed delivery not moving to Return Requested
**Check:**
- Database: Verify status transition is allowed
- Backend logs: Check for validation errors
- SaleService.php: Verify 'return_requested' is in valid transitions
- DriverPortalController.php: Verify status is set correctly

### Issue: Notifications not received
**Check:**
- Email configuration in .env file
- Queue worker is running (php artisan queue:work)
- NotificationService is working
- Email addresses are correct
- Check spam folder

---

## Success Criteria

All tests pass if:
✅ Failed deliveries automatically move to "Return Requested"
✅ All stakeholders receive notifications
✅ Orders page updates every 5 seconds without manual refresh
✅ Driver deliveries page updates every 5 seconds
✅ Changes are synchronized across all portals
✅ Polling pauses when modals are open
✅ No performance issues or errors
✅ User experience is smooth and intuitive

---

## Rollback Plan

If issues are found:
1. Revert `SaleService.php` changes (remove 'return_requested' from shipped transitions)
2. Revert `DriverPortalController.php` changes (remove email notifications)
3. Revert `Orders.jsx` changes (remove polling useEffect)
4. Clear cache: `php artisan cache:clear`
5. Restart queue worker: `php artisan queue:restart`
6. Hard refresh browsers: Ctrl+Shift+R (or Cmd+Shift+R on Mac)

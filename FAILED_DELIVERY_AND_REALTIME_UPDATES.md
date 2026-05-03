# Failed Delivery & Real-time Updates Implementation

## Overview
This document describes the implementation of failed delivery logic and real-time updates for the order management system.

## Changes Made

### 1. Failed Delivery Logic (Backend)

#### File: `laravel_backend/app/Services/SaleService.php`
**Change**: Added `'return_requested'` to valid transitions from `'shipped'` status

```php
'shipped' => ['delivered', 'cancelled', 'return_requested'], // Allow failed deliveries to go to return_requested
```

**Why**: When a driver marks a shipped order as "Failed", the system needs to automatically move it to "Return Requested" status. This transition was previously blocked.

#### File: `laravel_backend/app/Http/Controllers/DriverPortalController.php`
**Change**: Added email notifications for failed deliveries

```php
} elseif ($action === 'failed') {
    // ... existing notification code ...
    
    // Send email notifications after response
    dispatch(function () use ($emailService, $saleSnapshot, $driverName) {
        try {
            $emailService->sendOrderStatusToAdmin(
                $saleSnapshot,
                'Delivery Failed - Return Requested',
                "Order #{$saleSnapshot->transaction_id} delivery failed by {$driverName}. The order has been automatically moved to Return Requested status."
            );
            $emailService->sendOrderStatusToCustomer(
                $saleSnapshot,
                'Delivery Issue - Return Requested',
                "We're sorry, but your order #{$saleSnapshot->transaction_id} could not be delivered. We've automatically initiated a return request. Our team will contact you shortly."
            );
        } catch (\Throwable $e) {
            \Log::warning("Failed delivery email failed for sale #{$saleSnapshot->id}: " . $e->getMessage());
        }
    })->afterResponse();
}
```

**Why**: Admins and customers need to be notified when a delivery fails so they can take appropriate action.

### 2. Real-time Updates (Frontend)

#### File: `react_frontend/src/pages/admin/Orders/Orders.jsx`
**Change**: Added real-time polling to automatically refresh order data every 5 seconds

```javascript
// Real-time polling — refresh every 5s when tab is visible and no modal open
useEffect(() => {
  const interval = setInterval(() => {
    if (document.visibilityState === 'visible' && 
        !isViewModalOpen && !isCancelModalOpen && !isReturnModalOpen && 
        !isAcceptReturnModalOpen && !isMarkReturnModalOpen && !isPayModalOpen && 
        !isShipModalOpen && !isDeliverModalOpen && !isVoidModalOpen && !isRestockModalOpen) {
      refetch();
    }
  }, 5000);
  return () => clearInterval(interval);
}, [refetch, isViewModalOpen, isCancelModalOpen, isReturnModalOpen, isAcceptReturnModalOpen, 
    isMarkReturnModalOpen, isPayModalOpen, isShipModalOpen, isDeliverModalOpen, isVoidModalOpen, isRestockModalOpen]);
```

**Why**: The orders page needs to automatically refresh when changes are made from other portals (driver, customer, secretary). This ensures all users see up-to-date information without manually refreshing.

**Note**: The driver deliveries page (`react_frontend/src/pages/driver/Deliveries/Deliveries.jsx`) already had real-time polling implemented, so no changes were needed there.

## How It Works

### Failed Delivery Flow:
1. Driver marks a shipped order as "Failed" in the driver portal
2. Backend automatically changes order status from `shipped` to `return_requested`
3. Driver notes are saved with "(Failed)" prefix
4. Notifications are sent to admins via the notification system
5. Emails are sent to both admins and customers
6. The order appears in the "Returns & Cancelled" tab with "Return Requested" status
7. Admin can then accept the return and assign a pickup driver

### Real-time Updates Flow:
1. Every 5 seconds, the orders page checks if:
   - The browser tab is visible (not in background)
   - No modals are currently open (to avoid disrupting user input)
2. If conditions are met, it fetches fresh order data from the API
3. The UI updates automatically to show the latest status
4. This works across all portals:
   - Driver marks order as delivered → Admin sees it immediately
   - Secretary updates payment status → Driver sees it immediately
   - Customer requests return → Admin sees it immediately

## Benefits

1. **Automatic Status Management**: Failed deliveries are automatically moved to the correct status without manual intervention
2. **Better Communication**: All stakeholders are notified when deliveries fail
3. **Real-time Visibility**: All users see up-to-date order information without manual refreshing
4. **Improved Workflow**: Reduces confusion and delays caused by stale data
5. **Better User Experience**: No need to constantly refresh the page to see updates

## Testing Recommendations

1. **Failed Delivery Test**:
   - Create an order and mark it as shipped
   - Log in as a driver and mark the order as "Failed"
   - Verify the order status changes to "Return Requested"
   - Check that admin and customer receive email notifications
   - Verify the order appears in the "Returns & Cancelled" tab

2. **Real-time Updates Test**:
   - Open the orders page in two browser windows (one as admin, one as driver)
   - Mark an order as delivered in the driver portal
   - Verify the admin portal updates within 5 seconds without manual refresh
   - Test with different status changes (shipped, delivered, return requested, etc.)

3. **Modal Interaction Test**:
   - Open a modal on the orders page (e.g., view order details)
   - Wait 5+ seconds
   - Verify that the page does NOT refresh while the modal is open
   - Close the modal and verify polling resumes

## Future Enhancements

1. **WebSocket Integration**: Replace polling with WebSocket/Pusher for instant updates
2. **Optimistic UI Updates**: Show changes immediately before server confirmation
3. **Conflict Resolution**: Handle cases where multiple users edit the same order simultaneously
4. **Offline Support**: Queue status changes when offline and sync when connection is restored

# Testing Guide for Invoice Fix

## What Was Fixed

The invoice was showing `product: null` for some items. Now it will fetch complete product data from the database.

## Changes Made

### Backend (`PosController.php`)
- ✅ Added `getOrderForInvoice($xid)` method
- ✅ Added `convertNumberToWords($number)` helper
- ✅ Added error handling with try-catch
- ✅ Removed `withTrashed()` (not needed)
- ✅ Changed from object to array notation for compatibility

### Frontend (`Invoice.vue`)
- ✅ Added auto-fetch when modal opens
- ✅ Added loading state
- ✅ Added fallback to props if API fails

### Routes (`web.php`)
- ✅ Added `GET /api/pos/order/{xid}` route

## How to Test

### 1. Open POS Invoice
```
1. Go to POS
2. Create or select an order
3. Click "Print Invoice" button
4. Invoice modal should open
```

### 2. Check Console
Open browser console (F12) and look for:
```javascript
// Should see:
"Loaded complete order data: {order object}"

// Should NOT see:
"Error fetching order data"
```

### 3. Check Invoice Display
The invoice should show:
- ✅ Product names (not "Product Not Found")
- ✅ HSN codes (not "-" or blank)
- ✅ All product details
- ✅ Custom fields if any

### 4. Test Cases

#### Test Case 1: Active Products
- Order with active products
- Expected: All product names and HSN codes visible

#### Test Case 2: Deleted Products  
- Order with deleted products
- Expected: Still shows product names and HSN codes (from database)

#### Test Case 3: Mixed Products
- Order with both active and deleted products
- Expected: All products display correctly

#### Test Case 4: API Failure
- Simulate API failure (disconnect network)
- Expected: Falls back to props data, invoice still works

## Debugging

### If API Returns 400 Error

Check Laravel logs:
```bash
tail -f storage/logs/laravel.log
```

Look for:
```
Error in getOrderForInvoice: [error message]
Stack trace: [stack trace]
```

### If Products Still Show as Null

1. Check if products exist in database:
```sql
SELECT * FROM products WHERE id IN (
  SELECT product_id FROM order_items WHERE order_id = X
);
```

2. Check if HSN codes exist:
```sql
SELECT id, name, hsn_code FROM products WHERE id = X;
```

3. Check browser console for API response

### Common Issues

**Issue 1: "An unknown error occurred"**
- Solution: Check Laravel logs for actual error
- Fixed by adding try-catch and error logging

**Issue 2: Products still null**
- Solution: Check if Product model has correct relationships
- Fixed by using direct query instead of relationships

**Issue 3: HSN codes not showing**
- Solution: Check if hsn_code column exists in products table
- Fixed by using null coalescing operator (??)

## API Response Example

### Success Response:
```json
{
  "success": true,
  "message": "Order fetched successfully",
  "order": {
    "xid": "0W44L3WJ",
    "invoice_number": "SALE-19983",
    "total": 13000,
    "amount_in_words": "Thirteen Thousand Rupees Only",
    "entry_person_name": "ADMIN",
    "items": [
      {
        "quantity": 1,
        "unit_price": 600,
        "product": {
          "name": "Product Name",
          "hsn_code": "8516",
          "image_url": "https://..."
        }
      }
    ]
  }
}
```

### Error Response:
```json
{
  "error": {
    "message": "Failed to fetch order: [error details]",
    "code": 1
  }
}
```

## Rollback Plan

If something goes wrong, you can:

1. **Remove the route:**
```php
// In routes/web.php, comment out:
// ApiRoute::get('pos/order/{xid}', ['as' => 'api.pos.order', 'uses' => 'PosController@getOrderForInvoice']);
```

2. **Disable auto-fetch in Invoice.vue:**
```javascript
// Comment out the watch that calls fetchCompleteOrderData
// The invoice will use props data only
```

3. **The invoice will work as before** (with product: null issue)

## Success Criteria

✅ Invoice opens without errors
✅ Product names display correctly
✅ HSN codes display correctly  
✅ No console errors
✅ Print functionality works
✅ PDF download works (if enabled)
✅ Works for both active and deleted products

## Next Steps

After testing:
1. Monitor Laravel logs for any errors
2. Check user feedback
3. Test with different order types (sales, returns, etc.)
4. Test with different product configurations

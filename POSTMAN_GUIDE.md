# Postman Collection Guide

## Quick Start

1. **Import Collection**
   - Open Postman
   - Click "Import" button
   - Select `Support_Desk_Lite.postman_collection.json`
   - Collection will appear in your workspace

2. **Set Variables**
   - Click on collection name → "Variables" tab
   - Ensure `base_url` is set to `http://localhost:8000`
   - Update other variables as needed:
     - `ticket_id`: Will be set from responses
     - `customer_id`: Will be set from responses
     - `tag_id`: Will be set from responses

3. **Start Server**
   ```bash
   php artisan serve
   ```

4. **Test Sequence**
   - **Step 1**: Create Customer → Copy `id` from response → Update `customer_id` variable
   - **Step 2**: Create Ticket → Copy `id` from response → Update `ticket_id` variable
   - **Step 3**: Get Ticket Details → Verify all relationships
   - **Step 4**: Add Comment/Internal Note → Verify ticket reopened if needed
   - **Step 5**: Attach Tag → Use tag ID (create tag in database first if needed)
   - **Step 6**: List Tickets → Test filters, search, sorting

## Creating Tags

Tags need to be created in the database first. The easiest way is to use the seeder:

**Option 1: Run Database Seeder (Recommended)**
```bash
php artisan db:seed
```

This will create 5 sample tags:
- urgent (red)
- bug (red)
- feature (teal)
- question (yellow)
- support (green)

**Option 2: Run Tag Seeder Only**
```bash
php artisan db:seed --class=TagSeeder
```

**Option 3: Laravel Tinker**
```bash
php artisan tinker
```
```php
$tag = \App\Models\Tag::create(['name' => 'urgent', 'colour' => '#ff0000']);
echo $tag->id; // Use this ID in Postman
```

**Option 4: Direct SQL**
```sql
INSERT INTO tags (name, colour, created_at, updated_at) 
VALUES ('urgent', '#ff0000', NOW(), NOW());
```

## Testing Business Rules

### Rule 1: Resolved Status
1. Create a ticket with status `open`
2. Update ticket to status `resolved`
3. Check response → `resolved_at` should be set automatically

### Rule 2: Comment Reopens Ticket
1. Create a ticket and resolve it (status `resolved`)
2. Add a comment via POST `/api/tickets/{id}/updates`
3. Check ticket details → Status should be `open`, `resolved_at` should be `null`

### Rule 3: Internal Notes Visibility
1. Add an internal note to a ticket
2. Get ticket details (authenticated) → Internal note should be visible
3. Test without authentication → Internal note should be filtered out

## Common Issues

**404 Not Found**
- Check if `base_url` is correct
- Ensure server is running: `php artisan serve`
- Verify IDs exist in database

**422 Validation Error**
- Check enum values: status must be `open|in_progress|resolved|closed`
- Check priority must be `low|medium|high|urgent`
- Check type must be `comment|internal_note|status_change`
- Ensure customer_id exists

**500 Internal Server Error**
- Check Laravel logs: `storage/logs/laravel.log`
- Verify database migrations ran: `php artisan migrate:status`

## Tips

- Use Postman's "Tests" tab to automatically extract IDs from responses
- Use collection variables to share IDs across requests
- Save example responses for reference
- Use Postman's environment feature for different environments (dev/staging)


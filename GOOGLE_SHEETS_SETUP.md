# Google Sheets Integration Setup Guide

## Overview
Your Oikos Orchard & Farm booking system now automatically transfers all bookings to Google Sheets, in addition to saving them locally in `bookings.json` and sending email confirmations.

## Setup Steps

### Step 1: Create a Google Sheet for Bookings
1. Go to [Google Drive](https://drive.google.com)
2. Click **New** → **Google Sheets**
3. Rename the sheet to "Oikos Bookings" or similar
4. The default sheet name should be "Sheet1" (or create a new sheet named "Bookings")
5. Keep the sheet open - you'll need the ID next

### Step 2: Get Your Google Sheet ID
1. Look at the URL of your Google Sheet: `https://docs.google.com/spreadsheets/d/SHEET_ID_HERE/edit`
2. Copy the `SHEET_ID_HERE` part (long alphanumeric string)
3. Save this ID for the next step

### Step 3: Create a Google Apps Script
1. Go to [Google Apps Script](https://script.google.com)
2. Click **New project**
3. Copy and paste the code from `google-apps-script.gs` file in your project
4. Replace `YOUR_GOOGLE_SHEET_ID_HERE` with your actual Sheet ID from Step 2
5. Make sure the sheet name in your code matches your Google Sheet (default is "Bookings")
6. Click **Save** (Ctrl+S)

### Step 4: Deploy the Apps Script
1. Click the **Deploy** button (top right)
2. Click **New deployment**
3. Click the gear icon, select **Web app**
4. Set:
   - **Execute as:** Your Gmail account
   - **Who has access:** Anyone
5. Click **Deploy**
6. Copy the deployment URL (it will look like: `https://script.google.com/macros/s/SCRIPT_ID/userweb`)

### Step 5: Update Your PHP Configuration
1. Open `google-sheets-integration.php` in your text editor
2. Find this line:
   ```php
   define('GOOGLE_SHEETS_WEBHOOK_URL', 'https://script.google.com/macros/d/YOUR_SCRIPT_ID/userweb?v=YOUR_VERSION_ID');
   ```
3. Replace it with your actual deployment URL from Step 4
4. **Note:** Change `/macros/d/` to `/macros/s/` if needed (the URL format varies)
5. Save the file

## How It Works

### When a Booking is Submitted:
1. Booking data is saved to `bookings.json` (local storage)
2. Booking data is sent to Google Sheets via the Apps Script webhook
3. Confirmation emails are sent to both admin and customer
4. Success response is returned to the frontend

### Data Flow:
```
Website Form → send-booking.php → bookings.json ✓
                               → Google Sheets ✓
                               → Email Confirmations ✓
```

## Testing

### Method 1: Test via Website
1. Open your website
2. Fill out and submit a booking form
3. Check your Google Sheet - new row should appear within seconds

### Method 2: Test Google Apps Script
1. Go back to your Apps Script project
2. Click on `testBooking()` function
3. Click **Run** button
4. Check your Google Sheet - a test booking should appear

## Troubleshooting

### Bookings not appearing in Google Sheets?

**Issue:** Google Apps Script webhook not configured
- **Solution:** Make sure you updated `GOOGLE_SHEETS_WEBHOOK_URL` in `google-sheets-integration.php`
- **Check:** The URL should not have placeholder text like `YOUR_SCRIPT_ID`

**Issue:** "Sheet not found" error
- **Solution:** Make sure the sheet tab name in the Apps Script matches your Google Sheet
- **Check:** Go to your Google Sheet and verify the tab name (default: "Bookings")

**Issue:** Permission denied error
- **Solution:** When deploying the Apps Script, make sure you selected:
  - Execute as: Your Gmail account
  - Who has access: Anyone
- **Note:** You may need to re-deploy if permissions were set incorrectly

### Need to update the deployment?
1. Go to your Apps Script project
2. Click **Deploy** → **Manage deployments**
3. Click the pencil icon to edit
4. Make your changes and click **Deploy**
5. Update the URL in `google-sheets-integration.php`

## Google Sheet Features

### Auto-Formatting
The Google Apps Script automatically:
- Adds column headers with green background and white text
- Auto-resizes columns to fit content
- Formats timestamps consistently

### Columns in Google Sheet:
1. Booking ID
2. Full Name
3. Email
4. Phone
5. Check-in Date
6. Number of Guests
7. Package Name
8. Package Price
9. Special Requests
10. Submitted At

## Security Notes

- ✓ Bookings are still saved locally to `bookings.json`
- ✓ Email notifications are still sent to admin and customer
- ✓ Google Sheets provides additional cloud backup
- ✓ Apps Script runs as your personal account (no external sharing by default)
- ✓ The webhook only accepts POST requests with valid booking data

## File Structure

```
OikosOrchardandFarm/
├── send-booking.php              (Updated - now includes Google Sheets)
├── google-sheets-integration.php  (New - webhook configuration)
├── google-apps-script.gs          (New - Apps Script code)
├── GOOGLE_SHEETS_SETUP.md         (This file)
├── bookings.json                  (Local backup - unchanged)
└── ... other files
```

## Additional Customization

### Sending Notifications to Multiple Emails
Edit the `sendGoogleSheetsNotification()` function in `google-apps-script.gs` to send emails to multiple recipients:

```javascript
const adminEmails = [
  'owner1@example.com',
  'owner2@example.com'
];

adminEmails.forEach(email => {
  GmailApp.sendEmail(email, subject, message, {htmlBody: message});
});
```

### Adding More Columns
1. Update the `headers` array in `addBookingToSheet()`
2. Update the `bookingRow` array to include new data
3. Update `send-booking.php` to capture the new field

## Support

If you encounter issues:
1. Check the Google Apps Script logs: **View** → **Logs**
2. Check the browser console for JavaScript errors: F12 → Console
3. Verify your Sheet ID is correct
4. Make sure the Apps Script has the right sheet name

---

**Last Updated:** January 27, 2026
**Version:** 1.0

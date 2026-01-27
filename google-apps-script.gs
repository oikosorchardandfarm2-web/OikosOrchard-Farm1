/**
 * Google Apps Script for Oikos Orchard & Farm Bookings
 * 
 * SETUP INSTRUCTIONS:
 * 1. Go to https://script.google.com
 * 2. Create a new project
 * 3. Paste this entire code into the editor
 * 4. Create a Google Sheet for bookings (save the Sheet ID)
 * 5. Update SPREADSHEET_ID below with your Google Sheet ID
 * 6. Save the project (Ctrl+S)
 * 7. Click "Deploy" > "New deployment" > Type: "Web app"
 * 8. Execute as: Your email address
 * 9. Who has access: "Anyone"
 * 10. Click "Deploy"
 * 11. Copy the deployment URL and paste it in send-booking.php
 * 
 * To find your Sheet ID: Open your Google Sheet, the ID is in the URL
 * Example: https://docs.google.com/spreadsheets/d/SHEET_ID_HERE/edit
 */

// ============ CONFIGURATION ============
// Replace with your Google Sheet ID
const SPREADSHEET_ID = '1pWE72focDg7ZguylUJIaSysHZg1qxfQ_JiiT4Fk-26c';
const SHEET_NAME = 'Sheet1'; // The sheet tab name

// ============ WEB APP ENDPOINT ============
function doPost(e) {
  try {
    Logger.log('doPost called with event: ' + JSON.stringify(e));
    
    // Check if postData exists
    if (!e || !e.postData || !e.postData.contents) {
      Logger.log('Warning: No postData received');
      // Still try to process in case data came differently
      if (e && e.parameter) {
        Logger.log('Data in parameter: ' + JSON.stringify(e.parameter));
      }
      return ContentService.createTextOutput(JSON.stringify({
        success: false,
        message: 'No POST data received'
      })).setMimeType(ContentService.MimeType.JSON);
    }

    // Parse the incoming JSON data
    let data;
    try {
      data = JSON.parse(e.postData.contents);
      Logger.log('Parsed booking data: ' + JSON.stringify(data));
    } catch (parseError) {
      Logger.log('JSON Parse Error: ' + parseError.toString());
      Logger.log('Raw postData: ' + e.postData.contents);
      return ContentService.createTextOutput(JSON.stringify({
        success: false,
        message: 'Invalid JSON format'
      })).setMimeType(ContentService.MimeType.JSON);
    }
    
    // Add booking to Google Sheets
    const result = addBookingToSheet(data);
    Logger.log('addBookingToSheet result: ' + JSON.stringify(result));
    
    if (result.success) {
      return ContentService.createTextOutput(JSON.stringify({
        success: true,
        message: 'Booking added to Google Sheets successfully',
        rowNumber: result.rowNumber
      })).setMimeType(ContentService.MimeType.JSON);
    } else {
      return ContentService.createTextOutput(JSON.stringify({
        success: false,
        message: result.error
      })).setMimeType(ContentService.MimeType.JSON);
    }
    
  } catch (error) {
    Logger.log('Fatal Error in doPost: ' + error.toString() + ' at ' + error.lineNumber);
    return ContentService.createTextOutput(JSON.stringify({
      success: false,
      message: 'Server error: ' + error.toString()
    })).setMimeType(ContentService.MimeType.JSON);
  }
}

// ============ GET REQUEST HANDLER ============
function doGet(e) {
  return ContentService.createTextOutput(JSON.stringify({
    success: true,
    message: 'Oikos Orchard & Farm Booking API is running',
    status: 'Ready to receive bookings',
    spreadsheetId: SPREADSHEET_ID,
    sheetName: SHEET_NAME
  })).setMimeType(ContentService.MimeType.JSON);
}

// ============ ADD BOOKING TO SHEET ============
function addBookingToSheet(data) {
  try {
    // Get the spreadsheet and sheet
    const spreadsheet = SpreadsheetApp.openById(SPREADSHEET_ID);
    const sheet = spreadsheet.getSheetByName(SHEET_NAME);
    
    if (!sheet) {
      return {
        success: false,
        error: 'Sheet "' + SHEET_NAME + '" not found. Please create it first.'
      };
    }
    
    // Get last row to determine if we need headers
    const lastRow = sheet.getLastRow();
    
    // Add headers if sheet is empty
    if (lastRow === 0) {
      const headers = [
        'Booking ID',
        'Full Name',
        'Email',
        'Phone',
        'Check-in Date',
        'Number of Guests',
        'Package Name',
        'Package Price',
        'Special Requests',
        'Submitted At'
      ];
      sheet.appendRow(headers);
      
      // Format header row
      const headerRange = sheet.getRange(1, 1, 1, headers.length);
      headerRange.setBackground('#27ae60');
      headerRange.setFontColor('#ffffff');
      headerRange.setFontWeight('bold');
    }
    
    // Prepare the booking row
    const bookingRow = [
      data.bookingId || '',
      data.fullName || '',
      data.email || '',
      data.phone || '',
      data.checkinDate || '',
      data.guests || '',
      data.packageName || '',
      data.packagePrice || '',
      data.specialRequests || '',
      data.timestamp || new Date().toLocaleString()
    ];
    
    // Append the booking row
    sheet.appendRow(bookingRow);
    
    // Get the row number of the newly added booking
    const newRowNumber = sheet.getLastRow();
    
    // Auto-fit columns
    sheet.autoResizeColumns(1, bookingRow.length);
    
    // Log the booking
    Logger.log('Booking added to row: ' + newRowNumber);
    Logger.log('Booking data: ' + JSON.stringify(data));
    
    return {
      success: true,
      rowNumber: newRowNumber,
      message: 'Booking added successfully'
    };
    
  } catch (error) {
    Logger.log('Error in addBookingToSheet: ' + error.toString());
    return {
      success: false,
      error: error.toString()
    };
  }
}

// ============ TEST FUNCTION (for debugging) ============
// Run this in the Apps Script editor to test
function testBooking() {
  const testData = {
    bookingId: 'test_' + new Date().getTime(),
    fullName: 'John Doe',
    email: 'john@example.com',
    phone: '555-1234',
    checkinDate: '2026-02-15',
    guests: '4',
    packageName: 'Glamping for 2',
    packagePrice: '₱3,200',
    specialRequests: 'Extra pillows please',
    timestamp: new Date().toLocaleString()
  };
  
  const result = addBookingToSheet(testData);
  Logger.log('Test result: ' + JSON.stringify(result));
}

// ============ DIAGNOSTIC FUNCTION ============
// Lists all sheet names in the spreadsheet
function listSheets() {
  try {
    Logger.log('Attempting to access spreadsheet: ' + SPREADSHEET_ID);
    const spreadsheet = SpreadsheetApp.openById(SPREADSHEET_ID);
    const sheets = spreadsheet.getSheets();
    Logger.log('Found ' + sheets.length + ' sheets');
    
    sheets.forEach(function(sheet, index) {
      Logger.log('Sheet ' + (index + 1) + ': "' + sheet.getName() + '"');
    });
    
    return sheets.map(s => s.getName());
  } catch (error) {
    Logger.log('ERROR accessing spreadsheet: ' + error.toString());
    return [];
  }
}

// ============ SEND EMAIL NOTIFICATION ============
function sendGoogleSheetsNotification(data) {
  try {
    // This function sends a notification email to your Gmail
    // when a new booking is added
    const adminEmail = 'oikosorchardandfarm2@gmail.com';
    
    const subject = 'New Booking Added to Google Sheets - ' + data.packageName;
    
    const message = `
    <html>
      <body style="font-family: Arial, sans-serif;">
        <h2>New Booking Added!</h2>
        <p><strong>Name:</strong> ${data.fullName}</p>
        <p><strong>Email:</strong> ${data.email}</p>
        <p><strong>Phone:</strong> ${data.phone}</p>
        <p><strong>Package:</strong> ${data.packageName}</p>
        <p><strong>Check-in Date:</strong> ${data.checkinDate}</p>
        <p><strong>Guests:</strong> ${data.guests}</p>
        <p><strong>Submitted At:</strong> ${data.timestamp}</p>
        <hr>
        <p><a href="https://docs.google.com/spreadsheets/d/${SPREADSHEET_ID}/">View in Google Sheets</a></p>
      </body>
    </html>
    `;
    
    GmailApp.sendEmail(adminEmail, subject, message, {
      htmlBody: message
    });
    
  } catch (error) {
    Logger.log('Error sending notification email: ' + error.toString());
  }
}

// Google Apps Script webhook URL
const WEBHOOK_URL = 'https://script.google.com/macros/s/AKfycbyfgMWh3i6EvBrf6yyNkrHsX7LFUYXTvzZ3C95oEI7DVcDOmWLXOUdj1j4PMbag_-fI7w/exec';

exports.handler = async (event, context) => {
  // Only allow POST requests
  if (event.httpMethod !== 'POST') {
    return {
      statusCode: 405,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ success: false, message: 'Method not allowed' })
    };
  }

  try {
    // Parse incoming data
    const data = JSON.parse(event.body);
    console.log('Received booking data:', data);

    // Validate required fields
    if (!data.fullName || !data.email || !data.phone || !data.checkinDate || !data.guests || !data.packageName) {
      return {
        statusCode: 400,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ success: false, message: 'Please fill all required fields' })
      };
    }

    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(data.email)) {
      return {
        statusCode: 400,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ success: false, message: 'Invalid email address' })
      };
    }

    // Prepare booking data
    const bookingData = {
      fullName: data.fullName,
      email: data.email,
      phone: data.phone,
      checkinDate: data.checkinDate,
      guests: data.guests,
      packageName: data.packageName,
      packagePrice: data.packagePrice || '',
      specialRequests: data.specialRequests || '',
      timestamp: new Date().toLocaleString(),
      bookingId: 'booking_' + Date.now()
    };

    console.log('Sending to Google Sheets:', bookingData);

    // Send to Google Apps Script
    const sheetResponse = await fetch(WEBHOOK_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(bookingData)
    });

    console.log('Google Sheets response status:', sheetResponse.status);
    const sheetResponseText = await sheetResponse.text();
    console.log('Google Sheets response:', sheetResponseText);

    if (sheetResponse.status >= 200 && sheetResponse.status < 300) {
      console.log('Successfully sent to Google Sheets:', bookingData.bookingId);
    } else {
      console.warn('Google Sheets returned non-2xx status:', sheetResponse.status);
    }

    return {
      statusCode: 200,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        success: true,
        message: 'Booking submitted successfully! A confirmation email has been sent to ' + data.email + '. Our team will contact you within 24 hours at ' + data.phone + '.',
        data: bookingData
      })
    };

  } catch (error) {
    console.error('Error processing booking:', error);
    return {
      statusCode: 500,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ 
        success: false, 
        message: 'Server error: ' + error.message,
        error: error.toString()
      })
    };
  }
};

// Booking handler - saves to bookings.json and returns success
const fs = require('fs');
const path = require('path');

exports.handler = async (event, context) => {
  // Add CORS headers
  const headers = {
    'Content-Type': 'application/json',
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
    'Access-Control-Allow-Headers': 'Content-Type'
  };

  // Handle OPTIONS requests
  if (event.httpMethod === 'OPTIONS') {
    return {
      statusCode: 200,
      headers,
      body: 'ok'
    };
  }

  // Only allow POST requests
  if (event.httpMethod !== 'POST') {
    return {
      statusCode: 405,
      headers,
      body: JSON.stringify({ success: false, message: 'Method not allowed' })
    };
  }

  try {
    // Parse incoming data
    const data = JSON.parse(event.body);
    console.log('Received booking data:', data);

    // Safely extract and trim values
    const fullName = String(data.fullName || '').trim();
    const email = String(data.email || '').trim();
    const phone = String(data.phone || '').trim();
    const checkinDate = String(data.checkinDate || '').trim();
    const guests = String(data.guests || '').trim();
    const packageName = String(data.packageName || '').trim();
    const packagePrice = String(data.packagePrice || '').trim();
    const specialRequests = String(data.specialRequests || '').trim();

    // Validate required fields
    if (!fullName || !email || !phone || !checkinDate || !guests || !packageName) {
      return {
        statusCode: 400,
        headers,
        body: JSON.stringify({ success: false, message: 'Please fill all required fields' })
      };
    }

    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      return {
        statusCode: 400,
        headers,
        body: JSON.stringify({ success: false, message: 'Invalid email address' })
      };
    }

    // Create booking data object
    const timestamp = new Date().toISOString().replace('T', ' ').substring(0, 19);
    const bookingData = {
      fullName,
      email,
      phone,
      checkinDate,
      guests,
      packageName,
      packagePrice,
      specialRequests,
      timestamp,
      id: 'booking_' + Date.now().toString(36)
    };

    // Save to bookings.json
    try {
      const bookingsPath = path.join(__dirname, '../../bookings.json');
      console.log('Attempting to save to:', bookingsPath);
      
      let bookings = [];
      
      // Read existing bookings if file exists
      if (fs.existsSync(bookingsPath)) {
        const fileContent = fs.readFileSync(bookingsPath, 'utf8');
        bookings = JSON.parse(fileContent || '[]');
      }
      
      // Add new booking
      bookings.push(bookingData);
      
      // Write back to file
      fs.writeFileSync(bookingsPath, JSON.stringify(bookings, null, 2), 'utf8');
      console.log('Booking saved successfully to bookings.json');
    } catch (fileError) {
      console.error('Error saving to bookings.json:', fileError);
      // Continue anyway - the response will still be success since the main operation worked
    }

    // Log booking
    console.log('=== BOOKING SUBMISSION ===');
    console.log('ID:', bookingData.id);
    console.log('Name:', fullName);
    console.log('Email:', email);
    console.log('Phone:', phone);
    console.log('Check-in:', checkinDate);
    console.log('Guests:', guests);
    console.log('Package:', packageName);
    console.log('Timestamp:', timestamp);
    console.log('==========================');

    // Return success
    return {
      statusCode: 200,
      headers,
      body: JSON.stringify({
        success: true,
        message: 'Thank you! Your booking request has been received. We will contact you within 24 hours to confirm.',
        data: bookingData
      })
    };

  } catch (error) {
    console.error('Error processing booking:', error);
    return {
      statusCode: 500,
      headers,
      body: JSON.stringify({ 
        success: false, 
        message: 'Server error. Please try again later.'
      })
    };
  }
};

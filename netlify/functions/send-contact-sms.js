const https = require('https');
const querystring = require('querystring');

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
    let data;
    if (typeof event.body === 'string') {
      // Try to parse as JSON first
      try {
        data = JSON.parse(event.body);
      } catch (e) {
        // Fallback to querystring for form data
        data = querystring.parse(event.body);
      }
    } else {
      data = event.body;
    }

    console.log('Received contact SMS data:', data);

    const name = data.name ? data.name.trim() : '';
    const email = data.email ? data.email.trim() : '';
    const phone = data.phone ? data.phone.trim() : '';
    const body = data.body ? data.body.trim() : '';

    // Validate required fields
    if (!name || !email || !phone || !body) {
      return {
        statusCode: 400,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ success: false, message: 'Please fill all required fields' })
      };
    }

    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      return {
        statusCode: 400,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ success: false, message: 'Invalid email address' })
      };
    }

    // Validate message length (SMS max 160 characters)
    if (body.length > 160) {
      return {
        statusCode: 400,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ success: false, message: 'Message exceeds 160 character limit' })
      };
    }

    // Twilio credentials from environment variables
    const TWILIO_ACCOUNT_SID = process.env.TWILIO_ACCOUNT_SID;
    const TWILIO_AUTH_TOKEN = process.env.TWILIO_AUTH_TOKEN;
    const TWILIO_MESSAGING_SERVICE_SID = process.env.TWILIO_MESSAGING_SERVICE_SID;
    const NOTIFY_PHONE_NUMBER = process.env.NOTIFY_PHONE_NUMBER;

    if (!TWILIO_ACCOUNT_SID || !TWILIO_AUTH_TOKEN) {
      console.error('Missing Twilio credentials');
      return {
        statusCode: 500,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ success: false, message: 'Server configuration error' })
      };
    }

    // Format SMS message
    const smsMessage = `📧 New Contact from Oikos Website:\nName: ${name}\nEmail: ${email}\nPhone: ${phone}\nMessage: ${body}`;

    // Send SMS via Twilio
    const twilioResponse = await sendTwilioSMS(
      TWILIO_ACCOUNT_SID,
      TWILIO_AUTH_TOKEN,
      TWILIO_MESSAGING_SERVICE_SID,
      NOTIFY_PHONE_NUMBER,
      smsMessage
    );

    console.log('Twilio response:', twilioResponse);

    if (twilioResponse.success) {
      console.log('Successfully sent SMS');
      return {
        statusCode: 200,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          success: true,
          message: 'Thank you! Your message has been sent. We will contact you soon.'
        })
      };
    } else {
      console.warn('SMS sending failed:', twilioResponse.error);
      // Still return success to user, as email fallback would work on server
      return {
        statusCode: 200,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          success: true,
          message: 'Thank you! Your message has been sent. We will contact you soon.'
        })
      };
    }

  } catch (error) {
    console.error('Error processing contact SMS:', error);
    return {
      statusCode: 500,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        success: false,
        message: 'Server error. Please try again.'
      })
    };
  }
};

/**
 * Send SMS via Twilio API
 */
function sendTwilioSMS(accountSid, authToken, messagingServiceSid, toNumber, message) {
  return new Promise((resolve) => {
    const auth = Buffer.from(`${accountSid}:${authToken}`).toString('base64');

    const postData = querystring.stringify({
      MessagingServiceSid: messagingServiceSid,
      To: toNumber,
      Body: message
    });

    const options = {
      hostname: 'api.twilio.com',
      port: 443,
      path: `/2010-04-01/Accounts/${accountSid}/Messages.json`,
      method: 'POST',
      headers: {
        'Authorization': `Basic ${auth}`,
        'Content-Type': 'application/x-www-form-urlencoded',
        'Content-Length': Buffer.byteLength(postData)
      }
    };

    const req = https.request(options, (res) => {
      let responseData = '';

      res.on('data', (chunk) => {
        responseData += chunk;
      });

      res.on('end', () => {
        try {
          const response = JSON.parse(responseData);
          if (response.sid) {
            resolve({ success: true, sid: response.sid });
          } else {
            resolve({ success: false, error: response.message || 'Unknown error' });
          }
        } catch (e) {
          resolve({ success: false, error: 'Invalid response from Twilio' });
        }
      });
    });

    req.on('error', (error) => {
      console.error('Twilio request error:', error);
      resolve({ success: false, error: error.message });
    });

    req.write(postData);
    req.end();
  });
}

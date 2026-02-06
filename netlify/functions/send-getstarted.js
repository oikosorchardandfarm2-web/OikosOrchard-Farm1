const nodemailer = require('nodemailer');

exports.handler = async (event) => {
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
        const data = JSON.parse(event.body);

        // Safely extract and trim values, ensuring they're strings
        const name = String(data.name || '').trim();
        const email = String(data.email || '').trim();
        const phone = String(data.phone || '').trim();
        const interested = String(data.interested || '').trim();

        // Validate required fields
        if (!name || !email || !phone || !interested) {
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

        // Log the submission
        console.log('=== GET STARTED SUBMISSION ===');
        console.log('Timestamp:', new Date().toISOString());
        console.log('Name:', name);
        console.log('Email:', email);
        console.log('Phone:', phone);
        console.log('Interested In:', interested);
        console.log('==============================');

        // Return success - data is logged and form submission confirmed
        return {
            statusCode: 200,
            headers,
            body: JSON.stringify({
                success: true,
                message: 'Thank you! We have received your request and will contact you within 24 hours.'
            })
        };

    } catch (error) {
        console.error('Error processing Get Started form:', error);
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

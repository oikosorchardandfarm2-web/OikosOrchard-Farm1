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

        // Setup email transporter (using Gmail)
        const gmailUser = process.env.GMAIL_USER;
        const gmailPassword = process.env.GMAIL_PASSWORD || process.env.GMAIL_APP_PASSWORD;

        console.log('Email setup - gmailUser:', gmailUser ? 'SET' : 'NOT SET');
        console.log('Email setup - gmailPassword:', gmailPassword ? 'SET' : 'NOT SET');
        console.log('Email setup - process.env keys:', Object.keys(process.env).filter(k => k.includes('GMAIL') || k.includes('ADMIN')));

        if (!gmailUser || !gmailPassword) {
            console.error('Gmail credentials not configured. Check environment variables.');
            console.error('Missing: ', { gmailUser: !gmailUser, gmailPassword: !gmailPassword });
            // For now, just log the request and return success
            console.log('Get Started Request:', { name, email, phone, interested });
            return {
                statusCode: 200,
                headers,
                body: JSON.stringify({
                    success: true,
                    message: 'Thank you! We have received your request and will contact you shortly.'
                })
            };
        }

        const transporter = nodemailer.createTransport({
            service: 'gmail',
            auth: {
                user: gmailUser,
                pass: gmailPassword
            }
        });

        const adminEmail = gmailUser;
        const currentDate = new Date().toLocaleString();

        // Email to admin
        const adminMailOptions = {
            from: gmailUser,
            to: adminEmail,
            subject: 'New Get Started Request - Oikos Orchard & Farm',
            html: `
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; background: #f5f5f5; padding: 20px; border-radius: 8px; }
                        .header { background: #27ae60; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
                        .content { background: white; padding: 20px; }
                        .field { margin: 15px 0; border-bottom: 1px solid #eee; padding-bottom: 10px; }
                        .label { font-weight: bold; color: #27ae60; display: inline-block; width: 150px; }
                        .value { display: inline-block; }
                        .footer { text-align: center; color: #999; font-size: 12px; margin-top: 20px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>📋 New Get Started Request</h2>
                        </div>
                        <div class='content'>
                            <div class='field'>
                                <span class='label'>Name:</span>
                                <span class='value'>${name}</span>
                            </div>
                            <div class='field'>
                                <span class='label'>Email:</span>
                                <span class='value'>${email}</span>
                            </div>
                            <div class='field'>
                                <span class='label'>Phone:</span>
                                <span class='value'>${phone}</span>
                            </div>
                            <div class='field'>
                                <span class='label'>Interested In:</span>
                                <span class='value'>${interested}</span>
                            </div>
                            <div class='field'>
                                <span class='label'>Submitted:</span>
                                <span class='value'>${currentDate}</span>
                            </div>
                        </div>
                        <div class='footer'>
                            <p>This is an automated notification from Oikos Orchard & Farm website.</p>
                        </div>
                    </div>
                </body>
                </html>
            `
        };

        // Email to user
        const userMailOptions = {
            from: gmailUser,
            to: email,
            subject: 'Thank You for Getting Started - Oikos Orchard & Farm',
            html: `
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; background: #f5f5f5; padding: 20px; border-radius: 8px; }
                        .header { background: #27ae60; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
                        .content { background: white; padding: 20px; line-height: 1.6; }
                        .footer { text-align: center; color: #999; font-size: 12px; margin-top: 20px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>🌱 Welcome to Oikos Orchard & Farm!</h2>
                        </div>
                        <div class='content'>
                            <p>Dear <strong>${name}</strong>,</p>
                            <p>Thank you for your interest in <strong>Oikos Orchard & Farm</strong>! We have received your request and will contact you shortly.</p>
                            
                            <h3>Your Request Details:</h3>
                            <ul>
                                <li><strong>Interested In:</strong> ${interested}</li>
                                <li><strong>Submitted:</strong> ${currentDate}</li>
                            </ul>

                            <p>Our team will reach out to you within <strong>24 hours</strong> to discuss your needs and how we can help you.</p>

                            <h3>Contact Information:</h3>
                            <ul>
                                <li>📱 <strong>Phone:</strong> +63 917 777 0851</li>
                                <li>📍 <strong>Address:</strong> Vegetable Highway, Upper Bae, Sibonga, Cebu, Philippines</li>
                            </ul>

                            <p>If you have any immediate questions, feel free to reach out to us directly.</p>

                            <p>Best regards,<br>
                            <strong>🌿 Oikos Orchard & Farm Team</strong></p>
                        </div>
                        <div class='footer'>
                            <p>&copy; 2026 Oikos Orchard & Farm. All rights reserved.</p>
                            <p>Sustainable Agriculture | Organic Products | Agritourism</p>
                        </div>
                    </div>
                </body>
                </html>
            `
        };

        // Send both emails
        try {
            await transporter.sendMail(adminMailOptions);
            await transporter.sendMail(userMailOptions);
        } catch (emailError) {
            console.log('Email error (non-critical):', emailError.message);
            // Continue anyway - we got the request
        }

        return {
            statusCode: 200,
            headers,
            body: JSON.stringify({
                success: true,
                message: 'Thank you! We have received your request and will contact you shortly. Check your email for confirmation.'
            })
        };

    } catch (error) {
        console.error('Error:', error);
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

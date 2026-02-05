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

        console.log('=== GET STARTED REQUEST RECEIVED ===');
        console.log('Name:', name);
        console.log('Email:', email);
        console.log('Phone:', phone);
        console.log('Interested:', interested);
        console.log('Timestamp:', new Date().toISOString());
        console.log('=====================================');

        // Get email credentials from environment
        const gmailUser = process.env.GMAIL_USER;
        const gmailPassword = (process.env.GMAIL_PASSWORD || '').replace(/\s/g, '');

        console.log('Gmail setup check:');
        console.log('  GMAIL_USER set:', !!gmailUser);
        console.log('  GMAIL_PASSWORD length:', gmailPassword.length);

        // Try to send email if credentials exist
        if (gmailUser && gmailPassword) {
            try {
                const transporter = nodemailer.createTransport({
                    service: 'gmail',
                    auth: {
                        user: gmailUser,
                        pass: gmailPassword
                    },
                    tls: {
                        rejectUnauthorized: false
                    }
                });

                // Email to admin
                const adminMailOptions = {
                    from: gmailUser,
                    to: gmailUser,
                    subject: 'New Get Started Request - Oikos Orchard & Farm',
                    html: `
                        <h2>New Get Started Request</h2>
                        <p><strong>Name:</strong> ${name}</p>
                        <p><strong>Email:</strong> ${email}</p>
                        <p><strong>Phone:</strong> ${phone}</p>
                        <p><strong>Interested In:</strong> ${interested}</p>
                        <p><strong>Timestamp:</strong> ${new Date().toLocaleString()}</p>
                    `
                };

                // Email to user
                const userMailOptions = {
                    from: gmailUser,
                    to: email,
                    subject: 'Thank You for Getting Started - Oikos Orchard & Farm',
                    html: `
                        <h2>🌱 Welcome to Oikos Orchard & Farm!</h2>
                        <p>Dear ${name},</p>
                        <p>Thank you for your interest in <strong>Oikos Orchard & Farm</strong>! We have received your request and will contact you shortly.</p>
                        <p><strong>Your Request Details:</strong></p>
                        <ul>
                            <li>Interested In: ${interested}</li>
                            <li>Submitted: ${new Date().toLocaleString()}</li>
                        </ul>
                        <p>Our team will reach out to you within <strong>24 hours</strong> to discuss your needs.</p>
                        <p>Best regards,<br><strong>🌿 Oikos Orchard & Farm Team</strong></p>
                    `
                };

                console.log('Attempting to send emails...');
                
                await Promise.all([
                    transporter.sendMail(adminMailOptions),
                    transporter.sendMail(userMailOptions)
                ]);

                console.log('✅ Emails sent successfully');
                
                return {
                    statusCode: 200,
                    headers,
                    body: JSON.stringify({
                        success: true,
                        message: 'Thank you! We have received your request and will contact you shortly. Check your email for confirmation.'
                    })
                };
            } catch (emailError) {
                console.error('❌ Email sending failed:', {
                    message: emailError.message,
                    code: emailError.code
                });
                console.error('Stack:', emailError.stack);
                
                // Still return success - data was received even if email failed
                return {
                    statusCode: 200,
                    headers,
                    body: JSON.stringify({
                        success: true,
                        message: 'Thank you! We have received your request and will contact you shortly.'
                    })
                };
            }
        } else {
            console.warn('⚠️  Gmail credentials not configured - returning success without sending email');
            return {
                statusCode: 200,
                headers,
                body: JSON.stringify({
                    success: true,
                    message: 'Thank you! We have received your request and will contact you shortly.'
                })
            };
        }

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

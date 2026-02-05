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
        const adminEmail = process.env.ADMIN_EMAIL;

        console.log('📧 Gmail configuration:');
        console.log('  Sending FROM:', gmailUser);
        console.log('  Sending TO (Admin):', adminEmail);
        console.log('  Password length:', gmailPassword.length);

        // Try to send email if credentials exist
        if (gmailUser && gmailPassword) {
            try {
                const transporter = nodemailer.createTransport({
                    host: 'smtp.gmail.com',
                    port: 587,
                    secure: false,
                    auth: {
                        user: gmailUser,
                        pass: gmailPassword
                    }
                });

                // Verify connection
                console.log('🔐 Verifying Gmail connection...');
                await transporter.verify();
                console.log('✅ Gmail connection verified');

                // Email to admin
                const adminMailOptions = {
                    from: gmailUser,
                    to: adminEmail,
                    subject: '📋 New Get Started Request - Oikos Orchard & Farm',
                    html: `
                        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                            <h2 style="color: #27ae60;">📋 New Get Started Request</h2>
                            <p><strong>Name:</strong> ${name}</p>
                            <p><strong>Email:</strong> ${email}</p>
                            <p><strong>Phone:</strong> ${phone}</p>
                            <p><strong>Interested In:</strong> ${interested}</p>
                            <p><strong>Submitted:</strong> ${new Date().toLocaleString()}</p>
                            <hr>
                            <p style="color: #666; font-size: 12px;">This is an automated email from Oikos Orchard & Farm website.</p>
                        </div>
                    `
                };

                // Email to user
                const userMailOptions = {
                    from: gmailUser,
                    to: email,
                    subject: '🌱 Welcome to Oikos Orchard & Farm!',
                    html: `
                        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                            <h2 style="color: #27ae60;">🌱 Welcome to Oikos Orchard & Farm!</h2>
                            <p>Dear <strong>${name}</strong>,</p>
                            <p>Thank you for your interest in <strong>Oikos Orchard & Farm</strong>! We have received your request and will contact you shortly.</p>
                            <h3 style="color: #27ae60;">Your Request Details:</h3>
                            <ul>
                                <li><strong>Interested In:</strong> ${interested}</li>
                                <li><strong>Submitted:</strong> ${new Date().toLocaleString()}</li>
                            </ul>
                            <p>Our team will reach out to you within <strong>24 hours</strong> to discuss your needs.</p>
                            <h3 style="color: #27ae60;">📞 Contact Information:</h3>
                            <ul>
                                <li><strong>Phone:</strong> +63 917 777 0851</li>
                                <li><strong>Email:</strong> ${adminEmail}</li>
                                <li><strong>Address:</strong> Vegetable Highway, Upper Bae, Sibonga, Cebu, Philippines</li>
                            </ul>
                            <p>Best regards,<br><strong>🌿 Oikos Orchard & Farm Team</strong></p>
                            <hr>
                            <p style="color: #666; font-size: 12px;">&copy; 2026 Oikos Orchard & Farm. All rights reserved.</p>
                        </div>
                    `
                };

                console.log('📧 Sending admin notification to:', adminEmail);
                const adminResult = await transporter.sendMail(adminMailOptions);
                console.log('✅ Admin email sent:', adminResult.messageId);

                console.log('📧 Sending user confirmation to:', email);
                const userResult = await transporter.sendMail(userMailOptions);
                console.log('✅ User email sent:', userResult.messageId);
                
                return {
                    statusCode: 200,
                    headers,
                    body: JSON.stringify({
                        success: true,
                        message: 'Thank you! We have received your request and will contact you shortly. Check your email for confirmation.'
                    })
                };
            } catch (emailError) {
                console.error('❌ Email sending failed!');
                console.error('  Error:', emailError.message);
                console.error('  Code:', emailError.code);
                if (emailError.response) {
                    console.error('  Response:', emailError.response);
                }
                
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
            console.warn('⚠️ Gmail credentials incomplete');
            console.warn('  GMAIL_USER:', gmailUser);
            console.warn('  GMAIL_PASSWORD length:', gmailPassword.length);
            return {
                statusCode: 400,
                headers,
                body: JSON.stringify({
                    success: false,
                    message: 'Email service is not configured. Please contact the administrator.'
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

# Agro Farm - Website Landing Page

A modern, fully responsive website landing page for an organic farming business built with HTML5, Bootstrap 5, and JavaScript.

## Features

### ✨ Design & UI
- **Modern Green Theme**: Professional color scheme perfect for agriculture/farming
- **Fully Responsive**: Works seamlessly on desktop, tablet, and mobile devices
- **Clean Layout**: Easy-to-navigate sections with clear hierarchy
- **Professional Typography**: Beautiful fonts and spacing
- **Smooth Animations**: Engaging transitions and effects

### 🎯 Sections Included

1. **Navigation Bar**
   - Sticky header with smooth scrolling
   - Mobile-responsive hamburger menu
   - Active link indicators

2. **Hero Section**
   - Eye-catching banner with call-to-action buttons
   - Animated content
   - Gradient background

3. **About Section**
   - Company introduction
   - Key features with icons
   - Professional messaging

4. **Services Section**
   - 4 service cards with icons
   - Hover animations
   - Descriptive text

5. **Products Section**
   - Featured product showcase
   - Interactive product cards
   - Pricing information

6. **Why Choose Us**
   - Key benefits section
   - Icon-based cards
   - Compelling messaging

7. **Statistics Section**
   - Animated counters
   - Key metrics display
   - Engaging numbers

8. **Testimonials**
   - Customer reviews
   - Star ratings
   - Social proof

9. **Contact Section**
   - Multiple contact methods
   - Contact form
   - Email and phone information

10. **Footer**
    - Company information
    - Quick links
    - Social media links
    - Copyright information

### 🚀 JavaScript Features

- **Smooth Scrolling**: All navigation links scroll smoothly to sections
- **Animated Counters**: Numbers animate when they come into view
- **Form Validation**: Client-side validation for contact forms
- **Scroll-to-Top Button**: Appears when scrolling down
- **Active Link Updates**: Navigation updates based on scroll position
- **Intersection Observer**: Elements animate as they come into view
- **Modal Forms**: Get started modal with form handling
- **Responsive Design**: All JavaScript works on all screen sizes

### 📱 Responsive Breakpoints

- **Desktop**: 1200px and above
- **Tablet**: 768px - 1199px
- **Mobile**: Below 768px

All features are optimized for each screen size.

## File Structure

```
OikosOrchardandFarm/
├── index.html      # Main HTML file
├── styles.css      # CSS styling
├── script.js       # JavaScript functionality
└── README.md       # This file
```

## How to Use

### 1. **Local Setup (XAMPP)**
```bash
# Place files in your XAMPP htdocs folder
C:\xampp\htdocs\OikosOrchardandFarm\
```

2. **Open in Browser**
   - Start XAMPP Apache server
   - Navigate to: `http://localhost/OikosOrchardandFarm/`

### 2. **Direct File Access**
- Simply open `index.html` in your web browser

## Technologies Used

- **HTML5**: Semantic markup
- **Bootstrap 5**: Responsive grid system and components
- **CSS3**: Modern styling with animations and transitions
- **JavaScript (ES6)**: Interactive features and form handling
- **Font Awesome 6**: Icon library
- **Responsive Design**: Mobile-first approach

## Customization Guide

### Colors
Edit the CSS variables in `styles.css`:
```css
:root {
    --primary-green: #27ae60;
    --dark-green: #1e8449;
    --light-green: #d5f4e6;
    --text-dark: #2c3e50;
    --text-muted: #7f8c8d;
}
```

### Content
Update text and information in `index.html`:
- Company name and description
- Service names and descriptions
- Product information
- Contact details
- Testimonials
- Statistics

### Navigation Links
All navigation is in the navbar section. Update hrefs to match your section IDs.

### Forms
The contact form and get started modal can be connected to a backend service:
- Form data is logged to console
- Update the form submission handlers in `script.js`

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Performance Optimizations

- CDN-hosted Bootstrap and Font Awesome
- Lazy loading ready (infrastructure in place)
- Optimized animations using CSS and JavaScript
- Minimal dependencies
- Fast load times

## SEO Considerations

- Semantic HTML5 structure
- Meta tags for viewport and charset
- Structured content hierarchy
- Alt text ready for images
- Fast page load times

## Future Enhancements

Consider adding:
- Backend contact form handler
- Blog section
- Product cart functionality
- User authentication
- Database integration
- CMS integration
- Multi-language support
- Dark mode toggle

## License

This project is free to use and modify for personal or commercial purposes.

## Support

For questions or customization needs:
- Modify the HTML content in `index.html`
- Update styles in `styles.css`
- Extend functionality in `script.js`

## Version

Version: 1.0.0
Created: January 2026

---

**Happy Farming! 🌾**

// ================================
// REVIEWS PERSISTENCE (LocalStorage)
// ================================

const REVIEWS_STORAGE_KEY = 'oikos_reviews';

function saveReviewToStorage(reviewData) {
    let reviews = JSON.parse(localStorage.getItem(REVIEWS_STORAGE_KEY)) || [];
    reviews.unshift({
        ...reviewData,
        id: Date.now(), // Unique ID for the review
        timestamp: new Date().toISOString()
    });
    localStorage.setItem(REVIEWS_STORAGE_KEY, JSON.stringify(reviews));
}

function loadReviewsFromStorage() {
    const reviews = JSON.parse(localStorage.getItem(REVIEWS_STORAGE_KEY)) || [];
    const container = document.getElementById('testimonials-container');
    
    if (!container || reviews.length === 0) return;
    
    reviews.forEach(review => {
        // Check if review already exists in DOM
        const existingReview = container.querySelector(`[data-review-id="${review.id}"]`);
        if (!existingReview) {
            const newTestimonial = document.createElement('div');
            newTestimonial.className = 'col-md-4 testimonial-item';
            newTestimonial.setAttribute('data-review-id', review.id);
            newTestimonial.innerHTML = `
                <div class="testimonial-card">
                    <div class="stars mb-2">
                        ${Array(parseInt(review.rating)).fill('<i class="fas fa-star text-warning"></i>').join('')}
                        ${Array(5 - parseInt(review.rating)).fill('<i class="fas fa-star text-secondary"></i>').join('')}
                    </div>
                    <p class="rating-number">${review.rating}.0</p>
                    <p class="mb-3">"${review.review}"</p>
                    <h6 class="text-success">- ${review.name}</h6>
                    <small class="text-muted">${review.location}</small>
                </div>
            `;
            container.insertBefore(newTestimonial, container.firstChild);
        }
    });
}

// ================================
// PAGINATION FUNCTIONALITY
// ================================

const itemsPerPage = 3;
let currentPage = 1;
let allTestimonials = [];

function initPagination() {
    allTestimonials = Array.from(document.querySelectorAll('.testimonial-item'));
    if (allTestimonials.length === 0) return; // Exit if no testimonials exist
    
    const totalPages = Math.ceil(allTestimonials.length / itemsPerPage);
    updatePagination();
    updatePageButtons(totalPages);
}

function displayPage(pageNum) {
    const startIdx = (pageNum - 1) * itemsPerPage;
    const endIdx = startIdx + itemsPerPage;
    
    allTestimonials.forEach((item, idx) => {
        if (idx >= startIdx && idx < endIdx) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
    
    currentPage = pageNum;
    updatePagination();
}

function updatePagination() {
    const totalPages = Math.ceil(allTestimonials.length / itemsPerPage);
    
    // Update active page button
    document.querySelectorAll('.page-item').forEach(item => {
        if (item.id && item.id.startsWith('page-')) {
            item.classList.remove('active');
        }
    });
    
    const activePage = document.getElementById(`page-${currentPage}`);
    if (activePage) {
        activePage.classList.add('active');
    }
    
    // Update Previous button
    const prevBtn = document.getElementById('prev-btn');
    if (prevBtn) {
        if (currentPage === 1) {
            prevBtn.classList.add('disabled');
        } else {
            prevBtn.classList.remove('disabled');
        }
    }
    
    // Update Next button
    const nextBtn = document.getElementById('next-btn');
    if (nextBtn) {
        if (currentPage === totalPages) {
            nextBtn.classList.add('disabled');
        } else {
            nextBtn.classList.remove('disabled');
        }
    }
}

function updatePageButtons(totalPages) {
    const pagination = document.getElementById('pagination');
    if (!pagination) return; // Exit if pagination doesn't exist
    
    const prevBtn = pagination.querySelector('#prev-btn');
    const nextBtn = pagination.querySelector('#next-btn');
    
    // Remove old page buttons
    document.querySelectorAll('.page-item').forEach(item => {
        if (item.id && item.id.startsWith('page-')) {
            item.remove();
        }
    });
    
    // Add new page buttons
    for (let i = 1; i <= totalPages; i++) {
        const pageItem = document.createElement('li');
        pageItem.className = 'page-item';
        pageItem.id = `page-${i}`;
        if (i === 1) pageItem.classList.add('active');
        
        pageItem.innerHTML = `<a class="page-link" href="#" onclick="goToPage(${i}, event)">${i}</a>`;
        pagination.insertBefore(pageItem, nextBtn);
    }
}

function goToPage(pageNum, event) {
    event.preventDefault();
    displayPage(pageNum);
}

function nextPage(event) {
    event.preventDefault();
    const totalPages = Math.ceil(allTestimonials.length / itemsPerPage);
    if (currentPage < totalPages) {
        displayPage(currentPage + 1);
    }
}

function previousPage(event) {
    event.preventDefault();
    if (currentPage > 1) {
        displayPage(currentPage - 1);
    }
}

// Initialize pagination on load
document.addEventListener('DOMContentLoaded', initPagination);

// Update pagination when new review is added
const originalSubmitReview = window.submitReview;
window.submitReview = function() {
    originalSubmitReview();
    setTimeout(() => {
        initPagination();
        displayPage(1);
    }, 500);
};

// ================================
// RATING FUNCTIONALITY
// ================================

document.addEventListener('DOMContentLoaded', function() {
    const starIcons = document.querySelectorAll('.star-icon');
    const ratingValue = document.getElementById('ratingValue');
    const ratingText = document.getElementById('ratingText');
    
    const ratingTexts = {
        1: 'Poor',
        2: 'Fair',
        3: 'Good',
        4: 'Very Good',
        5: 'Excellent'
    };

    starIcons.forEach(star => {
        star.addEventListener('click', function() {
            const rating = this.getAttribute('data-rating');
            ratingValue.value = rating;
            ratingText.textContent = ratingTexts[rating];
            
            // Update star display
            starIcons.forEach(s => {
                if (s.getAttribute('data-rating') <= rating) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
        });

        star.addEventListener('mouseenter', function() {
            const rating = this.getAttribute('data-rating');
            starIcons.forEach(s => {
                if (s.getAttribute('data-rating') <= rating) {
                    s.style.color = '#ffc107';
                } else {
                    s.style.color = '#ddd';
                }
            });
        });
    });

    // Reset on mouse leave
    document.querySelector('.rating-input')?.addEventListener('mouseleave', function() {
        const currentRating = ratingValue.value;
        starIcons.forEach(s => {
            if (s.getAttribute('data-rating') <= currentRating) {
                s.classList.add('active');
                s.style.color = '#ffc107';
            } else {
                s.classList.remove('active');
                s.style.color = '#ddd';
            }
        });
    });
});

function submitReview() {
    const name = document.getElementById('reviewName').value;
    const location = document.getElementById('reviewLocation').value;
    const review = document.getElementById('reviewText').value;
    const rating = document.getElementById('ratingValue').value;

    if (!name || !location || !review || !rating) {
        showAlert('Please fill in all fields', 'danger');
        return;
    }

    // Prepare review data
    const reviewData = {
        name,
        location,
        review,
        rating
    };

    // Save to localStorage
    saveReviewToStorage(reviewData);

    // Create new testimonial card with review ID
    const reviewId = Date.now();
    const newTestimonial = document.createElement('div');
    newTestimonial.className = 'col-md-4 testimonial-item';
    newTestimonial.setAttribute('data-review-id', reviewId);
    newTestimonial.innerHTML = `
        <div class="testimonial-card">
            <div class="stars mb-2">
                ${Array(parseInt(rating)).fill('<i class="fas fa-star text-warning"></i>').join('')}
                ${Array(5 - parseInt(rating)).fill('<i class="fas fa-star text-secondary"></i>').join('')}
            </div>
            <p class="rating-number">${rating}.0</p>
            <p class="mb-3">"${review}"</p>
            <h6 class="text-success">- ${name}</h6>
            <small class="text-muted">${location}</small>
        </div>
    `;

    // Add to testimonials container
    const container = document.getElementById('testimonials-container');
    container.insertBefore(newTestimonial, container.firstChild);

    // Show success message
    showAlert('Thank you! Your review has been added.', 'success');

    // Reset form
    document.getElementById('reviewForm').reset();
    document.getElementById('ratingValue').value = '5';
    document.getElementById('ratingText').textContent = 'Excellent';
    document.querySelectorAll('.star-icon').forEach(star => {
        if (star.getAttribute('data-rating') <= '5') {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
    });

    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('reviewModal'));
    modal.hide();

    // Reinitialize pagination to include new review
    setTimeout(() => {
        initPagination();
        displayPage(1);
    }, 500);

    // Log data (for backend integration)
    console.log('New Review Data:', {
        name,
        location,
        review,
        rating,
        timestamp: new Date()
    });
}

// ================================
// COUNTER ANIMATION
// ================================

function animateCounters() {
    const counters = document.querySelectorAll('.counter');
    const speed = 50; // Lower number = faster animation

    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target'));
        let count = 0;

        const increment = Math.ceil(target / speed);

        const updateCount = () => {
            if (count < target) {
                count += increment;
                if (count > target) count = target;
                counter.textContent = count.toLocaleString();
                setTimeout(updateCount, 50);
            } else {
                counter.textContent = target.toLocaleString();
            }
        };

        // Start animation when element is in viewport
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !counter.classList.contains('animated')) {
                    counter.classList.add('animated');
                    updateCount();
                    observer.unobserve(entry.target);
                }
            });
        });

        observer.observe(counter);
    });
}

document.addEventListener('DOMContentLoaded', animateCounters);

// ================================
// SMOOTH SCROLLING
// ================================

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href !== '#' && document.querySelector(href)) {
            e.preventDefault();
            const target = document.querySelector(href);
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });

            // Close mobile menu if open
            const navbarCollapse = document.querySelector('.navbar-collapse');
            if (navbarCollapse.classList.contains('show')) {
                const navbarToggler = document.querySelector('.navbar-toggler');
                navbarToggler.click();
            }
        }
    });
});

// ================================
// NAVBAR ACTIVE LINK
// ================================

function updateActiveLink() {
    const sections = document.querySelectorAll('section');
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');

    window.addEventListener('scroll', () => {
        let current = '';

        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            if (scrollY >= sectionTop - 200) {
                current = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href').slice(1) === current) {
                link.classList.add('active');
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', updateActiveLink);

// ================================
// FORM VALIDATION & SUBMISSION
// ================================

const contactForm = document.getElementById('contactForm');

if (contactForm) {
    contactForm.addEventListener('submit', function (e) {
        e.preventDefault();

        // Get form data
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);

        // Simple validation
        if (!data.name || !data.email || !data.subject || !data.message) {
            showAlert('Please fill in all fields', 'danger');
            return;
        }

        // Email validation
        if (!isValidEmail(data.email)) {
            showAlert('Please enter a valid email address', 'danger');
            return;
        }

        // Here you would typically send the data to a server
        console.log('Form Data:', data);

        // Show success message
        showAlert('Message sent successfully! We will get back to you soon.', 'success');

        // Reset form
        this.reset();
    });
}

// ================================
// GET STARTED FORM SUBMISSION
// ================================

function submitGetStarted() {
    const form = document.getElementById('getStartedForm');
    const inputs = form.querySelectorAll('input, select');
    let isValid = true;

    // Validate all fields
    inputs.forEach(input => {
        if (!input.value) {
            isValid = false;
            input.classList.add('is-invalid');
        } else {
            input.classList.remove('is-invalid');
        }
    });

    if (!isValid) {
        showAlert('Please fill in all fields', 'danger');
        return;
    }

    // Validate email
    const emailInput = form.querySelector('input[type="email"]');
    if (!isValidEmail(emailInput.value)) {
        showAlert('Please enter a valid email address', 'danger');
        return;
    }

    // Get form data using form elements with names
    const name = form.querySelector('input[name="name"]').value;
    const email = form.querySelector('input[name="email"]').value;
    const phone = form.querySelector('input[name="phone"]').value;
    const interested = form.querySelector('select[name="interested"]').value;

    const data = {
        name: name.trim(),
        email: email.trim(),
        phone: phone.trim(),
        interested: interested.trim()
    };

    // Show loading state
    const submitButton = form.closest('.modal-content').querySelector('button[onclick="submitGetStarted()"]');
    const originalText = submitButton ? submitButton.textContent : 'Get Started';
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Submitting...';
    }

    // Send to server
    fetch(`/OikosOrchardandFarm/send-getstarted.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(responseData => {
        if (responseData.success) {
            showAlert(responseData.message, 'success');
            form.reset();
            // Reset validation classes
            inputs.forEach(input => {
                input.classList.remove('is-invalid');
            });
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('contactModal'));
            if (modal) modal.hide();
        } else {
            showAlert('Error: ' + (responseData.message || 'Failed to submit request'), 'danger');
        }
    })
    .catch(error => {
        console.error('Get Started submission error:', error);
        showAlert('Error submitting request: ' + error.message, 'danger');
    })
    .finally(() => {
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        }
    });
}

// ================================
// UTILITY FUNCTIONS
// ================================

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showAlert(message, type = 'info') {
    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.setAttribute('role', 'alert');
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    // Add to page
    const container = document.querySelector('body');
    container.insertBefore(alertDiv, container.firstChild);

    // Position it at top
    alertDiv.style.position = 'fixed';
    alertDiv.style.top = '80px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '9999';
    alertDiv.style.maxWidth = '400px';

    // Auto dismiss after 5 seconds
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alertDiv);
        bsAlert.close();
    }, 5000);
}

// ================================
// SCROLL ANIMATIONS
// ================================

function observeElements() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    };

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observe service cards
    const serviceCards = document.querySelectorAll('.service-card, .product-card, .why-card, .contact-info, .testimonial-card');
    serviceCards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });
}

document.addEventListener('DOMContentLoaded', observeElements);

// ================================
// BUTTON RIPPLE EFFECT
// ================================

function addRippleEffect() {
    const buttons = document.querySelectorAll('.btn');

    buttons.forEach(button => {
        button.addEventListener('click', function (e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;

            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');

            // Remove any existing ripples
            const existingRipple = this.querySelector('.ripple');
            if (existingRipple) {
                existingRipple.remove();
            }

            this.appendChild(ripple);
        });
    });
}

document.addEventListener('DOMContentLoaded', addRippleEffect);

// ================================
// MOBILE MENU CLOSE ON LINK CLICK
// ================================

const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
const navbarCollapse = document.querySelector('.navbar-collapse');

navLinks.forEach(link => {
    link.addEventListener('click', () => {
        if (navbarCollapse.classList.contains('show')) {
            const navbarToggler = document.querySelector('.navbar-toggler');
            navbarToggler.click();
        }
    });
});

// ================================
// SCROLL TO TOP BUTTON
// ================================

function createScrollToTopButton() {
    const scrollButton = document.createElement('button');
    scrollButton.id = 'scrollToTopBtn';
    scrollButton.innerHTML = '<i class="fas fa-arrow-up"></i>';
    scrollButton.style.cssText = `
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        background-color: #27ae60;
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        font-size: 20px;
        z-index: 999;
        display: none;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
    `;

    document.body.appendChild(scrollButton);

    // Show button when scrolling down
    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 300) {
            scrollButton.style.display = 'flex';
        } else {
            scrollButton.style.display = 'none';
        }
    });

    // Scroll to top when button is clicked
    scrollButton.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // Hover effect
    scrollButton.addEventListener('mouseover', () => {
        scrollButton.style.backgroundColor = '#1e8449';
        scrollButton.style.transform = 'translateY(-5px)';
    });

    scrollButton.addEventListener('mouseout', () => {
        scrollButton.style.backgroundColor = '#27ae60';
        scrollButton.style.transform = 'translateY(0)';
    });
}

document.addEventListener('DOMContentLoaded', createScrollToTopButton);

// ================================
// LAZY LOADING IMAGES
// ================================

if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                observer.unobserve(img);
            }
        });
    });

    document.querySelectorAll('img.lazy').forEach(img => imageObserver.observe(img));
}

// ================================
// FORM INPUT VALIDATION
// ================================

function validateFormInput(input) {
    if (input.type === 'email') {
        input.style.borderColor = isValidEmail(input.value) ? '#27ae60' : '#dc3545';
    } else if (input.value.length > 0) {
        input.style.borderColor = '#27ae60';
    } else {
        input.style.borderColor = '#e0e0e0';
    }
}

const formInputs = document.querySelectorAll('.contact-form input, .contact-form textarea, #getStartedForm input, #getStartedForm select');

formInputs.forEach(input => {
    input.addEventListener('blur', () => validateFormInput(input));
    input.addEventListener('input', () => validateFormInput(input));
});

// ================================
// PARALLAX EFFECT (Optional)
// ================================

function parallaxEffect() {
    const parallaxElements = document.querySelectorAll('[data-parallax]');

    if (parallaxElements.length === 0) return;

    window.addEventListener('scroll', () => {
        parallaxElements.forEach(element => {
            const scrollPosition = window.pageYOffset;
            const elementOffset = element.offsetTop;
            const distance = scrollPosition - elementOffset;
            element.style.backgroundPosition = `center ${distance * 0.5}px`;
        });
    });
}

document.addEventListener('DOMContentLoaded', parallaxEffect);

// ================================
// PRODUCT LIST DATA
// ================================

const productListData = {
    'Fruits': [
        { item: 'Banana Kardava', pack: '25 kg / week' },
        { item: 'Banana Mondo*', pack: 'per kg' },
        { item: 'Banana Morado*', pack: 'per kg' },
        { item: 'Banana Sab-a*', pack: 'per kg' },
        { item: 'Banana Senyorita*', pack: 'per kg' },
        { item: 'Banana Tindok*', pack: 'per kg' },
        { item: 'Banana Tondan*', pack: 'per kg' },
        { item: 'Biasong*', pack: 'per kg' },
        { item: 'Bisaya Bayabas (aromatic)', pack: 'per kg' },
        { item: 'Doldol (Seasonal)', pack: '1 kg / week' },
        { item: 'Dragon Fruit* (Seasonal)', pack: 'per kg' },
        { item: 'Guapple', pack: '3 kg / week' },
        { item: 'Inyam (Seasonal)', pack: 'per kg' },
        { item: 'Katmon* (Seasonal)', pack: '2 kg / week' },
        { item: 'Kamias / Iba (Seasonal)', pack: '200 g / week' },
        { item: 'Karamay / Chinese Iba*', pack: '200 g / week' },
        { item: 'Lemon Meyer', pack: 'per kg' },
        { item: 'Lemon Lime', pack: 'per kg' },
        { item: 'Lemonsito', pack: 'per kg' },
        { item: 'Lomboy* (Seasonal)', pack: '5 kg / week' },
        { item: 'Mansanitas', pack: '100 g' },
        { item: 'Miracle Fruit', pack: 'per piece' },
        { item: 'Mulberries*', pack: '100 g' },
        { item: 'Papaya Red Lady', pack: 'per kg' },
        { item: 'Passion Fruit*', pack: 'per kg' },
        { item: 'Sambag / Tamarind*', pack: 'per kg' },
        { item: 'Tagpo', pack: '100 g' },
        { item: 'Tambis* (Seasonal)', pack: 'per kg' }
    ],
    'Vegetables': [
        { item: 'Alugbati / Spinach', pack: '200 g' },
        { item: 'Himbabao / Alukon*', pack: '100 g' },
        { item: 'Kamunggay / Moringa', pack: '200 g' },
        { item: 'Kamunggay / Moringa (de-stemmed)', pack: '200 g' }
    ],
    'Herbs': [
        { item: 'Basil Holy', pack: '50 g' },
        { item: 'Basil Thai', pack: '50 g' },
        { item: 'Chives', pack: '100 g' },
        { item: 'Cilantro Mexican', pack: '200 g' },
        { item: 'Cilantro', pack: '100 g' },
        { item: 'Indian Curry', pack: '50 g' },
        { item: 'Guava Fresh Leaves', pack: '200 g' },
        { item: 'Lavender', pack: '50 g' },
        { item: 'Mint Pepper', pack: '50 g' },
        { item: 'Mint Eucalyptus', pack: '50 g' },
        { item: 'Oregano / Kalabo', pack: '50 g' },
        { item: 'Oregano Italian', pack: '50 g' },
        { item: 'Pandan', pack: '100 g' },
        { item: 'Root Beer', pack: '50 g' },
        { item: 'Rosemary', pack: '100 g' },
        { item: 'Sibuyas Dahonan', pack: '100 g' },
        { item: 'Tarragon', pack: '25 g' },
        { item: 'Thyme', pack: '25 g' }
    ],
    'Spices': [
        { item: 'Achuete / Annatto (Dried)', pack: '100 g' },
        { item: 'Bantiyong / Ash Gourd', pack: 'per kg' },
        { item: 'Ginger / Luy-a Dulaw', pack: '100 g' },
        { item: 'Ginger / Luy-a Bisaya', pack: '100 g' },
        { item: 'Ginger / Luy-a', pack: '100 g' },
        { item: 'Lemongrass', pack: '100 g' },
        { item: 'Sili Espada', pack: '100 g' },
        { item: 'Sili Kulikot', pack: '100 g' },
        { item: 'Sili Puti', pack: '100 g' },
        { item: 'Sugarcane / Tubó Tapol (Fresh)', pack: 'per kg' },
        { item: 'Turmeric', pack: '100 g' },
        { item: 'Cinnamon Fresh Leaves (Mana Mindanao)', pack: '10 g' },
        { item: 'Cinnamon Air-Dried Leaves (Mana Mindanao)', pack: '10 g' },
        { item: 'Cinnamon Fresh Leaves (Kaningag Cebu)', pack: '5 g' },
        { item: 'Cinnamon Air-Dried Leaves (Kaningag Cebu)', pack: '5 g' }
    ],
    'Edible Flowers': [
        { item: 'Banana Pusô', pack: '10 pcs' },
        { item: 'Blue Ternate', pack: '25 g' },
        { item: 'Bougainvillea', pack: '25 g' },
        { item: 'Hibiscus', pack: '50 g' },
        { item: 'Marigold Orange', pack: '50 g' },
        { item: 'Rose Red Local', pack: '50 g' },
        { item: 'Roselle (Seasonal)', pack: '100 g' }
    ],
    'From the Wild': [
        { item: 'Taklong / Tree Snail Escargot', pack: '1 kg' },
        { item: 'Pepinito', pack: '100 g' },
        { item: 'Wild Passion Fruit / Sto Papa', pack: '100 g' }
    ],
    'Eggs & Meat': [
        { item: 'Native Eggs', pack: '1 tray / week' },
        { item: 'Native Pig Hybrid (Live)*', pack: 'per kg' }
    ],
    'Slow Fresh Drinks': [
        { item: 'Tubâ', pack: '0–12 hours' },
        { item: 'Tubâ with Tungog', pack: '0–12 hours' },
        { item: 'Tubâ', pack: '12–24 hours' },
        { item: 'Tubâ with Tungog', pack: '12–24 hours' },
        { item: 'Coconut Buko', pack: 'per piece' },
        { item: 'Coconut Buko (50+)', pack: 'per piece' }
    ]
};

// Show product list in modal
window.showProductList = function(category) {
    const modal = new bootstrap.Modal(document.getElementById('productsListModal'));
    const products = productListData[category] || [];
    
    // Update modal title
    document.getElementById('productModalTitle').textContent = category;
    
    // Populate table
    const tableBody = document.getElementById('productListBody');
    tableBody.innerHTML = '';
    
    products.forEach(product => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${product.item}</td>
            <td>${product.pack}</td>
        `;
        tableBody.appendChild(row);
    });
    
    modal.show();
};

// ================================
// INITIALIZATION
// ================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('Agro Farm Website - JavaScript loaded successfully');
    console.log('showProductList function available:', typeof window.showProductList);
    
    // Load saved reviews from localStorage
    loadReviewsFromStorage();
    
    // Initialize pagination after reviews are loaded
    setTimeout(() => {
        initPagination();
    }, 100);
    
    // Initialize booking form handlers
    initializeBookingForms();
});

// ================================
// BOOKING FORM SUBMISSION
// ================================

function initializeBookingForms() {
    // Handle "Book Now" buttons
    document.querySelectorAll('button').forEach(btn => {
        if (btn.textContent.includes('Book Now')) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                showBookingForm(this);
            });
        }
    });
}

function showBookingForm(buttonElement) {
    // Get package name from the card
    const packageName = buttonElement.closest('.product-card, .glamping-package') 
        ? buttonElement.closest('.product-card, .glamping-package').querySelector('h5')?.textContent || 'Package'
        : 'Package';
    
    const packagePrice = buttonElement.closest('.product-card, .glamping-package')
        ? buttonElement.closest('.product-card, .glamping-package').querySelector('.price')?.textContent || '₱0.00'
        : '₱0.00';

    // Store the package info in session/local storage for the form
    localStorage.setItem('selectedPackage', JSON.stringify({
        name: packageName,
        price: packagePrice
    }));

    // Show booking modal (or redirect to booking page)
    const bookingModal = document.getElementById('bookingModal');
    if (bookingModal) {
        const modal = new bootstrap.Modal(bookingModal);
        modal.show();
        populateBookingForm(packageName, packagePrice);
    } else {
        // If no modal, redirect to booking page or show alert
        alert('Package: ' + packageName + '\n\nPlease fill in your details to book this package.');
    }
}

function populateBookingForm(packageName, packagePrice) {
    // Update booking form with selected package
    const packageInput = document.getElementById('packageName');
    const packagePriceInput = document.getElementById('packagePrice');
    
    if (packageInput) packageInput.value = packageName;
    if (packagePriceInput) packagePriceInput.value = packagePrice;
}

function updatePackageFromSelect() {
    // Get the selected value from the dropdown
    const packageSelect = document.getElementById('packageSelect');
    if (!packageSelect || !packageSelect.value) {
        document.getElementById('packageName').value = '';
        document.getElementById('packagePrice').value = '';
        return;
    }
    
    // Parse package name and price from the selected value
    const [packageName, packagePrice] = packageSelect.value.split('|');
    
    // Format price with comma separator
    const formattedPrice = '₱' + parseInt(packagePrice).toLocaleString('en-US');
    
    // Update the form fields
    document.getElementById('packageName').value = packageName;
    document.getElementById('packagePrice').value = formattedPrice;
}

// Function to set package directly (used by onclick handlers)
function setPackage(packageName, price) {
    document.getElementById('packageName').value = packageName;
    document.getElementById('packagePrice').value = '₱' + price.toLocaleString('en-US');
}

function submitBooking(event) {
    if (event) event.preventDefault();

    // Get form data
    const form = document.getElementById('bookingForm');
    if (!form) {
        console.error('Booking form not found');
        alert('Booking form not found on this page');
        return;
    }

    const fullName = document.getElementById('bookingFullName')?.value?.trim() || '';
    const email = document.getElementById('bookingEmail')?.value?.trim() || '';
    const phone = document.getElementById('bookingPhone')?.value?.trim() || '';
    const checkinDate = document.getElementById('bookingDate')?.value || '';
    const guests = document.getElementById('bookingGuests')?.value || '';
    const packageName = document.getElementById('packageName')?.value?.trim() || '';
    const packagePrice = document.getElementById('packagePrice')?.value?.trim() || '';
    const specialRequests = document.getElementById('bookingRequests')?.value?.trim() || '';

    // Validate required fields
    if (!fullName || !email || !phone || !checkinDate || !guests || !packageName) {
        console.warn('Validation failed:', { fullName, email, phone, checkinDate, guests, packageName });
        alert('Please fill in all required fields:\n- Full Name\n- Email\n- Phone\n- Check-in Date\n- Number of Guests\n- Package Name');
        return;
    }

    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address');
        return;
    }

    // Prepare data
    const bookingData = {
        fullName: fullName,
        email: email,
        phone: phone,
        checkinDate: checkinDate,
        guests: guests,
        packageName: packageName,
        packagePrice: packagePrice,
        specialRequests: specialRequests
    };

    console.log('Submitting booking:', bookingData);

    // Show loading state
    const submitButton = document.querySelector('#bookingModal button[onclick="submitBooking()"]');
    const originalText = submitButton ? submitButton.textContent : 'Submit Booking Request';
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Submitting...';
    }

    // Send to server
    fetch('/.netlify/functions/send-booking', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(bookingData)
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Booking response:', data);
        if (data.success) {
            // Show success modal with animation
            showSuccessModal('Thank you for booking!', data.message);
            form.reset();
            // Reset package fields
            document.getElementById('packageName').value = '';
            document.getElementById('packagePrice').value = '';
            // Close modal if exists
            const modalElement = document.getElementById('bookingModal');
            if (modalElement && window.bootstrap) {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    setTimeout(() => modal.hide(), 2500);
                }
            }
        } else {
            alert('Error: ' + (data.message || 'Failed to submit booking'));
        }
    })
    .catch(error => {
        console.error('Booking submission error:', error);
        alert('Error submitting booking: ' + error.message);
    })
    .finally(() => {
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        }
    });
}

// ================================
// SUCCESS MODAL WITH ANIMATION
// ================================

function showSuccessModal(title, message) {
    // Create modal HTML if it doesn't exist
    let modalContainer = document.getElementById('success-modal-container');
    if (!modalContainer) {
        modalContainer = document.createElement('div');
        modalContainer.id = 'success-modal-container';
        document.body.appendChild(modalContainer);
        
        // Add CSS for animations
        const style = document.createElement('style');
        style.textContent = `
            #success-modal-container {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                display: none;
                align-items: center;
                justify-content: center;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 9999;
                animation: fadeIn 0.3s ease-in-out;
            }
            
            #success-modal-container.show {
                display: flex;
            }
            
            .success-modal-content {
                background: white;
                border-radius: 15px;
                padding: 40px;
                text-align: center;
                max-width: 400px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
                animation: slideUp 0.4s ease-out;
            }
            
            .success-checkmark {
                width: 80px;
                height: 80px;
                margin: 0 auto 20px;
            }
            
            .checkmark-circle {
                width: 100%;
                height: 100%;
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
                animation: scaleIn 0.5s ease-out 0.2s both;
            }
            
            .checkmark-circle svg {
                width: 50px;
                height: 50px;
                filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
            }
            
            .checkmark-path {
                stroke-dasharray: 48;
                stroke-dashoffset: 48;
                animation: checkmark 0.6s ease-out 0.7s forwards;
            }
            
            .success-title {
                font-size: 24px;
                font-weight: 600;
                color: #333;
                margin-bottom: 10px;
                animation: fadeInUp 0.5s ease-out 0.3s both;
            }
            
            .success-message {
                font-size: 14px;
                color: #666;
                margin-bottom: 0;
                animation: fadeInUp 0.5s ease-out 0.5s both;
            }
            
            @keyframes fadeIn {
                from {
                    opacity: 0;
                }
                to {
                    opacity: 1;
                }
            }
            
            @keyframes slideUp {
                from {
                    transform: translateY(30px);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }
            
            @keyframes scaleIn {
                from {
                    transform: scale(0);
                }
                to {
                    transform: scale(1);
                }
            }
            
            @keyframes checkmark {
                to {
                    stroke-dashoffset: 0;
                }
            }
            
            @keyframes fadeInUp {
                from {
                    transform: translateY(10px);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }
            
            @keyframes fadeOut {
                from {
                    opacity: 1;
                }
                to {
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    // Build the modal HTML
    modalContainer.innerHTML = `
        <div class="success-modal-content">
            <div class="success-checkmark">
                <div class="checkmark-circle">
                    <svg viewBox="0 0 52 52" xmlns="http://www.w3.org/2000/svg">
                        <polyline 
                            class="checkmark-path" 
                            points="13 24 19 30 39 14" 
                            fill="none" 
                            stroke="white" 
                            stroke-width="3" 
                            stroke-linecap="round" 
                            stroke-linejoin="round"
                        />
                    </svg>
                </div>
            </div>
            <h3 class="success-title">${title}</h3>
            ${message ? `<p class="success-message">${message}</p>` : ''}
        </div>
    `;
    
    // Show the modal
    modalContainer.classList.add('show');
    
    // Auto-hide after 2.5 seconds with fade out
    setTimeout(() => {
        modalContainer.style.animation = 'fadeOut 0.3s ease-out forwards';
        setTimeout(() => {
            modalContainer.classList.remove('show');
            modalContainer.style.animation = '';
        }, 300);
    }, 2500);
}


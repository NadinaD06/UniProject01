<?php
/**
 * Home view - Landing page
 */
$page_title = 'ArtSpace - Connect, Create, Inspire';
$page_description = 'Join ArtSpace, the social media platform for artists to share, connect, and grow their creative skills.';
$body_class = 'home-page';
?>

<div class="hero-section">
    <div class="hero-content">
        <h1>Connect. Create. Inspire.</h1>
        <p class="hero-subtitle">Join the community where artists thrive together</p>
        <div class="hero-buttons">
            <a href="/register" class="btn btn-primary">Join Now</a>
            <a href="/login" class="btn btn-secondary">Log In</a>
        </div>
    </div>
    <div class="hero-image">
        <img src="/assets/images/hero-artwork.png" alt="ArtSpace Community">
    </div>
</div>

<div class="features-section">
    <div class="container">
        <h2 class="section-title">Why Join ArtSpace?</h2>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-paint-brush"></i>
                </div>
                <h3>Showcase Your Art</h3>
                <p>Share your creations with a community that appreciates art in all its forms.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Connect With Artists</h3>
                <p>Follow artists you admire and build connections with creative minds worldwide.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h3>Engage & Discuss</h3>
                <p>Get feedback, share techniques, and engage in meaningful conversations about art.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <h3>Find Inspiration</h3>
                <p>Discover new styles, techniques, and perspectives to fuel your creative journey.</p>
            </div>
        </div>
    </div>
</div>

<div class="community-section">
    <div class="container">
        <h2 class="section-title">Join Our Growing Community</h2>
        
        <div class="stats-row">
            <div class="stat-box">
                <div class="stat-number">10,000+</div>
                <div class="stat-label">Active Artists</div>
            </div>
            
            <div class="stat-box">
                <div class="stat-number">50,000+</div>
                <div class="stat-label">Artworks Shared</div>
            </div>
            
            <div class="stat-box">
                <div class="stat-number">100+</div>
                <div class="stat-label">Countries</div>
            </div>
        </div>
        
        <div class="cta-block">
            <h3>Ready to showcase your creativity?</h3>
            <a href="/register" class="btn btn-primary btn-large">Create Your Account</a>
        </div>
    </div>
</div>

<div class="testimonials-section">
    <div class="container">
        <h2 class="section-title">What Our Artists Say</h2>
        
        <div class="testimonials-slider">
            <div class="testimonial">
                <div class="testimonial-content">
                    <p>"ArtSpace transformed my artistic journey. The feedback I've received has been invaluable, and the connections I've made with other artists have opened up so many opportunities."</p>
                </div>
                <div class="testimonial-author">
                    <img src="/assets/images/testimonials/user1.jpg" alt="Sarah J.">
                    <div class="author-info">
                        <h4>Sarah J.</h4>
                        <p>Digital Illustrator</p>
                    </div>
                </div>
            </div>
            
            <div class="testimonial">
                <div class="testimonial-content">
                    <p>"As a traditional painter, I was hesitant to join an online platform. But ArtSpace's supportive community helped me not only share my work but also explore new mediums and techniques."</p>
                </div>
                <div class="testimonial-author">
                    <img src="/assets/images/testimonials/user2.jpg" alt="Miguel C.">
                    <div class="author-info">
                        <h4>Miguel C.</h4>
                        <p>Traditional Painter</p>
                    </div>
                </div>
            </div>
            
            <div class="testimonial">
                <div class="testimonial-content">
                    <p>"The exposure I've gained through ArtSpace has been amazing. My work has been seen by people from around the world, and I've even received commission opportunities through the platform."</p>
                </div>
                <div class="testimonial-author">
                    <img src="/assets/images/testimonials/user3.jpg" alt="Aisha T.">
                    <div class="author-info">
                        <h4>Aisha T.</h4>
                        <p>Mixed Media Artist</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="faq-section">
    <div class="container">
        <h2 class="section-title">Frequently Asked Questions</h2>
        
        <div class="faq-grid">
            <div class="faq-item">
                <h3>Is ArtSpace free to use?</h3>
                <p>Yes, ArtSpace is completely free to join and use. You can create an account, share your artwork, and connect with other artists at no cost.</p>
            </div>
            
            <div class="faq-item">
                <h3>What types of art can I share?</h3>
                <p>ArtSpace welcomes all visual art forms, including digital art, traditional paintings, sculptures, photography, crafts, and more.</p>
            </div>
            
            <div class="faq-item">
                <h3>How do I protect my artwork from being copied?</h3>
                <p>All images uploaded to ArtSpace are displayed with your copyright information. We also enable right-click protection and offer watermarking options for your uploads.</p>
            </div>
            
            <div class="faq-item">
                <h3>Can I sell my artwork on ArtSpace?</h3>
                <p>While ArtSpace is primarily a platform for sharing and connecting, you can indicate if your work is available for purchase and provide contact information for interested buyers.</p>
            </div>
        </div>
    </div>
</div>

<div class="cta-section">
    <div class="container">
        <h2>Start Your Artistic Journey Today</h2>
        <p>Join thousands of artists sharing their work, finding inspiration, and building meaningful connections.</p>
        <div class="cta-buttons">
            <a href="/register" class="btn btn-primary btn-large">Create Account</a>
            <a href="/explore" class="btn btn-outline btn-large">Explore Art</a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Simple testimonial slider
        let currentSlide = 0;
        const testimonials = document.querySelectorAll('.testimonial');
        const totalSlides = testimonials.length;
        
        function showSlide(index) {
            // Hide all slides
            testimonials.forEach(slide => {
                slide.style.display = 'none';
            });
            
            // Show current slide
            testimonials[index].style.display = 'block';
        }
        
        // Initialize slider
        showSlide(currentSlide);
        
        // Auto advance slides
        setInterval(function() {
            currentSlide = (currentSlide + 1) % totalSlides;
            showSlide(currentSlide);
        }, 5000);
    });
</script>
<div class="container" style="padding: 40px 0;">
    <div class="row">
        <div class="col-lg-9 mx-auto">
            
            <h1 style="margin-bottom: 30px; color: #667eea;">Contact Us</h1>
            
            <p style="font-size: 16px; margin-bottom: 40px;">Have questions, suggestions, or feedback? We'd love to hear from you! Fill out the form below and we'll get back to you as soon as possible.</p>
            
            <div class="row">
                
                <!-- Contact Form -->
                <div class="col-lg-7 mb-4">
                    <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        
                        <?php if ($message_sent): ?>
                            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                                <strong>✅ Success!</strong> Your message has been sent successfully. We'll get back to you soon.
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error_message): ?>
                            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                                <strong>❌ Error!</strong> <?php echo htmlspecialchars($error_message, ENT_QUOTES); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Your Name *</label>
                                <input type="text" 
                                       name="name" 
                                       required 
                                       style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 14px;"
                                       placeholder="Enter your full name">
                            </div>
                            
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Email Address *</label>
                                <input type="email" 
                                       name="email" 
                                       required 
                                       style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 14px;"
                                       placeholder="your@email.com">
                            </div>
                            
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Subject *</label>
                                <input type="text" 
                                       name="subject" 
                                       required 
                                       style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 14px;"
                                       placeholder="What is your query about?">
                            </div>
                            
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Message *</label>
                                <textarea name="message" 
                                          required 
                                          rows="6" 
                                          style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 14px; resize: vertical;"
                                          placeholder="Type your message here..."></textarea>
                            </div>
                            
                            <button type="submit" 
                                    name="submit_contact"
                                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; border: none; border-radius: 6px; font-size: 16px; font-weight: 600; cursor: pointer; width: 100%;">
                                ✉️ Send Message
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Contact Information -->
                <div class="col-lg-5 mb-4">
                    
                    <!-- Email -->
                    <div style="background: #f8f9fa; padding: 25px; border-radius: 10px; margin-bottom: 20px;">
                        <div style="font-size: 40px; margin-bottom: 15px;">📧</div>
                        <h3 style="font-size: 18px; color: #667eea; margin-bottom: 10px;">Email Us</h3>
                        <p style="margin: 0; font-size: 14px; color: #666;">General Inquiries:</p>
                        <p style="margin: 5px 0; font-size: 16px;"><a href="mailto:info@yoursite.com" style="color: #333; text-decoration: none;">info@yoursite.com</a></p>
                        <p style="margin: 15px 0 0 0; font-size: 14px; color: #666;">Support:</p>
                        <p style="margin: 5px 0; font-size: 16px;"><a href="mailto:support@yoursite.com" style="color: #333; text-decoration: none;">support@yoursite.com</a></p>
                    </div>
                    
                    <!-- India Post Helpline -->
                    <div style="background: #f8f9fa; padding: 25px; border-radius: 10px; margin-bottom: 20px;">
                        <div style="font-size: 40px; margin-bottom: 15px;">📞</div>
                        <h3 style="font-size: 18px; color: #667eea; margin-bottom: 10px;">India Post Helpline</h3>
                        <p style="margin: 0; font-size: 14px; color: #666;">Customer Care (Toll-Free):</p>
                        <p style="margin: 5px 0; font-size: 20px; font-weight: 600;"><a href="tel:18001112011" style="color: #333; text-decoration: none;">1800-11-2011</a></p>
                    </div>
                    
                    <!-- Official Resources -->
                    <div style="background: #f8f9fa; padding: 25px; border-radius: 10px;">
                        <div style="font-size: 40px; margin-bottom: 15px;">🔗</div>
                        <h3 style="font-size: 18px; color: #667eea; margin-bottom: 15px;">Official Resources</h3>
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <li style="margin-bottom: 10px;">
                                <a href="https://www.indiapost.gov.in" target="_blank" style="color: #667eea; text-decoration: none; font-size: 14px;">
                                    → India Post Official Website
                                </a>
                            </li>
                            <li style="margin-bottom: 10px;">
                                <a href="https://complaints.indiapost.gov.in" target="_blank" style="color: #667eea; text-decoration: none; font-size: 14px;">
                                    → File Complaint
                                </a>
                            </li>
                            <li style="margin-bottom: 10px;">
                                <a href="https://www.indiapost.gov.in/vas/Pages/trackconsignment.aspx" target="_blank" style="color: #667eea; text-decoration: none; font-size: 14px;">
                                    → Track Speed Post
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                </div>
                
            </div>
            
            <!-- FAQ Section -->
            <div style="margin-top: 60px;">
                <h2 style="color: #667eea; margin-bottom: 30px;">Frequently Asked Questions</h2>
                
                <div style="background: white; border-left: 4px solid #667eea; padding: 20px; margin-bottom: 15px; border-radius: 0 8px 8px 0;">
                    <h3 style="font-size: 18px; margin-bottom: 10px; color: #333;">How accurate is your PIN code data?</h3>
                    <p style="margin: 0; font-size: 14px; color: #666;">Our data is sourced from India Post's official database and is regularly updated to ensure accuracy.</p>
                </div>
                
                <div style="background: white; border-left: 4px solid #667eea; padding: 20px; margin-bottom: 15px; border-radius: 0 8px 8px 0;">
                    <h3 style="font-size: 18px; margin-bottom: 10px; color: #333;">Is your service really free?</h3>
                    <p style="margin: 0; font-size: 14px; color: #666;">Yes! Our service is completely free with no hidden charges. No registration required.</p>
                </div>
                
                <div style="background: white; border-left: 4px solid #667eea; padding: 20px; margin-bottom: 15px; border-radius: 0 8px 8px 0;">
                    <h3 style="font-size: 18px; margin-bottom: 10px; color: #333;">How often do you update the database?</h3>
                    <p style="margin: 0; font-size: 14px; color: #666;">We update our database regularly to reflect changes in India Post's system.</p>
                </div>
                
                <div style="background: white; border-left: 4px solid #667eea; padding: 20px; margin-bottom: 15px; border-radius: 0 8px 8px 0;">
                    <h3 style="font-size: 18px; margin-bottom: 10px; color: #333;">Can I use your data for commercial purposes?</h3>
                    <p style="margin: 0; font-size: 14px; color: #666;">Please review our <a href="/terms-of-service" style="color: #667eea;">Terms of Service</a> for usage guidelines.</p>
                </div>
                
            </div>
            
        </div>
    </div>
</div>
<?php
/**
 * Configuration file for RoundTours - Tours and Travel Landing Page
 * 
 * This file contains global configuration variables for your website
 */

// Site information
$site_config = [
    'site_name' => 'RoundTours',
    'site_tagline' => 'Tours and Travel Landing Page',
    'site_url' => 'https://yourdomain.com',
    'company_phone' => '+(1) 123 456 7890',
    'company_email' => 'hi@example.com',
    'copyright_year' => date('Y'),
];

// Navigation menu
$nav_menu = [
    ['link' => '#deals', 'text' => 'Deals'],
    ['link' => '#offers', 'text' => 'Offers'],
    ['link' => '#holidays', 'text' => 'Holidays'],
    ['link' => '#review', 'text' => 'Review'],
];

// Languages
$languages = ['English', 'Russian', 'French'];

// Currencies
$currencies = ['INR', 'USD', 'EUR'];

// Social media
$social_media = [
    ['icon' => 'facebook', 'link' => 'https://facebook.com/'],
    ['icon' => 'twitter-x', 'link' => 'https://twitter.com/'],
    ['icon' => 'linkedin', 'link' => 'https://linkedin.com/'],
    ['icon' => 'instagram', 'link' => 'https://instagram.com/'],
    ['icon' => 'whatsapp', 'link' => 'https://whatsapp.com/'],
];

// Footer links
$footer_links = [
    'company' => ['About Us', 'Careers', 'Blog', 'Press', 'Offers', 'Deals'],
    'support' => ['Contact', 'Legal Notice', 'Privacy Policy', 'Terms and Conditions', 'Sitemap'],
    'services' => ['Bus', 'Activity Finder', 'Tour List', 'Flight Search', 'Cruise Ticket', 'Holidays', 'Travel Agents'],
    'legal' => ['Privacy', 'Terms', 'Site Map'],
];

// Function to get site configuration
function get_site_config($key = null) {
    global $site_config;
    
    if ($key && isset($site_config[$key])) {
        return $site_config[$key];
    }
    
    return $site_config;
}
<script setup>
import GuessLayout from '../layouts/GuessLayout.vue';
import { ref, onMounted } from 'vue';

const isActive = ref('home');
const scrollToSection = (sectionId) => {
  document.getElementById(sectionId)?.scrollIntoView({ 
    behavior: 'smooth' 
  });
};
</script>

<template>
    <div class="min-h-screen bg-white text-gray-900">
      <!-- Navigation Bar -->
      <nav class="bg-white/90 backdrop-blur-xl fixed w-full top-0 z-50 border-b border-gray-200">
        <div class="container mx-auto px-6">
          <div class="flex items-center justify-between h-16">
            <!-- Logo -->
            <div class="flex items-center space-x-3">
              <div class="w-12 h-12 rounded flex items-center justify-center">
                <img src="../assets/LABTrack.png" alt="" srcset="">
              </div>
                  <p class="font-[Orbitron] text-sm font-bold">LAB<span class="text-[#12e19f] ">TRACK</span></p>
            </div>

            <!-- Navigation Links -->
            <div class="hidden md:flex items-center space-x-8">
              <button @click="scrollToSection('home')" 
              class="text-gray-600 hover:text-black transition-colors text-sm focus:outline-none">
                Home
              </button>
              <button @click="scrollToSection('about')" 
              class="text-gray-600 hover:text-black transition-colors text-sm focus:outline-none">
                About
              </button>
              <button @click="scrollToSection('features')" 
              class="text-gray-600 hover:text-black transition-colors text-sm focus:outline-none">
                Features
              </button>
              <button @click="scrollToSection('benefits')" 
              class="text-gray-600 hover:text-black transition-colors text-sm focus:outline-none">
                Benefits
              </button>
              <button @click="scrollToSection('contact')" 
              class="text-gray-600 hover:text-black transition-colors text-sm focus:outline-none">
                Contact
              </button>
            </div>

            <!-- Auth Buttons -->
            <div class="flex items-center space-x-3">
              <!-- <router-link
                to="/login"
                
                class="text-gray-600 hover:text-black transition-colors text-sm"
              >
                Login
              </router-link> -->
              <router-link
                to="/request-account"
                class="bg-black rounded-[100px] text-white px-4 py-2 text-xs font-medium hover:bg-gray-800 transition-colors"
              >
                Request Access
              </router-link>
            </div>
          </div>
        </div>
      </nav>

      <!-- Hero Section -->
      <section id="home" class="pt-24 pb-16">
        <div class="container mx-auto px-6">
          <div class="max-w-4xl mx-auto text-center">
            <!-- Main Logo & Title -->
            <div class="mb-8">
              <div class="w-25 h-25 rounded-lg flex items-center justify-center mx-auto mb-4">
                  <img src="../assets/LABTrackv2.png" alt="" srcset="">
              </div>
              <h1 class="text-4xl md:text-5xl font-bold mb-2 font-[Orbitron]">
                LAB<span class="text-[#12e19f]">TRACK</span>
              </h1>
              <p class="text-sm text-gray-500 uppercase tracking-wider mb-4">
                Computer Laboratory Tracker
              </p>
              <!-- <p class="text-gray-500 text-sm">
                Saint Francis Xavier College
              </p> -->
            </div>

            <!-- Subtitle -->
            <p class="text-xl text-gray-700 mb-8 max-w-2xl mx-auto">
              Comprehensive Laboratory Management System for Academic Excellence
            </p>

            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
              <router-link
                to="/login"
                class="bg-black text-white px-8 py-3 font-medium hover:bg-gray-800 transition-colors"
              >
                Get Started
              </router-link>
              <button 
                @click="scrollToSection('about')"
                disabled
                class="border border-gray-400 text-gray-700 px-8 py-3 font-medium hover:border-black hover:text-black transition-colors"
              >
                Learn More
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- About Section -->
      <section id="about" class="py-16 bg-gray-50">
        <div class="container mx-auto px-6">
          <div class="max-w-4xl mx-auto text-center">
            <h2 class="text-3xl font-bold mb-6">About LabTrack</h2>
            <p class="text-gray-700 text-lg max-w-3xl mx-auto leading-relaxed">
              LabTrack is a school-based Laboratory Management System that helps monitor equipment,
              schedule usage, 
              and track maintenance with ease. It ensures accountability, 
              efficiency, and transparency in laboratory operations while giving students and faculty a modern,
               organized way to access lab resources.
            </p>
          </div>
        </div>
      </section>

<!-- Features Section -->
<section id="features" class="py-16">
  <div class="container mx-auto px-6">
    <div class="max-w-4xl mx-auto">
      <h2 class="text-3xl font-bold text-center mb-12">Core Features</h2>
      
      <div class="grid md:grid-cols-3 gap-8">
        
        <!-- User Role Management -->
        <div class="bg-white border border-gray-200 p-6 hover:border-gray-400 transition-colors shadow-sm">
          <div class="w-12 h-12 bg-black rounded flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2
                   c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 
                   015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857
                   m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 
                   11-6 0 3 3 0 016 0z"/>
            </svg>
          </div>
          <h3 class="text-xl font-semibold mb-3">User Role Management</h3>
          <p class="text-gray-600">
            Provides different access levels for <b>admins, faculty, and students</b>. 
            Ensures secure handling of data and permissions.
          </p>
        </div>

        <!-- Reporting & Analytics -->
        <div class="bg-white border border-gray-200 p-6 hover:border-gray-400 transition-colors shadow-sm">
          <div class="w-12 h-12 bg-black rounded flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 
                   00-2 2v6a2 2 0 002 2h2a2 2 0 
                   002-2zm0 0V9a2 2 0 012-2h2a2 2 0 
                   012 2v10m-6 0a2 2 0 002 2h2a2 2 
                   0 002-2m0 0V5a2 2 0 012-2h2a2 2 
                   0 012 2v14a2 2 0 01-2 2h-2a2 2 
                   0 01-2-2z"/>
            </svg>
          </div>
          <h3 class="text-xl font-semibold mb-3">Reporting & Analytics</h3>
          <p class="text-gray-600">
            Generates <b>usage, inventory, and maintenance reports</b> while 
            providing insights for smarter decision-making and resource planning.
          </p>
        </div>

        <!-- Maintenance & Issue Tracking -->
        <div class="bg-white border border-gray-200 p-6 hover:border-gray-400 transition-colors shadow-sm">
          <div class="w-12 h-12 bg-black rounded flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12h6m2 8H7a2 2 0 01-2-2V6a2 2 
                   0 012-2h5l2 2h5a2 2 0 012 2v10a2 
                   2 0 01-2 2z"/>
            </svg>
          </div>
          <h3 class="text-xl font-semibold mb-3">Usage Monitoring & Accountability</h3>
            <p class="text-gray-600">
              Records <b>which student is using which computer</b> in real time. 
              Ensures that every computer is <b>assigned and traceable</b> to a specific student, 
              helping promote responsibility, prevent misuse, and reduce losses or damages.
            </p>
        </div>

      </div>
    </div>
  </div>
</section>


      <!-- Benefits Section -->
      <section id="benefits" class="py-16 bg-gray-50">
        <div class="container mx-auto px-6">
          <div class="max-w-4xl mx-auto">
            <h2 class="text-3xl font-bold text-center mb-12">Why Choose LabTrack?</h2>
            
            <div class="grid md:grid-cols-2 gap-8">
              <div class="flex items-start space-x-4">
                <div class="w-6 h-6 bg-black rounded flex items-center justify-center flex-shrink-0 mt-1">
                  <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                  </svg>
                </div>
                <div>
                  <h4 class="font-semibold text-black mb-2">Academic Integration</h4>
                  <p class="text-gray-600">Specifically designed for educational institutions with full academic calendar and curriculum support.</p>
                </div>
              </div>
              
              <div class="flex items-start space-x-4">
                <div class="w-6 h-6 bg-black rounded flex items-center justify-center flex-shrink-0 mt-1">
                  <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                  </svg>
                </div>
                <div>
                  <h4 class="font-semibold text-black mb-2">Safety Compliance</h4>
                  <p class="text-gray-600">Built-in safety protocols and automated compliance tracking for all laboratory operations.</p>
                </div>
              </div>
              
              <div class="flex items-start space-x-4">
                <div class="w-6 h-6 bg-black rounded flex items-center justify-center flex-shrink-0 mt-1">
                  <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                  </svg>
                </div>
                <div>
                  <h4 class="font-semibold text-black mb-2">Research Support</h4>
                  <p class="text-gray-600">Comprehensive tools and features designed to enhance academic research and student projects.</p>
                </div>
              </div>
              
              <div class="flex items-start space-x-4">
                <div class="w-6 h-6 bg-black rounded flex items-center justify-center flex-shrink-0 mt-1">
                  <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                  </svg>
                </div>
                <div>
                  <h4 class="font-semibold text-black mb-2">Cost Efficiency</h4>
                  <p class="text-gray-600">Optimize resource allocation and significantly reduce operational costs through intelligent management.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Contact Section -->
      <section id="contact" class="py-16">
        <div class="container mx-auto px-6">
          <div class="max-w-4xl mx-auto text-center">
            <h2 class="text-3xl font-bold mb-6">Get Started Today</h2>
            <p class="text-gray-700 text-lg mb-8 max-w-2xl mx-auto">
              Ready to transform your laboratory management? Join Saint Francis Xavier College's commitment to academic excellence.
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-8">
              <router-link
                to="/contact"
                class="bg-black text-white px-8 py-3 font-medium hover:bg-gray-800 transition-colors"
              >
                Contact IT Support
              </router-link>
              <router-link
                to="/about"
                class="border border-gray-400 text-gray-700 px-8 py-3 font-medium hover:border-black hover:text-black transition-colors"
              >
                Learn More
              </router-link>
            </div>

            <div class="text-center text-gray-400 text-sm">
              <p>&copy; 2024 Saint Francis Xavier College. All rights reserved.</p>
            </div>
          </div>
        </div>
      </section>
    </div>
</template>

<style scoped>
/* White theme styling */
.container {
  max-width: 1200px;
}

/* Smooth scrolling */
html {
  scroll-behavior: smooth;
}

/* Navigation styling */
nav {
  backdrop-filter: blur(10px);
}

/* Typography */
h1, h2, h3 {
  letter-spacing: -0.01em;
}

/* Hover effects */
.hover\:border-gray-400:hover {
  transform: translateY(-2px);
}

/* Remove all focus outlines */
button:focus, 
a:focus,
*:focus {
  outline: none !important;
}

/* Transitions */
* {
  transition: all 0.3s ease;
}

/* Section spacing */
section {
  scroll-margin-top: 80px;
}

/* Clean shadows */
.shadow-sm {
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}
</style>
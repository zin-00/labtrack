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
    <div class="min-h-screen bg-white text-gray-900 relative overflow-hidden">
      <!-- Animated Background - Soft Gradient Blobs -->
      <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
      </div>

      <!-- Navigation Bar -->
      <nav class="bg-white/90 backdrop-blur-xl fixed w-full top-0 z-50 border-b border-gray-200">
        <div class="container mx-auto px-6">
          <div class="flex items-center justify-between h-16">
            <!-- Logo -->
            <div class="flex items-center space-x-3">
              <div class="w-35 h-auto rounded flex items-center justify-center">
                <img src="../assets/lb5.png" alt="" srcset="">
              </div>
            </div>

            <!-- Navigation Links -->
            <div class="hidden md:flex items-center space-x-8">
              <button @click="scrollToSection('home')" 
              class="text-gray-600 hover:text-green-700 transition-colors text-sm focus:outline-none">
                Home
              </button>
              <button @click="scrollToSection('about')" 
              class="text-gray-600 hover:text-green-700 transition-colors text-sm focus:outline-none">
                About
              </button>
              <button @click="scrollToSection('features')" 
              class="text-gray-600 hover:text-green-700 transition-colors text-sm focus:outline-none">
                Features
              </button>
              <button @click="scrollToSection('benefits')" 
              class="text-gray-600 hover:text-green-700 transition-colors text-sm focus:outline-none">
                Benefits
              </button>
              <button @click="scrollToSection('contact')" 
              class="text-gray-600 hover:text-green-700 transition-colors text-sm focus:outline-none">
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
                class="bg-black rounded-[100px] text-white px-4 py-2 text-xs font-medium hover:bg-green-700 transition-colors"
              >
                Request Access
              </router-link>
            </div>
          </div>
        </div>
      </nav>

      <!-- Hero Section -->
      <section id="home" class=" pb-16 min-h-screen flex items-center">
        <div class="container mx-auto px-6 lg:px-12">
          <div class="flex flex-col lg:flex-row items-center justify-between gap-16">
            <!-- Left Side - Content -->
            <div class="w-full lg:w-1/2 text-center lg:text-left lg:pl-8">
              <!-- Main Logo & Title -->
              <div class="mb-6">
                <div class="w-64 h-auto flex items-center justify-center lg:justify-start mb-4">
                    <img src="../assets/lbv6.png" alt="LabTrack" class="w-full h-full object-contain">
                </div>
                <p class="text-sm text-gray-400 uppercase tracking-[0.2em] font-medium">
                  Computer Laboratory Tracker
                </p>
              </div>

              <!-- Subtitle -->
              <p class="text-lg text-gray-600 mb-10 max-w-md mx-auto lg:mx-0 leading-relaxed">
                Comprehensive Laboratory Management System for Academic Excellence
              </p>

              <!-- CTA Buttons -->
              <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                <router-link
                  to="/login"
                  class="bg-black text-white px-8 py-3 font-medium hover:bg-green-700 transition-colors"
                >
                  Get Started
                </router-link>
                <button 
                  @click="scrollToSection('about')"
                  class="border border-gray-400 text-gray-700 px-8 py-3 font-medium hover:border-green-700 hover:text-green-700 transition-colors"
                >
                  Learn More
                </button>
              </div>
            </div>

            <!-- Right Side - 3D RFID Card -->
            <div class="w-full lg:w-1/2 flex items-center justify-center">
              <div class="rfid-card-container">
                <!-- 3D RFID Card -->
                <div class="rfid-card">
                  <!-- Card Front -->
                  <div class="card-front">
                    <!-- Chip -->
                    <div class="chip">
                      <div class="chip-line"></div>
                      <div class="chip-line"></div>
                      <div class="chip-line"></div>
                      <div class="chip-line"></div>
                      <div class="chip-main"></div>
                    </div>
                    
                    <!-- RFID Waves -->
                    <div class="rfid-waves">
                      <div class="wave wave-1"></div>
                      <div class="wave wave-2"></div>
                      <div class="wave wave-3"></div>
                      <div class="wave wave-4"></div>
                    </div>
                    
                    <!-- Card Content -->
                    <div class="card-content">
                      <div class="card-logo">
                        <span class="font-bold">Lab</span><span class="font-light">Track</span>
                      </div>
                      <div class="card-id">ID: XXXX-XXXX-XXXX</div>
                    </div>
                    
                    <!-- Card Footer -->
                    <div class="card-footer">
                      <div class="card-label">ACCESS CARD</div>
                      <div class="card-type">RFID</div>
                    </div>
                  </div>
                </div>
                
                <!-- Floating text -->
                <div class="mt-8 text-center">
                  <p class="text-gray-500 text-sm">Tap your RFID card to access</p>
                </div>
              </div>
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
        <div class="bg-white border border-gray-200 p-6 hover:border-green-700 transition-colors shadow-sm hover:shadow-md">
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
        <div class="bg-white border border-gray-200 p-6 hover:border-green-700 transition-colors shadow-sm hover:shadow-md">
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
        <div class="bg-white border border-gray-200 p-6 hover:border-green-700 transition-colors shadow-sm hover:shadow-md">
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
                <div class="w-6 h-6 bg-green-700 rounded flex items-center justify-center flex-shrink-0 mt-1">
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
                <div class="w-6 h-6 bg-green-700 rounded flex items-center justify-center flex-shrink-0 mt-1">
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
                <div class="w-6 h-6 bg-green-700 rounded flex items-center justify-center flex-shrink-0 mt-1">
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
                <div class="w-6 h-6 bg-green-700 rounded flex items-center justify-center flex-shrink-0 mt-1">
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
                class="bg-black text-white px-8 py-3 font-medium hover:bg-green-700 transition-colors"
              >
                Contact IT Support
              </router-link>
              <router-link
                to="/about"
                class="border border-gray-400 text-gray-700 px-8 py-3 font-medium hover:border-green-700 hover:text-green-700 transition-colors"
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

/* ========== Animated Background - Soft Gradient Blobs ========== */

.blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.5;
  animation: blob-float 20s ease-in-out infinite;
}

.blob-1 {
  width: 600px;
  height: 600px;
  background: linear-gradient(135deg, rgba(229, 231, 235, 0.8) 0%, rgba(209, 213, 219, 0.6) 100%);
  top: -200px;
  right: -100px;
  animation-delay: 0s;
}

.blob-2 {
  width: 500px;
  height: 500px;
  background: linear-gradient(135deg, rgba(243, 244, 246, 0.8) 0%, rgba(229, 231, 235, 0.6) 100%);
  bottom: -150px;
  left: -100px;
  animation-delay: -7s;
}

.blob-3 {
  width: 400px;
  height: 400px;
  background: linear-gradient(135deg, rgba(249, 250, 251, 0.7) 0%, rgba(243, 244, 246, 0.5) 100%);
  top: 40%;
  left: 50%;
  transform: translate(-50%, -50%);
  animation-delay: -14s;
}

@keyframes blob-float {
  0%, 100% {
    transform: translate(0, 0) scale(1);
  }
  25% {
    transform: translate(30px, -30px) scale(1.05);
  }
  50% {
    transform: translate(-20px, 20px) scale(0.95);
  }
  75% {
    transform: translate(20px, 10px) scale(1.02);
  }
}

/* Sections need relative positioning to stay above background */
section {
  position: relative;
  z-index: 1;
}

nav {
  position: relative;
  z-index: 50;
}

/* ========== 3D RFID Card Styles ========== */

.rfid-card-container {
  perspective: 1000px;
}

.rfid-card {
  width: 320px;
  height: 200px;
  position: relative;
  transform-style: preserve-3d;
  animation: card-float 6s ease-in-out infinite;
}

.card-front {
  width: 100%;
  height: 100%;
  background: linear-gradient(145deg, #1a1a1a 0%, #0a0a0a 50%, #1a1a1a 100%);
  border-radius: 16px;
  padding: 24px;
  position: relative;
  box-shadow: 
    0 25px 50px -12px rgba(0, 0, 0, 0.4),
    0 0 0 1px rgba(255, 255, 255, 0.1),
    inset 0 1px 0 rgba(255, 255, 255, 0.1);
  transform: rotateY(-15deg) rotateX(5deg);
  transition: transform 0.3s ease;
}

.rfid-card:hover .card-front {
  transform: rotateY(-5deg) rotateX(2deg);
}

/* Chip styles */
.chip {
  width: 45px;
  height: 35px;
  background: linear-gradient(135deg, #e8e8e8 0%, #c0c0c0 50%, #e8e8e8 100%);
  border-radius: 6px;
  position: relative;
  overflow: hidden;
  box-shadow: 
    inset 0 1px 2px rgba(255, 255, 255, 0.8),
    0 2px 4px rgba(0, 0, 0, 0.3);
}

.chip-line {
  position: absolute;
  background: #a0a0a0;
}

.chip-line:nth-child(1) {
  width: 100%;
  height: 1px;
  top: 33%;
}

.chip-line:nth-child(2) {
  width: 100%;
  height: 1px;
  top: 66%;
}

.chip-line:nth-child(3) {
  width: 1px;
  height: 100%;
  left: 33%;
}

.chip-line:nth-child(4) {
  width: 1px;
  height: 100%;
  left: 66%;
}

.chip-main {
  position: absolute;
  width: 33%;
  height: 33%;
  background: linear-gradient(135deg, #d0d0d0 0%, #b0b0b0 100%);
  top: 33%;
  left: 33%;
  border-radius: 2px;
}

/* RFID Waves */
.rfid-waves {
  position: absolute;
  top: 20px;
  right: 24px;
  width: 50px;
  height: 50px;
}

.wave {
  position: absolute;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  animation: pulse 2s ease-in-out infinite;
}

.wave-1 {
  width: 15px;
  height: 15px;
  top: 17px;
  left: 17px;
  animation-delay: 0s;
}

.wave-2 {
  width: 25px;
  height: 25px;
  top: 12px;
  left: 12px;
  animation-delay: 0.2s;
}

.wave-3 {
  width: 35px;
  height: 35px;
  top: 7px;
  left: 7px;
  animation-delay: 0.4s;
}

.wave-4 {
  width: 45px;
  height: 45px;
  top: 2px;
  left: 2px;
  animation-delay: 0.6s;
}

/* Card Content */
.card-content {
  position: absolute;
  bottom: 60px;
  left: 24px;
}

.card-logo {
  font-size: 24px;
  color: white;
  letter-spacing: 1px;
}

.card-logo .font-bold {
  font-weight: 700;
}

.card-logo .font-light {
  font-weight: 300;
  color: rgba(255, 255, 255, 0.6);
}

.card-id {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.4);
  font-family: 'Courier New', monospace;
  letter-spacing: 2px;
  margin-top: 4px;
}

/* Card Footer */
.card-footer {
  position: absolute;
  bottom: 24px;
  left: 24px;
  right: 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-label {
  font-size: 10px;
  color: rgba(255, 255, 255, 0.5);
  letter-spacing: 3px;
  font-weight: 500;
}

.card-type {
  font-size: 10px;
  color: rgba(255, 255, 255, 0.5);
  letter-spacing: 2px;
  padding: 4px 8px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 4px;
}

/* Card Animations */
@keyframes card-float {
  0%, 100% {
    transform: translateY(0px);
  }
  50% {
    transform: translateY(-15px);
  }
}

@keyframes pulse {
  0%, 100% {
    opacity: 0.3;
    transform: scale(1);
  }
  50% {
    opacity: 0.6;
    transform: scale(1.05);
  }
}
</style>


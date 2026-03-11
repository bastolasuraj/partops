<template>
  <div class="flex flex-col flex-grow min-h-screen">
    <!-- Navbar (hide on login and sitemap pages) -->
    <nav v-if="$route.path !== '/login' && $route.path !== '/sitemap'" class="navbar overflow-visible">
      <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center justify-between h-16 relative">
          <router-link to="/" class="flex items-center no-underline">
            <img src="@/assets/logo.png" alt="PAM" class="h-[74px] w-auto translate-y-2 drop-shadow-lg" />
          </router-link>
          
          <div class="hidden lg:flex gap-1 items-center">
            <!-- Dashboard -->
            <router-link 
              to="/dashboard"
              class="nav-item nav-link px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200"
              :class="{ 
                'bg-white/20 text-white': $route.path === '/dashboard',
                'text-white/90 hover:bg-white/10 hover:text-white': $route.path !== '/dashboard'
              }"
            >
              Dashboard
            </router-link>

            <!-- Parts Master -->
            <router-link 
              to="/parts"
              class="nav-item nav-link px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200"
              :class="{ 
                'bg-white/20 text-white': $route.path === '/parts',
                'text-white/90 hover:bg-white/10 hover:text-white': $route.path !== '/parts'
              }"
            >
              Parts Master
            </router-link>

            <!-- Master Data Dropdown -->
            <div class="relative dropdown-container nav-item" @mouseenter="masterDataOpen = true" @mouseleave="masterDataOpen = false">
              <button 
                class="nav-link h-full px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-1"
                :class="{ 
                  'bg-white/20 text-white': ['/suppliers', '/technicians', '/units'].includes($route.path),
                  'text-white/90 hover:bg-white/10 hover:text-white': !['/suppliers', '/technicians', '/units'].includes($route.path)
                }"
              >
                Master Data
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
              </button>
              
              <!-- Dropdown Menu -->
              <div v-if="masterDataOpen" class="absolute left-0 top-full w-48 bg-white rounded-lg shadow-xl py-2 z-50">
                <router-link 
                  to="/suppliers"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors"
                  :class="{ 'bg-blue-50 text-blue-600 font-medium': $route.path === '/suppliers' }"
                >
                  <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <rect x="1" y="3" width="15" height="13"></rect>
                      <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                      <circle cx="5.5" cy="18.5" r="2.5"></circle>
                      <circle cx="18.5" cy="18.5" r="2.5"></circle>
                    </svg>
                    Suppliers
                  </div>
                </router-link>
                <router-link 
                  to="/technicians"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors"
                  :class="{ 'bg-blue-50 text-blue-600 font-medium': $route.path === '/technicians' }"
                >
                  <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                      <circle cx="9" cy="7" r="4"></circle>
                      <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                      <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    Technicians
                  </div>
                </router-link>
                <router-link 
                  to="/units"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors"
                  :class="{ 'bg-blue-50 text-blue-600 font-medium': $route.path === '/units' }"
                >
                  <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                      <line x1="8" y1="21" x2="16" y2="21"></line>
                      <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                    Units
                  </div>
                </router-link>
              </div>
            </div>

            <!-- Asset Operations Dropdown -->
            <div class="relative dropdown-container nav-item" @mouseenter="assetOpsOpen = true" @mouseleave="assetOpsOpen = false">
              <button 
                class="nav-link h-full px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-1"
                :class="{ 
                  'bg-white/20 text-white': ['/incoming', '/outgoing', '/returns', '/transactions', '/reports'].includes($route.path),
                  'text-white/90 hover:bg-white/10 hover:text-white': !['/incoming', '/outgoing', '/returns', '/transactions', '/reports'].includes($route.path)
                }"
              >
                Asset Ops
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
              </button>
              
              <!-- Dropdown Menu -->
              <div v-if="assetOpsOpen" class="absolute left-0 top-full w-48 bg-white rounded-lg shadow-xl py-2 z-50">
                <router-link 
                  to="/incoming"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors"
                  :class="{ 'bg-blue-50 text-blue-600 font-medium': $route.path === '/incoming' }"
                >
                  <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <line x1="12" y1="5" x2="12" y2="19"></line>
                      <polyline points="19 12 12 19 5 12"></polyline>
                    </svg>
                    Incoming
                  </div>
                </router-link>
                <router-link 
                  to="/outgoing"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors"
                  :class="{ 'bg-blue-50 text-blue-600 font-medium': $route.path === '/outgoing' }"
                >
                  <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <line x1="12" y1="19" x2="12" y2="5"></line>
                      <polyline points="5 12 12 5 19 12"></polyline>
                    </svg>
                    Outgoing
                  </div>
                </router-link>
                <router-link 
                  to="/returns"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors"
                  :class="{ 'bg-blue-50 text-blue-600 font-medium': $route.path === '/returns' }"
                >
                  <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="1 4 1 10 7 10"></polyline>
                      <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                    Return to Vendor
                  </div>
                </router-link>
                <router-link 
                  to="/transactions"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors"
                  :class="{ 'bg-blue-50 text-blue-600 font-medium': $route.path === '/transactions' }"
                >
                  <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <line x1="8" y1="6" x2="21" y2="6"></line>
                      <line x1="8" y1="12" x2="21" y2="12"></line>
                      <line x1="8" y1="18" x2="21" y2="18"></line>
                      <line x1="3" y1="6" x2="3.01" y2="6"></line>
                      <line x1="3" y1="12" x2="3.01" y2="12"></line>
                      <line x1="3" y1="18" x2="3.01" y2="18"></line>
                    </svg>
                    Transactions
                  </div>
                </router-link>
                <router-link 
                  to="/reports"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors"
                  :class="{ 'bg-blue-50 text-blue-600 font-medium': $route.path === '/reports' }"
                >
                  <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <line x1="4" y1="20" x2="20" y2="20"></line>
                      <rect x="6" y="11" width="3" height="7"></rect>
                      <rect x="11" y="7" width="3" height="11"></rect>
                      <rect x="16" y="4" width="3" height="14"></rect>
                    </svg>
                    Reports
                  </div>
                </router-link>
              </div>
            </div>
            
            <!-- User Menu -->
            <div class="relative ml-4 nav-item" @mouseenter="userMenuOpen = true" @mouseleave="userMenuOpen = false">
              <button 
                class="flex items-center gap-2 text-white hover:bg-white/10 px-3 py-2 rounded-lg transition-colors h-full"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                  <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span class="text-sm">{{ firstName }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
              </button>
              
              <!-- Dropdown Menu -->
              <div v-if="userMenuOpen" class="absolute right-0 top-full mt-0 w-48 bg-white rounded-lg shadow-xl py-2 z-50">
                <div class="px-4 py-2 border-b border-gray-200">
                  <p class="text-sm font-medium text-gray-900">{{ currentUser?.display_name }}</p>
                  <p class="text-xs text-gray-500">{{ currentUser?.username }}@fowler.ca</p>
                </div>
                <router-link
                  v-if="isAdmin"
                  to="/settings"
                  class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
                  @click="userMenuOpen = false"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c0 .67.39 1.27 1 1.51H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                  </svg>
                  Settings
                </router-link>
                <router-link
                  v-if="isAdmin"
                  to="/tests"
                  class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
                  @click="userMenuOpen = false"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 11l3 3L22 4"></path>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                  </svg>
                  Tests
                </router-link>
                <button 
                  @click="handleLogout"
                  class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                  </svg>
                  Logout
                </button>
              </div>
            </div>
          </div>

          <!-- Mobile Menu Button -->
          <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden text-white p-2">
            <svg v-if="!mobileMenuOpen" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="3" y1="12" x2="21" y2="12"></line>
              <line x1="3" y1="6" x2="21" y2="6"></line>
              <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
      </div>
    </nav>

    <!-- Mobile Sidebar Menu (Slide from Right) -->
    <Transition name="slide-right">
      <div v-if="mobileMenuOpen" class="fixed inset-0 z-50 lg:hidden">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="mobileMenuOpen = false"></div>
        
        <!-- Sidebar -->
        <div class="absolute right-0 top-0 bottom-0 w-80 max-w-[85vw] bg-white shadow-2xl overflow-y-auto">
          <!-- Header -->
          <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-4 flex items-center justify-between">
            <div class="flex items-center">
              <img src="@/assets/logo.png" alt="PAM" class="h-8 w-auto" />
            </div>
            <button @click="mobileMenuOpen = false" class="text-white p-2 hover:bg-white/10 rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>

          <!-- User -->
          <div class="px-4 pt-4">
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
              <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-semibold">
                  {{ firstName?.charAt(0) || 'U' }}
                </div>
                <div class="min-w-0">
                  <div class="text-sm font-semibold text-gray-900 truncate">{{ currentUser?.display_name || firstName }}</div>
                  <div class="text-xs text-gray-500 truncate">{{ currentUser?.username ? `${currentUser.username}@fowler.ca` : '' }}</div>
                </div>
              </div>
              <div class="mt-3 flex flex-col gap-1">
                <router-link
                  v-if="isAdmin"
                  to="/settings"
                  @click="mobileMenuOpen = false"
                  class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-700 hover:bg-white transition-colors"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c0 .67.39 1.27 1 1.51H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                  </svg>
                  Settings
                </router-link>
                <router-link
                  v-if="isAdmin"
                  to="/tests"
                  @click="mobileMenuOpen = false"
                  class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-700 hover:bg-white transition-colors"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 11l3 3L22 4"></path>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                  </svg>
                  Tests
                </router-link>
                <button
                  @click="handleLogout"
                  class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-red-600 hover:bg-white transition-colors text-left"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                  </svg>
                  Logout
                </button>
              </div>
            </div>
          </div>

          <!-- Menu Items -->
          <div class="p-4">
            <!-- Dashboard -->
            <router-link 
              to="/dashboard"
              @click="mobileMenuOpen = false"
              class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-100 transition-colors"
              :class="{ 'bg-blue-50 text-blue-600 font-medium': $route.path === '/dashboard' }"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
              </svg>
              Dashboard
            </router-link>

            <!-- Parts Master -->
            <router-link 
              to="/parts"
              @click="mobileMenuOpen = false"
              class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-100 transition-colors"
              :class="{ 'bg-blue-50 text-blue-600 font-medium': $route.path === '/parts' }"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line>
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
              </svg>
              Parts Master
            </router-link>

            <!-- Master Data Section -->
            <div class="mt-4">
              <button 
                @click="mobileMasterDataOpen = !mobileMasterDataOpen"
                class="w-full flex items-center justify-between p-3 rounded-lg hover:bg-gray-100 transition-colors font-medium text-gray-700"
              >
                <div class="flex items-center gap-3">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                  </svg>
                  Master Data
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform" :class="{ 'rotate-180': mobileMasterDataOpen }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
              </button>
              
              <div v-if="mobileMasterDataOpen" class="ml-8 mt-1 space-y-1">
                <router-link 
                  to="/suppliers"
                  @click="mobileMenuOpen = false"
                  class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-100 transition-colors text-sm"
                  :class="{ 'bg-blue-50 text-blue-600 font-medium': $route.path === '/suppliers' }"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="3" width="15" height="13"></rect>
                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                    <circle cx="5.5" cy="18.5" r="2.5"></circle>
                    <circle cx="18.5" cy="18.5" r="2.5"></circle>
                  </svg>
                  Suppliers
                </router-link>
                <router-link 
                  to="/technicians"
                  @click="mobileMenuOpen = false"
                  class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-100 transition-colors text-sm"
                  :class="{ 'bg-blue-50 text-blue-600 font-medium': $route.path === '/technicians' }"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                  </svg>
                  Technicians
                </router-link>
                <router-link 
                  to="/units"
                  @click="mobileMenuOpen = false"
                  class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-100 transition-colors text-sm"
                  :class="{ 'bg-blue-50 text-blue-600 font-medium': $route.path === '/units' }"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                    <line x1="8" y1="21" x2="16" y2="21"></line>
                    <line x1="12" y1="17" x2="12" y2="21"></line>
                  </svg>
                  Units
                </router-link>
              </div>
            </div>

            <!-- Asset Operations Section -->
            <div class="mt-2">
              <button 
                @click="mobileAssetOpsOpen = !mobileAssetOpsOpen"
                class="w-full flex items-center justify-between p-3 rounded-lg hover:bg-gray-100 transition-colors font-medium text-gray-700"
              >
                <div class="flex items-center gap-3">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                  </svg>
                  Asset Ops
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform" :class="{ 'rotate-180': mobileAssetOpsOpen }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
              </button>
              
              <div v-if="mobileAssetOpsOpen" class="ml-8 mt-1 space-y-1">
                <router-link 
                  to="/incoming"
                  @click="mobileMenuOpen = false"
                  class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-100 transition-colors text-sm"
                  :class="{ 'bg-blue-50 text-blue-600 font-medium': $route.path === '/incoming' }"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <polyline points="19 12 12 19 5 12"></polyline>
                  </svg>
                  Incoming
                </router-link>
                <router-link 
                  to="/outgoing"
                  @click="mobileMenuOpen = false"
                  class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-100 transition-colors text-sm"
                  :class="{ 'bg-blue-50 text-blue-600 font-medium': $route.path === '/outgoing' }"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="19" x2="12" y2="5"></line>
                    <polyline points="5 12 12 5 19 12"></polyline>
                  </svg>
                  Outgoing
                </router-link>
                <router-link 
                  to="/returns"
                  @click="mobileMenuOpen = false"
                  class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-100 transition-colors text-sm"
                  :class="{ 'bg-blue-50 text-blue-600 font-medium': $route.path === '/returns' }"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="1 4 1 10 7 10"></polyline>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                  </svg>
                  Return to Vendor
                </router-link>
                <router-link 
                  to="/transactions"
                  @click="mobileMenuOpen = false"
                  class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-100 transition-colors text-sm"
                  :class="{ 'bg-blue-50 text-blue-600 font-medium': $route.path === '/transactions' }"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="8" y1="6" x2="21" y2="6"></line>
                    <line x1="8" y1="12" x2="21" y2="12"></line>
                    <line x1="8" y1="18" x2="21" y2="18"></line>
                    <line x1="3" y1="6" x2="3.01" y2="6"></line>
                    <line x1="3" y1="12" x2="3.01" y2="12"></line>
                    <line x1="3" y1="18" x2="3.01" y2="18"></line>
                  </svg>
                  Transactions
                </router-link>
                <router-link 
                  to="/reports"
                  @click="mobileMenuOpen = false"
                  class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-100 transition-colors text-sm"
                  :class="{ 'bg-blue-50 text-blue-600 font-medium': $route.path === '/reports' }"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="4" y1="20" x2="20" y2="20"></line>
                    <rect x="6" y="11" width="3" height="7"></rect>
                    <rect x="11" y="7" width="3" height="11"></rect>
                    <rect x="16" y="4" width="3" height="14"></rect>
                  </svg>
                  Reports
                </router-link>
              </div>
            </div>

            <!-- Divider -->
            <div class="my-4 border-t border-gray-200"></div>

            <!-- Additional Links -->
            <router-link 
              to="/work-orders"
              @click="mobileMenuOpen = false"
              class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-100 transition-colors text-sm"
              :class="{ 'bg-blue-50 text-blue-600 font-medium': $route.path === '/work-orders' }"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
              </svg>
              Work Orders
            </router-link>
            <router-link 
              to="/scanner"
              @click="mobileMenuOpen = false"
              class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-100 transition-colors text-sm"
              :class="{ 'bg-blue-50 text-blue-600 font-medium': $route.path === '/scanner' }"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 7V5a2 2 0 0 1 2-2h2"></path>
                <path d="M17 3h2a2 2 0 0 1 2 2v2"></path>
                <path d="M21 17v2a2 2 0 0 1-2 2h-2"></path>
                <path d="M7 21H5a2 2 0 0 1-2-2v-2"></path>
                <rect x="7" y="7" width="10" height="10" rx="2"></rect>
              </svg>
              QR Scanner
            </router-link>
            <router-link 
              to="/low-stock"
              @click="mobileMenuOpen = false"
              class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-100 transition-colors text-sm"
              :class="{ 'bg-blue-50 text-blue-600 font-medium': $route.path === '/low-stock' }"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
              </svg>
              Low Stock
            </router-link>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 py-8">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>

    <!-- Footer (hide on sitemap page) -->
    <footer v-if="$route.path !== '/sitemap'" class="footer">
      <div class="max-w-7xl mx-auto container">
        <div class="hidden lg:grid lg:grid-cols-4 gap-8">
          <!-- Main Navigation -->
          <div>
            <h3 class="text-white font-semibold mb-4">Main</h3>
            <div class="flex flex-col gap-2">
              <router-link to="/dashboard" class="text-gray-400 hover:text-white transition-colors text-sm">Dashboard</router-link>
              <router-link to="/parts" class="text-gray-400 hover:text-white transition-colors text-sm">Parts Master</router-link>
            </div>
          </div>

          <!-- Master Data -->
          <div>
            <h3 class="text-white font-semibold mb-4">Master Data</h3>
            <div class="flex flex-col gap-2">
              <router-link to="/suppliers" class="text-gray-400 hover:text-white transition-colors text-sm">Suppliers</router-link>
              <router-link to="/technicians" class="text-gray-400 hover:text-white transition-colors text-sm">Technicians</router-link>
              <router-link to="/units" class="text-gray-400 hover:text-white transition-colors text-sm">Units</router-link>
            </div>
          </div>

          <!-- Asset Operations -->
          <div>
            <h3 class="text-white font-semibold mb-4">Asset Ops</h3>
            <div class="flex flex-col gap-2">
              <router-link to="/incoming" class="text-gray-400 hover:text-white transition-colors text-sm">Incoming</router-link>
              <router-link to="/outgoing" class="text-gray-400 hover:text-white transition-colors text-sm">Outgoing</router-link>
              <router-link to="/returns" class="text-gray-400 hover:text-white transition-colors text-sm">Return to Vendor</router-link>
              <router-link to="/transactions" class="text-gray-400 hover:text-white transition-colors text-sm">Transactions</router-link>
              <router-link to="/reports" class="text-gray-400 hover:text-white transition-colors text-sm">Reports</router-link>
            </div>
          </div>

          <!-- Additional Views -->
          <div>
            <h3 class="text-white font-semibold mb-4">More</h3>
            <div class="flex flex-col gap-2">
              <router-link to="/work-orders" class="text-gray-400 hover:text-white transition-colors text-sm">Work Orders</router-link>
              <router-link to="/low-stock" class="text-gray-400 hover:text-white transition-colors text-sm">Low Stock</router-link>
              <router-link to="/fowler-mapping" class="text-gray-400 hover:text-white transition-colors text-sm">Fowler Mapping</router-link>
              <router-link to="/sitemap" class="text-gray-400 hover:text-white transition-colors text-sm">Sitemap</router-link>
            </div>
          </div>
        </div>

        <!-- Copyright -->
        <div class="pt-6 text-center" :class="route.path !== '/login' ? 'lg:mt-12 lg:border-t lg:border-gray-700' : ''">
          <p class="text-sm text-gray-500">&copy; 2024 PAM - Parts Asset Management - <a href="https://fowler.ca">Fowler Construction</a> . All rights reserved.</p>
        </div>
      </div>
    </footer>

    <!-- Toast Notification -->
    <Transition name="slide-up">
      <div
        v-if="toast.show"
        class="fixed bottom-6 right-6 max-w-sm w-full bg-white border border-gray-200 rounded-xl shadow-2xl p-4 z-50 flex items-start gap-3 border-l-4"
        :class="toastMeta.containerClass"
      >
        <div class="text-[10px] font-bold tracking-wide px-2 py-1 rounded" :class="toastMeta.badgeClass">
          {{ toastMeta.iconText }}
        </div>
        <div>
          <div class="font-bold mb-0.5" :class="toastMeta.titleClass">{{ toast.title }}</div>
          <div v-if="toast.message" class="text-gray-600 text-sm">{{ toast.message }}</div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from '@/composables/useToast'
import { authApi } from '@/services/api'
import { useSettings } from '@/composables/useSettings'

const router = useRouter()
const route = useRoute()
const { toast, showToast } = useToast()
const mobileMenuOpen = ref(false)
const userMenuOpen = ref(false)
const masterDataOpen = ref(false)
const assetOpsOpen = ref(false)
const mobileMasterDataOpen = ref(false)
const mobileAssetOpsOpen = ref(false)
const currentUser = ref(null)
const { loadSettings } = useSettings()

const firstName = computed(() => {
  if (!currentUser.value) {
    return 'User'
  }

  if (currentUser.value.first_name) {
    return currentUser.value.first_name
  }

  const displayName = currentUser.value.display_name || currentUser.value.username || ''
  const parts = displayName.trim().split(/\s+/)
  return parts[0] || 'User'
})

const isAdmin = computed(() => currentUser.value?.role === 'admin')

const toastMeta = computed(() => {
  const type = toast.value?.type || 'success'

  switch (type) {
    case 'error':
      return {
        iconText: 'ERR',
        titleClass: 'text-red-600',
        badgeClass: 'bg-red-100 text-red-700',
        containerClass: 'border-l-red-500'
      }
    case 'warning':
      return {
        iconText: 'WARN',
        titleClass: 'text-amber-700',
        badgeClass: 'bg-amber-100 text-amber-800',
        containerClass: 'border-l-amber-500'
      }
    case 'info':
      return {
        iconText: 'INFO',
        titleClass: 'text-blue-700',
        badgeClass: 'bg-blue-100 text-blue-800',
        containerClass: 'border-l-blue-500'
      }
    default:
      return {
        iconText: 'OK',
        titleClass: 'text-green-600',
        badgeClass: 'bg-green-100 text-green-700',
        containerClass: 'border-l-green-500'
      }
  }
})

const loadCurrentUser = async () => {
  try {
    const response = await authApi.me()
    currentUser.value = response.data.user
  } catch (error) {
    // User not authenticated, will be redirected by router guard
    console.error('Failed to load user info:', error)
  }
}

const navItems = [
  { path: '/dashboard', title: 'Dashboard', icon: 'HomeIcon' },
  { path: '/parts', title: 'Parts Master', icon: 'GridIcon' },
  { path: '/suppliers', title: 'Suppliers', icon: 'TruckIcon' },
  { path: '/technicians', title: 'Technicians', icon: 'UsersIcon' },
  { path: '/units', title: 'Units', icon: 'MonitorIcon' },
  { path: '/incoming', title: 'Incoming', icon: 'ArrowDownIcon' },
  { path: '/outgoing', title: 'Outgoing', icon: 'ArrowUpIcon' },
  { path: '/returns', title: 'Return to Vendor', icon: 'RotateIcon' },
  { path: '/transactions', title: 'Transactions', icon: 'ListIcon' },
  { path: '/reports', title: 'Reports', icon: 'BarChartIcon' },
]

// Load current user info
onMounted(() => {
  if (route.path !== '/login') {
    loadCurrentUser()
    loadSettings(true)
  }
})

watch(() => route.path, (path) => {
  if (path === '/login') {
    currentUser.value = null
    return
  }

  if (!currentUser.value) {
    loadCurrentUser()
  }
  loadSettings(true)
})

// Handle logout
const handleLogout = async () => {
  try {
    await authApi.logout()
    showToast('Logged out successfully', 'success')
    router.push('/login')
  } catch (error) {
    console.error('Logout error:', error)
    showToast('Logout failed', 'error')
  }
}

// Close user menu when clicking outside
const closeUserMenu = () => {
  userMenuOpen.value = false
}
</script>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(20px);
}

/* Slide from right animation */
.slide-right-enter-active,
.slide-right-leave-active {
  transition: all 0.3s ease-out;
}

.slide-right-enter-from .absolute.right-0,
.slide-right-leave-to .absolute.right-0 {
  transform: translateX(100%);
}

.slide-right-enter-from .absolute.inset-0,
.slide-right-leave-to .absolute.inset-0 {
  opacity: 0;
}

/* Dropdown container */
.dropdown-container {
  padding-bottom: 0;
}

.dropdown-container > div[class*="absolute"] {
  margin-top: 0;
}
</style>


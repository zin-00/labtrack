<script setup>
import AuthenticatedLayout from '../../../layouts/auth/AuthenticatedLayout.vue';
import { useStates } from '../../../composable/states';

const states = useStates();
const {
    searchQuery
    } = states;
</script>

<template>
    <AuthenticatedLayout>
          <div class="py-4 max-w-7xl mx-auto sm:px-4 bg-white">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Year Levels</h2>
                    <p class="mt-1 text-xs text-gray-600">
                        Manage Year Levels records with full CRUD operations.
                    </p>
                </div>

                                                
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <!-- Left: Search & Filters -->
                <div class="flex flex-wrap gap-2 items-center">
                    <!-- Search Box -->
                    <div class="relative">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search globally..."
                        class="w-64 border border-gray-300 rounded-md px-3 py-1.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                    >
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        class="absolute right-2 top-2 text-gray-400 hover:text-gray-600"
                    >
                        <XIcon :stroke-width="1.50" class="w-4 h-4" />
                    </button>
                    </div>

                    <!-- Status Filter -->
                    <select
                        class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                        >
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Maintenance">Maintenance</option>
                    </select>

                    <!-- Program Filter -->
                    <!-- <select
                        v-model="selectedProgram"
                        class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                        >
                        <option value="all">All Programs</option>
                        <option 
                            v-for="program in prog.programs"
                            :key="program.id" 
                            :value="program.id">
                            
                        {{ program.program_code }}
                    </option>
                    </select> -->

                     <!-- Year Level Filter -->
                    <select
                        v-model="statusFilter"
                        class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                        >
                        <option value="">All Year Levels</option>
                        <option value="Active">1st Year</option>
                        <option value="Inactive">2nd Year</option>
                        <option value="Maintenance">3rd Year</option>
                        <option value="Maintenance">4th Year</option>
                    </select>

                     <!-- Section Filter -->
                    <select
                        v-model="statusFilter"
                        class="border border-gray-300 rounded-md px-2 py-1.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition"
                        >
                        <option value="">All Section</option>
                        <option value="Active">Section A</option>
                        <option value="Inactive">Section B</option>
                        <option value="Maintenance">Section C</option>
                        <option value="Maintenance">Section D</option>
                    </select>
                </div>

                <!-- Right: Action Buttons -->
                <div class="flex gap-2">
                    <Button
                        title="Refresh"
                        class="p-2 text-white  bg-gray-800 hover:bg-gray-700  rounded-md transition duration-200"
                        >
                        <RefreshCcwIcon :stroke-width="1.50" class="h-5 w-5" />
                    </Button>

                    <Button
                        title="Add User"
                        class="p-2  text-white  bg-gray-800 hover:bg-gray-700 rounded-md transition duration-200"
                        >
                        <UserRoundPlusIcon :stroke-width="1.50" class="h-5 w-5" />
                    </Button>

                    <Button
                        title="Import Users"
                        class="p-2  text-white  bg-gray-800 hover:bg-gray-700 rounded-md transition duration-200"
                        >
                        <FolderDownIcon :stroke-width="1.50" class="h-5 w-5" />
                    </Button>
                    <Button class="p-2 text-white bg-gray-800 hover:bg-gray-700 rounded-md transition duration-200">
                        <FolderUpIcon :stroke-width="1.50" class="h-5 w-5" />
                    </Button>
                </div>
            </div>
          </div>

    </AuthenticatedLayout>
</template>
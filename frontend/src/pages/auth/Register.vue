<script setup>
import { reactive, onMounted } from 'vue';
import axios from 'axios';
import InputError from '../../components/input/InputError.vue';
import Button from '../../components/button/Button.vue';
import AuthBase from '../../layouts/AuthLayout.vue';
import { LoaderCircle } from 'lucide-vue-next';
import { useToast } from '../../composable/toastification/useToast';
import TextInput from '../../components/input/TextInput.vue';
import router from '../../router';
import { useAuthStore } from '../../composable/useAuth';
import { storeToRefs } from 'pinia';

const auth = useAuthStore();

const { register, clear_form } = auth;
const {form} = storeToRefs(auth);

onMounted(() => {
  clear_form();
});
</script>

<template>
    <AuthBase title="Create an account" description="Enter your details below to create your account">
        <Head title="Register" />

        <form @submit.prevent="register" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <TextInput id="name" type="text" required autofocus :tabindex="1" autocomplete="name" v-model="form.name" placeholder="Full name" />
                    <InputError :message="form.errors?.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <TextInput id="email" type="email" required :tabindex="2" autocomplete="email" v-model="form.email" placeholder="email@example.com" />
                    <InputError :message="form.errors?.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <TextInput
                        id="password"
                        type="password"
                        required
                        :tabindex="3"
                        autocomplete="new-password"
                        v-model="form.password"
                        placeholder="Password"
                    />
                    <InputError :message="Array.isArray(form.errors?.password) ? form.errors.password[0] : form.errors?.password" />

                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        v-model="form.password_confirmation"
                        placeholder="Confirm password"
                    />
                      <InputError :message="Array.isArray(form.errors?.password) ? form.errors.password[0] : form.errors?.password" />
                </div>

                <Button variant="dark" type="submit" class="mt-2 h-[50px] w-full" tabindex="5" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Create account
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <router-link to="/login" class="underline underline-offset-4" :tabindex="6">Log in</router-link>
                <!-- <TextLink :href="route('login')" class="underline underline-offset-4" :tabindex="6">Log in</TextLink> -->
            </div>
        </form>
    </AuthBase>
</template>
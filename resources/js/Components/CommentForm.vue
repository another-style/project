<script setup>
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    parentId: {
        type: Number,
        default: null,
    },
    placeholder: {
        type: String,
        default: 'Написать сообщение...',
    },
});

const emit = defineEmits(['submitted']);

const form = useForm({
    name: '',
    message: '',
    parent_id: props.parentId,
});

const submit = () => {
    form.post(route('comments.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('name', 'message');
            emit('submitted');
        },
    });
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-3">
        <div>
            <input
                v-model="form.name"
                type="text"
                placeholder="Имя (необязательно)"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                maxlength="50"
            />
            <InputError :message="form.errors.name" class="mt-1" />
        </div>

        <div>
            <textarea
                v-model="form.message"
                :placeholder="placeholder"
                rows="4"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                maxlength="5000"
                required
            ></textarea>
            <InputError :message="form.errors.message" class="mt-1" />
        </div>

        <div class="flex items-center justify-between">
            <span class="text-xs text-gray-400">{{ form.message.length }} / 5000</span>
            <button
                type="submit"
                :disabled="form.processing"
                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
            >
                Отправить
            </button>
        </div>
    </form>
</template>

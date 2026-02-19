<script setup>
import { ref } from 'vue';
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
    showTags: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['submitted']);

const form = useForm({
    name: '',
    message: '',
    parent_id: props.parentId,
    tags: [],
});

const tagInput = ref('');

const addTag = () => {
    const tag = tagInput.value.trim();
    if (tag && !form.tags.includes(tag) && form.tags.length < 5) {
        form.tags.push(tag);
    }
    tagInput.value = '';
};

const removeTag = (index) => {
    form.tags.splice(index, 1);
};

const submit = () => {
    form.post(route('comments.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('name', 'message', 'tags');
            tagInput.value = '';
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

        <div v-if="showTags">
            <div v-if="form.tags.length > 0" class="mb-2 flex flex-wrap gap-1">
                <span
                    v-for="(tag, index) in form.tags"
                    :key="index"
                    class="inline-flex items-center gap-1 rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800"
                >
                    {{ tag }}
                    <button
                        type="button"
                        @click="removeTag(index)"
                        class="text-indigo-600 hover:text-indigo-900"
                    >
                        &times;
                    </button>
                </span>
            </div>
            <div class="flex gap-2">
                <input
                    v-model="tagInput"
                    type="text"
                    placeholder="Добавить тег (Enter)"
                    class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                    maxlength="50"
                    :disabled="form.tags.length >= 5"
                    @keydown.enter.prevent="addTag"
                />
            </div>
            <p class="mt-1 text-xs text-gray-400">{{ form.tags.length }} / 5 тегов</p>
            <InputError :message="form.errors.tags" class="mt-1" />
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

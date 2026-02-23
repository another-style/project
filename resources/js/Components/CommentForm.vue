<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import { useMarkdownToolbar } from '@/Composables/useMarkdownToolbar.js';

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
    initialMessage: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['submitted']);

const form = useForm({
    name: '',
    message: props.initialMessage,
    parent_id: props.parentId,
    tags: [],
    images: [],
});

const textareaRef = ref(null);
const fileInputRef = ref(null);
const tagInput = ref('');
const imagePreviews = ref([]);
const { applyMarkdown } = useMarkdownToolbar();

watch(() => props.initialMessage, (val) => {
    form.message = val;
});

const handleApplyMarkdown = (type) => {
    applyMarkdown(textareaRef, {
        get value() { return form.message; },
        set value(v) { form.message = v; },
    }, type);
};

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

const handleImageFiles = (e) => {
    const files = Array.from(e.target.files).slice(0, 5);
    imagePreviews.value.forEach(url => URL.revokeObjectURL(url));
    imagePreviews.value = files.map(f => URL.createObjectURL(f));
    form.images = files;
};

const removeImage = (index) => {
    URL.revokeObjectURL(imagePreviews.value[index]);
    imagePreviews.value.splice(index, 1);
    form.images = form.images.filter((_, i) => i !== index);
    if (form.images.length === 0 && fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const submit = () => {
    form.post(route('comments.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('name', 'message', 'tags');
            form.images = [];
            imagePreviews.value.forEach(url => URL.revokeObjectURL(url));
            imagePreviews.value = [];
            tagInput.value = '';
            if (fileInputRef.value) {
                fileInputRef.value.value = '';
            }
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

        <div class="flex flex-wrap gap-1 bg-gray-50 px-2 py-1.5">
            <button type="button" @click="handleApplyMarkdown('bold')" class="markdown-toolbar-btn" title="Жирный">
                <strong>Ж</strong>
            </button>
            <button type="button" @click="handleApplyMarkdown('italic')" class="markdown-toolbar-btn" title="Курсив">
                <em>К</em>
            </button>
            <button type="button" @click="handleApplyMarkdown('strikethrough')" class="markdown-toolbar-btn" title="Зачёркнутый">
                <del>S</del>
            </button>
            <button type="button" @click="handleApplyMarkdown('code')" class="markdown-toolbar-btn" title="Инлайн-код">
                &lt;&gt;
            </button>
            <button type="button" @click="handleApplyMarkdown('codeBlock')" class="markdown-toolbar-btn" title="Блок кода">
                ```
            </button>
            <button type="button" @click="handleApplyMarkdown('list')" class="markdown-toolbar-btn" title="Список">
                •
            </button>
            <button type="button" @click="handleApplyMarkdown('orderedList')" class="markdown-toolbar-btn" title="Нумерованный список">
                1.
            </button>
            <button type="button" @click="handleApplyMarkdown('quote')" class="markdown-toolbar-btn" title="Цитата">
                &gt;
            </button>
        </div>

        <div>
            <textarea
                ref="textareaRef"
                v-model="form.message"
                :placeholder="placeholder"
                rows="4"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                maxlength="5000"
                required
            ></textarea>
            <InputError :message="form.errors.message" class="mt-1" />
        </div>

        <span class="text-xs text-gray-400">{{ form.message.length }} / 5000</span>

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

        <div>
            <input
                ref="fileInputRef"
                type="file"
                multiple
                accept=".jpg,.jpeg,.png,.webp"
                class="block w-full text-sm text-gray-500 file:mr-3 file:cursor-pointer file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100"
                :disabled="form.images.length >= 5"
                @change="handleImageFiles"
            />
            <div v-if="imagePreviews.length > 0" class="mt-2 flex flex-wrap gap-2">
                <div
                    v-for="(preview, index) in imagePreviews"
                    :key="index"
                    class="relative"
                >
                    <img
                        :src="preview"
                        class="h-16 w-16 rounded object-cover border border-gray-200"
                        alt=""
                    />
                    <button
                        type="button"
                        @click="removeImage(index)"
                        class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-white text-xs leading-none"
                    >
                        &times;
                    </button>
                </div>
            </div>
            <p class="mt-1 text-xs text-gray-400">Форматы: JPG, PNG, WebP. Не более 5 файлов (по 10 МБ).</p>
            <InputError :message="form.errors.images" class="mt-1" />
        </div>

        <div class="flex justify-end">
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

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import CommentForm from '@/Components/CommentForm.vue';

const props = defineProps({
    comment: {
        type: Object,
        required: true,
    },
});

const showReplyForm = ref(false);

const toggleReplyForm = () => {
    showReplyForm.value = !showReplyForm.value;
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <div :id="'comment-' + comment.id" class="rounded-lg border border-gray-200 bg-white p-4">
        <div class="flex items-center justify-between text-sm text-gray-500">
            <span class="font-medium text-gray-900">
                {{ comment.name || 'Аноним' }}
            </span>
            <div class="flex items-center gap-3">
                <time>{{ formatDate(comment.created_at) }}</time>
                <Link
                    :href="route('comments.show', comment.id)"
                    class="text-indigo-600 hover:text-indigo-800"
                >
                    #{{ comment.id }}
                </Link>
            </div>
        </div>

        <div class="mt-2 whitespace-pre-wrap text-sm text-gray-800">{{ comment.message }}</div>

        <div class="mt-3">
            <button
                @click="toggleReplyForm"
                class="text-sm text-indigo-600 hover:text-indigo-800"
            >
                {{ showReplyForm ? 'Отмена' : 'Ответить' }}
            </button>
        </div>

        <div v-if="showReplyForm" class="mt-3">
            <CommentForm
                :parent-id="comment.id"
                placeholder="Написать ответ..."
                @submitted="showReplyForm = false"
            />
        </div>
    </div>
</template>

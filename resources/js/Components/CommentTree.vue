<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import CommentItem from '@/Components/CommentItem.vue';

const props = defineProps({
    comments: {
        type: Array,
        required: true,
    },
    depth: {
        type: Number,
        default: 0,
    },
    maxDepth: {
        type: Number,
        default: 10,
    },
    inlineLoadMaxDepth: {
        type: Number,
        default: 4,
    },
});

const loadedReplies = ref({});
const loadingReplies = ref({});

const loadMore = async (commentId) => {
    loadingReplies.value[commentId] = true;

    try {
        const response = await fetch(route('comments.replies', commentId));
        const data = await response.json();
        loadedReplies.value[commentId] = data.children;
    } finally {
        loadingReplies.value[commentId] = false;
    }
};
</script>

<template>
    <div class="space-y-3">
        <div v-for="comment in comments" :key="comment.id">
            <CommentItem :comment="comment" />

            <div v-if="comment.children && comment.children.length > 0" class="ml-6 mt-2 border-l-2 border-gray-200 pl-4">
                <CommentTree
                    :comments="comment.children"
                    :depth="depth + 1"
                    :max-depth="maxDepth"
                    :inline-load-max-depth="inlineLoadMaxDepth"
                />
            </div>

            <div v-if="comment.has_more_replies && !loadedReplies[comment.id]" class="ml-6 mt-2">
                <Link
                    v-if="depth >= inlineLoadMaxDepth"
                    :href="route('comments.show', comment.id)"
                    class="text-sm text-indigo-600 hover:text-indigo-800"
                >
                    Продолжить ветку &rarr;
                </Link>
                <button
                    v-else
                    @click="loadMore(comment.id)"
                    :disabled="loadingReplies[comment.id]"
                    class="text-sm text-indigo-600 hover:text-indigo-800 disabled:opacity-50"
                >
                    {{ loadingReplies[comment.id] ? 'Загрузка...' : 'Показать ещё ответы' }}
                </button>
            </div>

            <div v-if="loadedReplies[comment.id]" class="ml-6 mt-2 border-l-2 border-gray-200 pl-4">
                <CommentTree
                    :comments="loadedReplies[comment.id]"
                    :depth="depth + 1"
                    :max-depth="maxDepth"
                    :inline-load-max-depth="inlineLoadMaxDepth"
                />
            </div>
        </div>
    </div>
</template>
